<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function thomepageInstall($self) {
  litepublisher::$site->home = '/';
$menus = tmenus::instance();
$menus->lock();
$self->url = '/';
$self->title = tlocal::$data['default']['home'];
$self->view = tviews::instance()->add(tlocal::$data['names']['home']);
  $homeview = tview::instance($self->view);
  $homeview->ajax = false;
$homeview->save();


$menus->idhome = $menus->add($self);
$menus->unlock();
}

function thomepageUninstall($self) {
  turlmap::unsub($self);
$menus = tmenus::instance();
$menus->lock();
unset($menus->items[$menus->idhome]);
$menus->sort();
$menus->unlock();
}

?>