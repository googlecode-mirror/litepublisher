<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

function TXMLRPCInstall(&$self) {
  $Urlmap = TUrlmap::Instance();
  $Urlmap->Lock();
  $Urlmap->Add('/rpc.xml', get_class($self), null);
  $Urlmap->Add('/xmlrpc.php', get_class($self), null);
  $Urlmap->Unlock();
  
  $self->Lock();
  
  $self->Add('demo.sayHello', 'sayHello', get_class($self));
  $self->Add('demo.addTwoNumbers', 'addTwoNumbers',  get_class($self));
  $self->Unlock();
}

function TXMLRPCUninstall(&$self) {
  TUrlmap::unsub($self);
}
?>