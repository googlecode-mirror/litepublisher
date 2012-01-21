<?php
/**
* Lite Publisher
* Copyright (C) 2010, 2012 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tsinglepassword extends tperm {

public function getheader($obj) {
if (isset($obj->password) && ($p = $obj->password)) {
return sprintf('<?php %s::auth(\'%s\'); ?>', get_class($this), $this->encryptpassword($p));
}
}

public function encryptpassword($p) {
return md5(litepublisher::$urlmap->url . litepublisher::$secret . $p);
}

protected function getpasswordcookie() {
return basemd5('post_' . $this->id .litepublisher::$secret . $this->password);
}

public static function auth($p) {
if (litepublisher::$options->group == 'admin') return;
$cookiename = 'singlepwd_' . litepublisher::$urlmap->itemrequested['id'];
$cookie = isset($_COOKIE[$cookiename]) ? $_COOKIE[$cookiename] : '';
if ($cookie != '') {
list($login, $password) = explode('.', $cookie);
if ($password == md5($login . litepublisher::$secret . $p)) return;
}
return self::redir('type=single&backurl=' . urlencode(litepublisher::$urlmap->url));
}

public static function redir($params) {
    litepublisher::$options->savemodified();
$url = litepublisher::$site->url . '/send-post-password.php' . litepublisher::$site->q . $params;
    if ( php_sapi_name() != 'cgi-fcgi' ) {
      $protocol = $_SERVER["SERVER_PROTOCOL"];
      if ( ('HTTP/1.1' != $protocol) && ('HTTP/1.0' != $protocol) ) $protocol = 'HTTP/1.0';
      header( "$protocol 307 Temporary Redirect", true, 307);
    }
    
    header('Location: ' . $url);
    if (ob_get_level()) ob_end_flush ();
    exit();
  }
  
}//class

class tpostpassword extends tevents_itemplate implements itemplate {
  
  public static function i() {
    return getinstance(__class__);
  }
  
  protected function create() {
    parent::create();
    $this->basename = 'post.password';
  }

  private function checkspam($s) {
    if  (!($s = @base64_decode($s))) return false;
    $sign = 'megaspamer';
    if (!strbegin($s, $sign)) return false;
    $timekey = (int) substr($s, strlen($sign));
    return time() < $timekey;
  }
  
  public function request($arg) {
    if (litepublisher::$options->commentsdisabled) return 404;
    if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
      return "<?php
      @header('Allow: POST');
      @header('HTTP/1.1 405 Method Not Allowed', true, 405);
      @header('Content-Type: text/plain');
      ?>";
    }
    
    if (get_magic_quotes_gpc()) {
      foreach ($_POST as $name => $value) {
        $_POST[$name] = stripslashes($_POST[$name]);
      }
    }

    'postid' => $postid,
    'antispam' => isset($values['antispam']) ? $values['antispam'] : ''

    if (!$this->checkspam($values['antispam']))          {
      return $this->htmlhelper->geterrorcontent($lang->spamdetected);
    }
    
}

public function gettitle() {}
  
  public function getcont() {
    $this->cache = false;
    $view = tview::getview($this);
    $theme = $view->theme;
    if ($this->text != '') return $theme->simple($this->text);
    
    $lang = tlocal::i('default');
    if ($this->basename == 'forbidden') {
      return $theme->simple(sprintf('<h1>%s</h1>', $lang->forbidden));
    } else {
      return $theme->parse($theme->content->notfound);
    }
  }

public function getform(tpost $post) {
$args = new targs();
    $args->idpost = $post->id;
    $args->antispam = base64_encode('megaspamer' . strtotime ("+1 hour"));
    
    $result .= $theme->parsearg($theme->templates['content.post.passwordform'], $args);

$result = '<?php
if ($cookie != \'' . $this->getpasswordcookie() . '\') {';




return $result;
}
  
}//class  
}//class