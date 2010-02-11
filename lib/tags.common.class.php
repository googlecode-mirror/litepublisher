<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tcommontags extends titems implements  itemplate {
  public $contents;
  public $itemsposts;
  public $PermalinkIndex;
  public $PostPropname;
  public $id;
  private $newtitle;
  
  protected function create() {
    $this->dbversion = dbversion;
    parent::create();
    if (!$this->dbversion)  $this->data['itemsposts'] = array();
    $this->data['lite'] = false;
    $this->data['sortname'] = 'count';
    $this->data['showcount'] = true;
    $this->data['maxcount'] =0;
    
    $this->PermalinkIndex = 'category';
    $this->PostPropname = 'categories';
    $this->contents = new ttagcontent($this);
    $this->itemsposts = new titemspostsowner ($this);
  }
  
  protected function getpost($id) {
    return tpost::instance($id);
  }
  
  public function getitem($id) {
    if ($this->dbversion && !isset($this->items[$id])) {
      $db = litepublisher::$db;
      if ($res = $db->query("select $this->thistable.*, $db->urlmap.url as url  from $this->thistable, $db->urlmap
      where $this->thistable.id = $id and  $db->urlmap.id  = $this->thistable.idurl limit 1")) {
        $this->items[$id] = $res->fetch(PDO::FETCH_ASSOC);
      }
    }
    if (isset($this->items[$id])) return $this->items[$id];
    return $this->error("Item $id not found in class ". get_class($this));
  }
  
  public function loaditems(array $items) {
    if (!$this->dbversion) return;
    //��������� �� �������� ����������� �����
    $items = array_diff($items, array_keys($this->items));
    if (count($items) == 0) return;
    $list = implode(',', $items);
    $table = $this->thistable;
    $db = litepublisher::$db;
    $res = $db->query("select $table.*, $db->urlmap.url as url  from $table, $db->urlmap
    where $table.id in ($list) and  $db->urlmap.id  = $table.idurl");
    $res->setFetchMode (PDO::FETCH_ASSOC);
    foreach ($res as $item) {
      $this->items[$item['id']] = $item;
    }
  }
  
  public function loadall() {
    if (!$this->dbversion)  return;
    $db = litepublisher::$db;
    $table = $this->thistable;
    $res = $db->query("select $table.*, $db->urlmap.url from $table, $db->urlmap
    where $table.idurl = $db->urlmap.id");
    $res->setFetchMode (PDO::FETCH_ASSOC);
    foreach ($res as $item) {
      $this->items[$item['id']] = $item;
    }
  }
  
  public function load() {
    if (!$this->dbversion) {
      if (parent::load()) {
        $this->itemsposts->items = &$this->data['itemsposts'];
      }
    }
  }
  
  public function save() {
    if (!$this->dbversion) parent::save();
    if (!$this->locked)  {
      twidgets::expired($this);
    }
  }
  
  public function getwidgetcontent($id, $sitebar) {
    return $this->GetSortedList($this->sortname, $this->maxcount, $sitebar);
  }
  
  private function GetSortedList($sortname, $count, $sitebar) {
    $result = '';
    $theme = ttheme::instance();
    $tml = $theme->getwidgetitem($this->basename, $sitebar);
    $args = targs::instance();
    $showcount = $this->showcount;
    $Sorted = $this->getsorted($sortname, $count);
    foreach($Sorted as $id) {
      $item = $this->getitem($id);
      $args->add($item);
      $args->icon = '';
      if ($item['icon'] != 0) {
        $files = tfiles::instance();
        $args->icon = $files->geticon($item['icon'], $item['title']);
      }
    $args->count = $showcount ? " ({$item['itemscount']})" : '';
      $result .= $theme->parsearg($tml,$args);
    }
    return $result;
  }
  
  public function geturl($id) {
    $item = $this->getitem($id);
    return $item['url'];
  }
  
  public function postedited($idpost) {
    $post = $this->getpost((int) $idpost);
    $this->lock();
  $changed = $this->itemsposts->setitems($idpost, $post->{$this->PostPropname});
    $this->updatecount($changed);
    $this->unlock();
  }
  
  public function postdeleted($idpost) {
    $this->lock();
    $changed = $this->itemsposts->deletepost($idpost);
    $this->updatecount($changed);
    $this->unlock();
  }
  
  private function updatecount(array $items) {
    if (count($items) == 0) return;
    if ($this->dbversion) {
      $db = litepublisher::$db;
      // ������� ���� ������ � ������� ������, ����� �������� ������ ����� ��������
      //��������� ������� ��������� �������� � ������� �����
      $items = implode(',', $items);
      $thistable = $this->thistable;
      $itemstable = $this->itemsposts->thistable;
      $poststable = $db->posts;
      $res = $db->query("select $itemstable.item as id, count($itemstable.item)as itemscount from $itemstable, $poststable
      where $itemstable.item in ($items)  and $itemstable.post = $poststable.id and $poststable.status = 'published'
      group by $itemstable.item");
      
      $db->table = $this->table;
      foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $db->setvalue($item['id'], 'itemscount', $item['itemscount']);
      }
    } else {
      $this->lock();
      foreach ($items as $id) {
        $this->items[$id]['itemscount'] = $this->itemsposts->getpostscount($id);
      }
      $this->unlock();
    }
  }
  
  public function add($title) {
    if (empty($title)) return false;
    $id  = $this->IndexOf('title', $title);
    if ($id > 0) return $id;
    $urlmap =turlmap::instance();
    $linkgen = tlinkgenerator::instance();
    $url = $linkgen->createurl($title, $this->PermalinkIndex, true);
    
    if ($this->dbversion)  {
      $id = $this->db->add(array('title' => $title));
      $idurl =         $urlmap->add($url, get_class($this),  $id);
      $this->db->setvalue($id, 'idurl', $idurl);
    } else {
      $id = ++$this->autoid;
      $idurl =         $urlmap->add($url, get_class($this),  $id);
    }
    
    $this->lock();
    $this->items[$id] = array(
    'id' => $id,
    'idurl' =>         $idurl,
    'url' =>$url,
    'title' => $title,
    'icon' => 0,
    'itemscount' => 0
    );
    $this->unlock();
    
    $this->added($this->autoid);
    $urlmap->clearcache();
    return $id;
  }
  
  public function edit($id, $title, $url) {
    $item = $this->getitem($id);
    if (($item['title'] == $title) && ($item['url'] == $url)) return;
    if ($item['title'] != $title)  {
      $item['title'] = $title;
      if ($this->dbversion) $this->db->setvalue($id, 'title', $title);
    }
    
    $urlmap = turlmap::instance();
    $linkgen = tlinkgenerator::instance();
    $url = trim($url);
    // ������� ������������ ��� �� ���
    if ($url == '') {
      $url = $linkgen->createurl($title, $this->PermalinkIndex, false);
    }
    
    if ($item['url'] != $url) {
      if (($urlitem = $urlmap->finditem($url)) && ($urlitem['id'] != $item['idurl'])) {
        $url = $linkgen->MakeUnique($url);
      }
      $urlmap->setidurl($item['idurl'], $url);
      $urlmap->addredir($item['url'], $url);
      $item['url'] = $url;
    }
    
    
    $this->items[$id] = $item;
    $this->save();
    $urlmap->clearcache();
  }
  
  public function delete($id) {
    $item = $this->getitem($id);
    $urlmap = turlmap::instance();
    $urlmap->deleteitem($item['idurl']);
    
    $this->lock();
    $this->contents->delete($id);
    $list = $this->itemsposts->getposts($id);
    $this->itemsposts->deleteitem($id);
    parent::delete($id);
    $this->unlock();
    $this->itemsposts->updateposts($list, $this->PostPropname);
    $urlmap->clearcache();
  }
  
  public function createnames($list) {
    if (is_string($list)) $list = explode(',', trim($list));
    $result = array();
    $this->lock();
    foreach ($list as $title) {
      $title = tcontentfilter::escape($title);
      if ($title == '') continue;
      $result[] = $this->add($title);
    }
    $this->unlock();
    return $result;
  }
  
  public function getnames(array $list) {
    $this->loaditems($list);
    $result =array();
    foreach ($list as $id) {
      if (!isset($this->items[$id])) continue;
      $result[] = $this->items[$id]['title'];
    }
    return $result;
  }
  
  public function getsorted($sortname, $count) {
    if ($sortname == 'count') $sortname = 'itemscount';
    $count = (int) $count;
    if (!in_array($sortname, array('title', 'count', 'id'))) $sortname = 'title';
    
    if ($this->dbversion) {
      $table = $this->thistable;
      $q = "select $table.*, $this->urltable.url from $table, $this->urltable
      where $table.parent = 0 and $this->urltable.id= $table.idurl order by $table.";
      $q .= $sortname == 'count' ? "itemscount asc" :"$sortname desc";
      if ($count > 0) $q .= " limit $count";
      $res = $this->db->query($q);
      $res->setFetchMode (PDO::FETCH_ASSOC);
      $result = array();
      foreach ($res as $item) {
        $id = $item['id'];
        $this->items[$id] = $item;
        $result[] = $id;
      }
      return $result;
    }
    
    $list = array();
    foreach($this->items as $id => $item) {
      $list[$id] = $item[$sortname];
    }
    if (($sortname == 'count')) {
      arsort($list);
    } else {
      asort($list);
    }
    
    if (($count > 0) && ($count < count($list))) {
      $list = array_slice($list, 0, $count, true);
    }
    
    return array_keys($list);
  }
  
  //Itemplate
  public function request($id) {
    $this->id = (int) $id;
    try {
      $item = $this->getitem((int) $id);
    } catch (Exception $e) {
      return 404;
    }
    
    $url = $item['url'];
    if(litepublisher::$urlmap->page != 1) $url = rtrim($url, '/') . "/page/$urlmap->page/";
    if (litepublisher::$urlmap->url != $url) $urlmap->redir301($url);
  }
  
  public function AfterTemplated(&$s) {
    $redir = "<?php
  \$url = '{$this->items[$this->id]['url']}';
    if(litepublisher::\$urlmap->page != 1) \$url = rtrim(\$url, '/') . \"/page/\$urlmap->page/\";
    if (litepublisher::\$urlmap->url != \$url) litepublisher::\$urlmap->redir301(\$url);
    ?>";
    $s = $redir.$s;
  }
  
  public function gettitle() {
    $item = $this->getitem($this->id);
    return $item['title'];
    // : TLocal::$data['default']['categories'];
  }
  
  public function gethead() {
    return '';
  }
  
  public function getkeywords() {
    $result = $this->contents->getvalue($this->id, 'keywords');
    if ($result == '') $result = $this->title;
    return $result;
  }
  
  public function getdescription() {
    $result = $this->contents->getvalue($this->id, 'description');
    if ($result == '') $result = $this->title;
    return $result;
  }
  
  public function GetTemplateContent() {
    $result = '';
    if ($this->id == 0) {
      $result .= $this->GetSortedList($this->sortname, 0, 0);
      return sprintf("<ul>\n%s</ul>\n", $result);
    }
    $result .= $this->contents->getcontent($this->id);
    
    $items = $this->itemsposts->getposts($this->id);
    $Posts = litepublisher::$classes->posts;
    $items = $Posts->sortbyposted($items);
    $postsperpage = $this->lite ? 1000 : litepublisher::$options->postsperpage;
    $list = array_slice($items, (litepublisher::$urlmap->page - 1) * $postsperpage, $postsperpage);
    $theme = ttheme::instance();
    $result .= $theme->getposts($list, $this->lite);
    $item = $this->getitem($this->id);
    $result .=$theme->getpages($item['url'], litepublisher::$urlmap->page, ceil(count($items)/ $postsperpage));
    return $result;
  }
  
  public function SetParams($lite, $sortname, $showcount, $maxcount) {
    if (($lite != $this->lite) || ($sortname != $this->sortname) || ($showcount != $this->showcount) || ($maxcount != $this->maxcount)) {
      $this->lite = $lite;
      $this->sortname = $sortname;
      $this->showcount = $showcount;
      $this->maxcount = $maxcount;
      $this->save();
    }
  }
  
}//class

class ttagcontent extends tdata {
  private $owner;
  private $items;
  
  public function __construct(TCommonTags $owner) {
    parent::__construct();
    $this->owner = $owner;
    $this->items = array();
  }
  
  private function getfilename($id) {
    return litepublisher::$paths->data . $this->owner->basename . DIRECTORY_SEPARATOR . $id . '.php';
  }
  
  public function getitem($id) {
    //if (isset($this->items[$id]))  return $this->items[$id];
    $item = array(
    'description' => '',
    'keywords' => '',
    'content' => '',
    'rawcontent' => ''
    );
    
    if ($this->owner->dbversion) {
      if ($r = $this->db->getitem($id)) $item = $r;
    } else {
      tfiler::unserialize($this->getfilename($id), $item);
    }
    $this->items[$id] = $item;
    return $item;
  }
  
  public function setitem($id, $item) {
    if (isset($this->items[$id]) && ($this->items[$id] == $item)) return;
    $this->items[$id] = $item;
    if ($this->owner->dbversion) {
      $item['id'] = $id;
      $this->db->insert($item);
    } else {
      tfiler::serialize($this->getfilename($id), $item);
    }
  }
  
  public function edit($id, $content, $description, $keywords) {
    $filter = tcontentfilter::instance();
    $item =array(
    'content' => $filter->filter($content),
    'rawcontent' => $content,
    'description' => $description,
    'keywords' => $keywords
    );
    $this->setitem($id, $item);
  }
  
  public function delete($id) {
    if ($this->owner->dbversion) {
      $this->db->iddelete($id);
    } else {
      @unlink($this->getfilename($id));
    }
  }
  
  public function getvalue($id, $name) {
    $item = $this->getitem($id);
    return $item[$name];
  }
  
  public function getcontent($id) {
    return $this->getvalue($id, 'content');
  }
  
  public function setcontent($id, $content) {
    $item = $this->getitem($id);
    $filter = tcontentfilter::instance();
    $item['rawcontent'] = $content;
    $item['content'] = $filter->GetPostContent($content);
    $item['description'] = tcontentfilter::getexcerpt($content, 80);
    $this->setitem($id, $item);
  }
  
  public function getdescription($id) {
    return $this->getvalue($id, 'description');
  }
  
  public function getkeywords($id) {
    return $this->getvalue($id, 'keywords');
  }
  
}//class


?>