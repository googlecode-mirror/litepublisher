<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcommentform extends tevents {
  public $helper;
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename ='commentform';
    $this->cache = false;
  }
  
  public function request($arg) {
    if (litepublisher::$options->commentsdisabled) return 404;
    if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
      return "<?php
      @header('HTTP/1.1 405 Method Not Allowed', true, 405);
      @header('Allow: POST');
      @header('Content-Type: text/plain');
      ?>";
    }
    
    tguard::post();
    if (isset($_POST['confirmid'])) return $this->confirm_recevied();
return $this->processform($_POST, false);
}

public function getshortpost($id) {
$id = (int) $id;
if ($id == 0) return false;
$db = litepublisher::$db;
return $db->selectassoc("select id, idurl, idperm, status, comments_status, commentscount from $db->posts where id = $id");
}

public function invalidate(array $shortpost) {
    $lang = tlocal::i('comment');
      if(!$shortpost) {
        return $this->geterrorcontent($lang->postnotfound);
      }
      
      if ($shortpost['status'] != 'published')  {
        return $this->geterrorcontent($lang->commentondraft);
      }

if ($shortpost['comments_status'] == 'closed') {
        return $this->geterrorcontent($lang->commentsdisabled);
}

return false;
}

public function processform(array $values, $confirmed) {
    $lang = tlocal::i('comment');
    if (trim($values['content']) == '') return $this->geterrorcontent($lang->emptycontent);
    if (!$this->checkspam(isset($values['antispam']) ? $values['antispam'] : ''))          {
      return $this->geterrorcontent($lang->spamdetected);
    }

      $shortpost= $this->getshortpost(isset($values['postid']) ? (int) $values['postid'] : 0);
if ($err = $this->invalidate($shortpost)) return $err;
if (intval($shortpost['idperm'])) > 0) {
$post = tpost::i((int) $shortpost['id']);
        $perm = tperm::i($post->idperm);
if (!$perm->hasperm($post)) return 403; 
}

    $cm = tcommentmanager::i();      
    if (litepublisher::$options->checkduplicate && $cm->checkduplicate($shortpost['id'], $values['content']) ) {
        return $this->geterrorcontent($lang->duplicate);
      }

      if (!$confirmed) $values['ip'] = preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR']);
if (litepublisher::$options->ingroups($cm->idgroups)) {
if (!$confirmed && $cm->confirmlogged)  return $this->request_confirm($values, $shortpost);
$iduser = litepublisher::$options->user;
} else {
switch ($shortpost['comments_status']) {
case 'reg':
        return $this->geterrorcontent($lang->reg);

case 'guest':
if (!$confirmed && $cm->confirmguest)  return $this->request_confirm($values, $shortpost);
$iduser = $cm->idguest;
break;

case 'comuser':
if (!$confirmed && $cm->confirmcomuser)  return $this->request_confirm($values, $shortpost);
if ($err = $this->processcomuser($values)) return $err;
$iduser = $this->addcomuser($values);
break;
}

$cm->add($shortpost['id'], $iduser, $values['content'], $values['ip']);

//$post->lastcommenturl;      
$shortpost['commentscount']++;
    if (!litepublisher::$options->commentpages || ($shortpost['commentscount'] <= litepublisher::$options->commentsperpage)) {
$c = 1;
} else {
    $c = ceil($shortpost['commentscount'] / litepublisher::$options->commentsperpage);
}

    $url = litepublisher::$urlmap->getvalue($shortpost['idurl'], 'url');
    if (($c > 1) && !litepublisher::$options->comments_invert_order) $url = rtrim($url, '/') . "/page/$c/";

    return $this->sendcookies($cookies, litepublisher::$site->url . $posturl);
}

