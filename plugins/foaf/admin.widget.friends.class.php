<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminfriendswidget extends tadminwidget {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  protected function dogetcontent(twidget $widget, targs $args){
    $args->maxcount = $widget->maxcount;
    $args->redir = $widget->redir;
    return $this->html->friendsform($args);
  }
  
  protected function doprocessform(twidget $widget)  {
    $widget->maxcount = (int) $_POST['maxcount'];
    $widget->redir = isset($_POST['redir']);
  }
  
}//class

?>