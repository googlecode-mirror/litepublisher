<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tmetapost extends titem {
  
  public static function instance($id = 0) {
    return parent::iteminstance('postmeta', __class__, (int) $id);
  }
  
  public function getbasename() {
    return 'posts' . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR . 'meta';
  }
  
  protected function create() {
    $this->table = 'postsmeta';
  }
  
  public function getdbversion() {
    return dbversion;
  }
  
  public function __set($name, $value) {
    if ($name == 'id') return $this->setid($value);
    $exists = isset($this->data[$name]);
    if ($exists && ($this->data[$name] == $value)) return true;
    $this->data[$name] = $value;
    if (dbversion) {
      $name = dbquote($name);
      $value = dbquote($value);
      if ($exists) {
        $this->db->update("value = $value", "id = $this->id and name = $name");
      } else {
        $this->db->insertrow("(id, name, value) values ($this->id, $name, $value)");
      }
    } else {
      $this->save();
    }
  }
  
  //db
  public function load() {
    if ($this->dbversion)  {
      $this->LoadFromDB();
    } else {
      parent::load();
    }
    return true;
  }
  
  protected function LoadFromDB() {
    $res = $this->db->select("id = $this->id");
    while ($row = litepublisher::$db->fetchassoc($res)) {
      $this->data[$row['name']] = $row['value'];
    }
    return true;
  }
  
  protected function SaveToDB() {
    $db = $this->db;
    $db->delete("id = $this->id");
    foreach ($this->data as $name => $value) {
      if ($name == 'id') continue;
      $name = dbquote($name);
      $value = dbquote($value);
      $this->db->insertrow("(id, name, value) values ($this->id, $name, $value)");
    }
  }
  
}//class
?>