public function confirm_recevied() {
    $lang = tlocal::i('comment');
/*
    $kept = tkeptcomments::i();
    $kept->deleteold();
*/
    $confirmid = $_POST['confirmid'];
tsession::start($confirmid);
    //if (!($values = $kept->getitem($confirmid))) {
if (!isset($_SESSION['confirmid'] || ($confirmid != $_SESSION['confirmid'])) {
          session_destroy();
      return $this->geterrorcontent($lang->notfound);
    }
$values = $_SESSION['values'];
          session_destroy();
return $this->processform($values, true);
}

public function request_confirm(array $values, array $shortpost) {
/*
    $kept = tkeptcomments::i();
    $kept->deleteold();
*/
      $values['date'] = time();
      $values['ip'] = preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR']);
    
      //$confirmid  = $kept->add($values);
$confirmid = md5uniq();
if ($sess = tsession::start($confirmid)) $sess->lifetime = 900;
$_SESSION['confirmid'] = $confirmid;
$_SESSION['values'] = $values;
    session_write_close();

if (intval($shortpost['idperm']) > 0) {
$header = $this->getpermheader($shortpost);
} else {
$header = '';
}

      return $header . $this->confirm($confirmid);
}

public function getpermheader(array $shortpost) {
$urlmap = litepublisher::$urlmap;
        $url = $urlmap->url;
        $saveitem = $urlmap->itemrequested;
        $urlmap->itemrequested = $urlmap->getitem($shortpost['idurl']);
        $urlmap->url = $urlmap->itemrequested['url'];
$post = tpost::i((int) $shortpost['id']);
        $perm = tperm::i($post->idperm);
        // not restore values because perm will be used this values
return $perm->getheader($post);
    }

  private function getconfirmform($confirmid) {
    ttheme::$vars['lang'] = tlocal::i('comment');
    $args = targs::i();
    $args->confirmid = $confirmid;
    $theme = tsimplecontent::gettheme();
    return $theme->parsearg(
    $theme->templates['content.post.templatecomments.confirmform'], $args);
  }
  
  //htmlhelper
  public function confirm($confirmid) {
if (isset($this->helper) && ($this != $this->helper)) return $this->helper->confirm($confirmid);
    return tsimplecontent::html($this->getconfirmform($confirmid));
  }
  
  public function geterrorcontent($s) {
if (isset($this->helper) && ($this != $this->helper)) return $this->helper->geterrorcontent($s);
    return tsimplecontent::content($s);
  }

  private function checkspam($s) {
    if  (!($s = @base64_decode($s))) return false;
    $sign = 'superspamer';
    if (!strbegin($s, $sign)) return false;
    $TimeKey = (int) substr($s, strlen($sign));
    return time() < $TimeKey;
  }

public function processcomuser(array &$values) {
        $lang = tlocal::i('comment');
    if (empty($values['name']))       return $this->geterrorcontent($lang->emptyname);
    $values['name'] = tcontentfilter::escape($values['name']);
    $values['email'] = isset($values['email']) ? trim($values['email']) : '';
    if (!tcontentfilter::ValidateEmail($values['email'])) {
      return $this->geterrorcontent($lang->invalidemail);
    }

    $values['url'] = isset($values['url']) ? tcontentfilter::escape(tcontentfilter::clean_website($values['url'])) : '';
    $subscribe = isset($values['subscribe']);

$cm = tcommentmanager::i();
    $users = tusers::i();
if ($id =$users->emailexists($values['email'])) {
$status = $users->getvalue($id, 'status');
if ($status != 'comuser') {
      return $this->geterrorcontent($lang->emailregistered);
}
} else {
    $uid = $users->add($values['name'], $values['email'], $values['url'], $values['ip']);
}

    if (!$cm->canadd( $uid)) {
      return $this->geterrorcontent($lang->toomany);
    }
    
    $subscribers = tsubscribers::i();
    $subscribers->update($post->id, $uid, $values['subscribe']);
    

    
    $cookies = array();
    $cookie = empty($_COOKIE['userid']) ? '' : $_COOKIE['userid'];
    $usercookie = $users->getcookie($uid);
    if ($usercookie != basemd5($cookie . litepublisher::$secret)) {
      $cookie= md5uniq();
      $usercookie = basemd5($cookie . litepublisher::$secret);
      $users->setvalue($uid, 'cookie', $usercookie);
    }
    $cookies['userid'] = $cookie;
    
    foreach (array('name', 'email', 'url') as $field) {
      $cookies["comuser_$field"] = $values[$field];
    }
    
    
    return $this->sendcookies($cookies, litepublisher::$site->url . $posturl);
  }

  public function sendcookies($cookies, $url) {
if (isset($this->helper) && ($this != $this->helper)) return $this->helper->sendcookies($cookies, $url);
    $result = '<?php ';
    foreach ($cookies as $name => $value) {
      $result .= " setcookie('$name', '$value', time() + 30000000,  '/', false);";
    }
    
    $result .= sprintf(" header('Location: %s'); ?>", $url);
    return $result;
  }
  
}//class