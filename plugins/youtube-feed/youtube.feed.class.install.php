<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function tyoutubefeedInstall($self) {
  $about = tplugins::getabout(tplugins::getname(__file__));
  $admin = tadminmenus::instance();
  $idfiles = $admin->url2id('/admin/files/');
  $admin->createitem($idfiles, 'youtube', 'author', 'tadminfiles');
  
  $parser = tthemeparser::instance();
  $parser->parsed = $self->themeparsed;
  ttheme::clearcache();
  
  if (dbversion) {
    $man = tdbmanager::instance();
    $man->alter('files', "modify `media` enum('bin','image','icon','audio','video','document','executable','text','archive', 'youtube') default 'bin'");
  }
}

function tyoutubefeedUninstall($self) {
  $admin = tadminmenus::instance();
  $admin->deleteurl('/admin/files/youtube/');
  
  $parser = tthemeparser::instance();
  $parser->unsubscribeclass($self);
}
?>