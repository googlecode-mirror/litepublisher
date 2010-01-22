<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function TXMLRPCCommentsInstall($self) {
  $caller = TXMLRPC::instance();
  $caller->lock();

  $caller->add('litepublisher.deletecomment',		'delete', get_class($self));
  $caller->add('litepublisher.setcommentstatus',		'setstatus', get_class($self));
  $caller->add('litepublisher.addcomment',		'add', get_class($self));
  $caller->add('litepublisher.getcomment',	'getcomment', get_class($self));
  $caller->add('litepublisher.getrecentcomments',		'getrecent', get_class($self));
  $caller->add('litepublisher.moderate',		'moderate', get_class($self));
  
//wordpress api
  $caller->add('wp.getCommentCount',	'wpgetCommentCount', get_class($self));
  $caller->add('wp.newComment','wpnewComment', get_class($self));
if (dbversion) {
  $caller->add('wp.getComment', 'wpgetComment', get_class($self));
  $caller->add('wp.getComments', 'wpgetComments', get_class($self));
  $caller->add('wp.deleteComment', 'wpdeleteComment', get_class($self));
  $caller->add('wp.editComment','wpeditComment', get_class($self));
  $caller->add('wp.getCommentStatusList', '	', get_class($self));
}

  $caller->unlock();
}

?>