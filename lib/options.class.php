<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class toptions extends tevents_storage {
  public $group;
  public $idgroups;
  protected $_user;
  protected $_admincookie;
  public $gmt;
  public $errorlog;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'options';
    $this->addevents('changed', 'perpagechanged', 'onsave');
    unset($this->cache);
    $this->gmt = 0;
    $this->errorlog = '';
    $this->group = '';
    $this->idgroups = array();
  }
  
  public function afterload() {
    parent::afterload();
    date_default_timezone_set($this->timezone);
    $this->gmt = date('Z');
    if (!defined('dbversion')) {
      define('dbversion', isset($this->data['dbconfig']));
    }
  }
  
  public function savemodified() {
    $result = tstorage::savemodified();
    $this->onsave($result);
    return $result;
  }
  
  public function __set($name, $value) {
    if (in_array($name, $this->eventnames)) {
      $this->addevent($name, $value['class'], $value['func']);
      return true;
    }
    
    if (method_exists($this, $set = 'set' . $name)) {
      $this->$set($value);
      return true;
    }
    
    if (!array_key_exists($name, $this->data)  || ($this->data[$name] != $value)) {
      $this->data[$name] = $value;
      $this->save();
      $this->dochanged($name, $value);
    }
    return true;
  }
  
  private function dochanged($name, $value) {
    if ($name == 'perpage') {
      $this->perpagechanged();
      $urlmap = turlmap::i();
      $urlmap->clearcache();
    } elseif ($name == 'cache') {
      $urlmap = turlmap::i();
      $urlmap->clearcache();
    } else {
      $this->changed($name, $value);
    }
  }
  
  public function delete($name) {
    if (array_key_exists($name, $this->data)) {
      unset($this->data[$name]);
      $this->save();
    }
  }
  
  public function getadmincookie() {
    if (is_null($this->_admincookie)) {
      $this->_admincookie = $this->cookieenabled && isset($_COOKIE['litepubl_user_flag']) ? $this->user && in_array(1, $this->idgroups) : false;
    }
    return $this->_admincookie;
  }
  
  public function setadmincookie($val) {
    $this->_admincookie = $val;
  }
  
  public function getuser() {
    if (is_null($this->_user)) {
      $this->_user = $this->cookieenabled ? $this->authcookie() : false;
    }
    return $this->_user;
  }
  
  public function setuser($id) {
    $this->_user = $id;
  }
  
  public function authcookie() {
    $cookie = isset($_COOKIE['litepubl_user']) ? (string) $_COOKIE['litepubl_user'] : (isset($_COOKIE['admin']) ? (string) $_COOKIE['admin'] : '');
    if ($cookie == '') return false;
    $cookie = basemd5($cookie . litepublisher::$secret);
    if (    $cookie == basemd5( litepublisher::$secret)) return false;
    if (!empty($this->cookie ) && ($this->cookie == $cookie)) {
      if ($this->cookieexpired < time()) return false;
      $this->_user = 1;
    } elseif (!$this->usersenabled)  {
      return false;
    } else {
      $users = tusers::i();
      if ($iduser = $users->findcookie($cookie)){
        $item = $users->getitem($iduser);
        if (strtotime($item['expired']) <= time()) return false;
        $this->_user = (int) $iduser;
      } else {
        return false;
      }
    }
    
    $this->updategroup();
    return $this->_user;
  }
  
  public function auth($email, $password) {
    if ($email == '' && $password == '' && $this->cookieenabled) return $this->authcookie();
    if ($email == $this->email) {
      if ($this->data['password'] != basemd5("$email:$this->realm:$password"))  return false;
      $this->_user = 1;
    } elseif(!$this->usersenabled) {
      return false;
    } else {
      $users = tusers::i();
      if (!($this->_user = $users->auth($email, $password))) return false;
    }
    $this->updategroup();
    return true;
  }
  
  public function updategroup() {
    if ($this->_user == 1) {
      $this->group = 'admin';
      $this->idgroups = array(1);
    } else {
      $user = tusers::i()->getitem($this->_user);
      $this->idgroups = $user['idgroups'];
      $this->group = tusergroups::i()->items[$user['idgroups'][0]]['name'];
    }
  }
  
  public function can_edit($idauthor) {
    return ($idauthor == $this->user) || ($this->group == 'admin') || ($this->group == 'editor');
  }
  
  public function getpassword() {
    if ($this->user <= 1) return $this->data['password'];
    $users = tusers::i();
    return $users->getvalue($this->user, 'password');
  }
  
  public function changepassword($newpassword) {
    $this->data['password'] = basemd5("$this->email:$this->realm:$newpassword");
    $this->save();
  }
  
  public function setdbpassword($password) {
    $this->data['dbconfig']['password'] = base64_encode(str_rot13 ($password));
    $this->save();
  }
  
  public function logout() {
    if ($this->cookieenabled) {
      $this->setcookies('', 0);
    } else {
      tauthdigest::i()->logout();
    }
  }
  
  public function setcookies($cookie, $expired) {
    setcookie('litepubl_user', $cookie, $expired, litepublisher::$site->subdir . '/', false);
    if ('admin' == $this->group) setcookie('litepubl_user_flag', $cookie ? 'true' : '', $expired, litepublisher::$site->subdir . '/', false);
    if ($this->_user == 1) {
      $this->set_cookie($cookie);
      $this->cookieexpired = $expired;
    } else {
      tusers::i()->setcookie($this->_user, $cookie, $expired);
    }
  }
  
  public function Getinstalled() {
    return isset($this->data['email']);
  }
  
  public function settimezone($value) {
    if(!isset($this->data['timezone']) || ($this->timezone != $value)) {
      $this->data['timezone'] = $value;
      $this->save();
      date_default_timezone_set($this->timezone);
      $this->gmt = date('Z');
    }
  }
  
  public function set_cookie($cookie) {
    if ($cookie != '') $cookie = basemd5((string) $cookie . litepublisher::$secret);
    $this->data['cookie'] = $cookie;
    $this->save();
  }

public function ingroup($groupname) {
//admin has all rights
if ($this->user == 1) return true;
if (in_array(1, $this->idgroups)) return true;
return tusergroups::i()->ingroup($this->user, $groupname);
}


  
  public function getcommentsapproved() {
    return $this->DefaultCommentStatus  == 'approved';
  }
  
  public function setcommentsapproved($value) {
    $this->DefaultCommentStatus  = $value ? 'approved' : 'hold';
  }
  
  public function handexception($e) {
    /*
    echo "<pre>\n";
    $debug = debug_backtrace();
    foreach ($debug as $error) {
      echo $error['function'] ;
      echo "\n";
    }
    //array_shift($debug);
    echo "</pre>\n";
    */
    $trace =str_replace(litepublisher::$paths->home, '', $e->getTraceAsString());
    
    $message = "Caught exception:\n" . $e->getMessage();
    $log = $message . "\n" . $trace;
    $this->errorlog .= str_replace("\n", "<br />\n", htmlspecialchars($log));
    tfiler::log($log, 'exceptions.log');
    if (!(litepublisher::$debug || $this->echoexception || $this->admincookie || litepublisher::$urlmap->adminpanel)) {
      tfiler::log($log, 'exceptionsmail.log');
    }
  }
  
  public function trace($msg) {
    try {
      throw new Exception($msg);
    } catch (Exception $e) {
      $this->handexception($e);
    }
  }
  
  public function showerrors() {
    if (!empty($this->errorlog) && (litepublisher::$debug || $this->echoexception || $this->admincookie || litepublisher::$urlmap->adminpanel)) {
      echo $this->errorlog;
    }
  }
  
}//class