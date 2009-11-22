<?php
/**
 * Lite Publisher 
 * Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
 * Dual licensed under the MIT (mit.txt) 
 * and GPL (gpl.txt) licenses.
**/

function tsubscribersInstall($self) {
global $classes;
if (dbversion) {
    $dbmanager = TDBManager ::instance();
    $dbmanager->CreateTable($self->table, file_get_contents(dirname(__file__) . DIRECTORY_SEPARATOR . 'items.posts.sql'));
}

  $self->fromemail = 'litepublisher@' . $_SERVER['HTTP_HOST'];
  $self->save();

$posts = tposts::instance();
$posts->deleted = $self->deletepost;

$comusers = tcomusers::instance();
$comusers->deleted = $self->deleteitem;

  $manager = $classes->commentmanager;
  $manager->lock();
  $manager->added = $self->sendmail;
  $manager->approved = $self->sendmail;
  $manager->unlock();
}

function tsubscribersUninstall(&$self) {
global $classes;
$classes->commentmanager->unsubscribeclass($self);
$classes->comusers->unsubscribeclass($self);
$classes->posts->unsubscribeclass($self);
}

?>