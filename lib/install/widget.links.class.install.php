<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2011 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function tlinkswidgetInstall($self) {
  tlocal::usefile('admin');
  $lang = tlocal::instance('installation');
  $self->add($lang->homeurl, $lang->homedescription, $lang->homename);
  
  $urlmap = turlmap::instance();
  $urlmap->add($self->redirlink, get_class($self), null, 'get');
  
  $robots = trobotstxt ::instance();
  $robots->AddDisallow($self->redirlink);
  $robots->save();
}

function tlinkswidgetUninstall($self) {
  turlmap::unsub($self);
}
