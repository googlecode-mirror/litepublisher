<?php

function TManifestInstall() {
  $Urlmap = TUrlmap::Instance();
  $Urlmap->Lock();
  $Urlmap->Add('/wlwmanifest.xml', get_class($self), 'manifest');
  $Urlmap->Add('/rsd.xml', get_class($self), 'rsd');
  $Urlmap->Unlock();
}

function TManifestUninstall() {
  TUrlmap::unsub($self);
}

?>