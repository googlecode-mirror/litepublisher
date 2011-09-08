<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tlocalmerger extends titems {
  public $html;
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    $this->dbversion = false;
    parent::create();
    $this->basename = 'localmerger';
    $this->addmap('html', array());
  }
  
  public function save() {
    if ($this->lockcount > 0) return;
    parent::save();
    $this->assemble();
  }
  
  public function normfilename($filename) {
    $filename = trim($filename);
    if (strbegin($filename,litepublisher::$paths->home)) $filename = substr($filename, strlen(litepublisher::$paths->home));
    if (empty($filename)) return false;
    $filename = str_replace(DIRECTORY_SEPARATOR, '/', $filename);
    $filename = '/' . ltrim($filename, '/');
    return $filename;
  }
  
  public function add($name, $filename) {
    if (!($filename = $this->normfilename($filename))) return false;
    if (!isset($this->items[$name])) {
      $this->items[$name] = array(
      'files' => array($filename),
      'texts' => array()
      );
    } else {
      if (in_array($filename, $this->items[$name]['files'])) return false;
      $this->items[$name]['files'][] = $filename;
    }
    $this->save();
    return count($this->items[$name]['files']) - 1;
  }
  
  public function deletefile($name, $filename) {
    if (!isset($this->items[$name])) return false;
    if (!($filename = $this->normfilename($filename))) return false;
    if (false === ($i = array_search($filename, $this->items[$name]['files']))) return false;
    array_delete($this->items[$name]['files'], $i);
    $this->save();
  }
  
  public function setfromstring($name, $s) {
    $this->lock();
    if (isset($this->items[$name])) {
      $this->items[$name]['files'] = array();
    } else {
      $this->items[$name] = array(
      'files' => array(),
      'texts' => array()
      );
    }
    
    $a = explode("\n", trim($s));
    foreach ($a as $filename) {
      $this->add($name, trim($filename));
    }
    $this->unlock();
  }
  
  public function addtext($name, $section, $s) {
    $s = trim($s);
    if ($s != '') $this->addsection($name, $section, tini2array::parsesection($s));
  }
  
  public function addsection($name, $section, array $items) {
    if (!isset($this->items[$name])) {
      $this->items[$name] = array(
      'files' => array(),
      'texts' => array($key => $items)
      );
    } elseif (!isset(      $this->items[$name]['texts'][$section])) {
      $this->items[$name]['texts'][$section] = $items;
    } else {
      $this->items[$name]['texts'][$section] = $items + $this->items[$name]['texts'][$section];
    }
    $this->save();
    return count($this->items[$name]['texts']) - 1;
  }
  
  public function deletetext($name, $key) {
    if (!isset($this->items[$name]['texts'][$key])) return;
    unset($this->items[$name]['texts'][$key]);
    $this->save();
    return true;
  }
  
  public function getrealfilename($filename) {
    $name = substr($filename, 0, strpos($filename, '/'));
    $dir = isset(litepublisher::$_paths[$name]) ? litepublisher::$_paths[$name] : litepublisher::$paths->home;
    return $dir . str_replace('/', DIRECTORY_SEPARATOR, $filename);
  }
  
  public function assemble() {
    $lang = getinstance('tlocal');
    $lang->ini = array();
    foreach ($this->items as $name => $items) {
      $this->parse($name);
    }
    $this->parsehtml();
  }
  
  public function parse($name) {
    $lang = getinstance('tlocal');
    if (!isset($this->items[$name])) $this->error(sprintf('The "%s" partition not found', $name));
    $ini = array();
    foreach ($this->items[$name]['files'] as $filename) {
      $realfilename = $this->getrealfilename($filename);
      if  (!file_exists($realfilename)) $this->error(sprintf('The file "%s" not found', $filename));
      if (!($parsed = parse_ini_file($realfilename, true))) $this->error(sprintf('Error parse "%s" ini file', $realfilename));
      if (count($ini) == 0) {
        $ini = $parsed;
      } else {
        foreach ($parsed as $section => $itemsini) {
          $ini[$section] = isset($ini[$section]) ? $itemsini + $ini[$section] : $itemsini;
        }
      }
    }
    
    foreach ($this->items[$name]['texts'] as $section => $itemsini) {
      $ini[$section] = isset($ini[$section]) ? $itemsini + $ini[$section] : $itemsini;
    }
    
    tfilestorage::savevar(tlocal::getcachedir() . $name , $ini);
    $lang->ini = $ini + $lang->ini;
    $lang->loaded[] = $name;
  }
  
  public function addhtml($filename) {
    if (!($filename = $this->normfilename($filename))) return false;
    if (in_array($filename, $this->html)) return false;
    $this->html[] = $filename;
    $this->save();
    return count($this->html);
  }
  
  public function deletehtml($filename) {
    if (!($filename = $this->normfilename($filename))) return false;
    if (false === ($i = array_search($filename, $this->html))) return false;
    array_delete($this->html, $i);
    $this->save();
  }
  
  public function parsehtml() {
    $html = getinstance('tadminhtml');
    $html->ini = array();
    foreach ($this->html as $filename) {
      $realfilename = $this->getrealfilename($filename);
      if  (!file_exists($realfilename)) $this->error(sprintf('The file "%s" not found', $filename));
      if (!($parsed = parse_ini_file($realfilename, true))) $this->error(sprintf('Error parse "%s" ini file', $realfilename));
      if (count($html->ini) == 0) {
        $html->ini = $parsed;
      } else {
        foreach ($parsed as $section => $itemsini) {
          $html->ini[$section] = isset($html->ini[$section]) ? $itemsini + $html->ini[$section] : $itemsini;
        }
      }
    }
    
    tfilestorage::savevar(tlocal::getcachedir() . 'adminhtml', $html->ini);
  }
  
  public function addplugin($name) {
    $language = litepublisher::$options->language;
    $dir = litepublisher::$paths->plugins . $name . DIRECTORY_SEPARATOR . 'resource' . DIRECTORY_SEPARATOR;
    $this->lock();
    if (file_exists($dir . $language . '.ini')) $this->add('default', "plugins/$name/resource/$language.ini");
    if (file_exists($dir . $language . '.admin.ini')) $this->add('admin', "plugins/$name/resource/$language.admin.ini");
    if (file_exists($dir . 'html.ini')) $this->addhtml("plugins/$name/resource/html.ini");
    $this->unlock();
  }
  
  public function deleteplugin($name) {
    $language = litepublisher::$options->language;
    $this->lock();
    $this->deletefile('default', "plugins/$name/resource/$language.ini");
    $this->deletefile('admin', "plugins/$name/resource/$language.admin.ini");
    $this->deletehtml("plugins/$name/resource/html.ini");
    $this->unlock();
  }
  
} //class