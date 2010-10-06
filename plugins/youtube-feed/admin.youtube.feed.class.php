<?php
/**
* Lite Publisher
* Copyright (C) 2010 Vladimir Yushko http://litepublisher.com/
* Dual licensed under the MIT (mit.txt)
* and GPL (gpl.txt) licenses.
**/

class tadminyoutubefeed {
  
  public static function instance() {
    return getinstance(__class__);
  }
  
  public function getcontent() {
    $feed = tyoutubefeed::instance();
    $about = tplugins::getabout(tplugins::getname(__file__));
    $args = targs::instance();
    $html = THtmlResource::instance();
    if (!isset($_POST['step'])) $_POST['step'] = 1;
    switch ($_POST['step']) {
      case 2:
      case 3:
      $files = tfiles::instance();
      $args->step = 3;
      $args->formtitle = $about['feeditems'];
      $tml = '<tr>
      <td align="center"><input type="checkbox" name="youtubeid-$id" id="youtubeid-$id" value="$id" $checked /></td>
      <td align="left"><a href="http://www.youtube.com/watch?v=$id" target="top">$title</a></td>
      </tr>';
      $items = '';
      foreach ($feed->items as $id => $item) {
        $args->add($item);
        $args->id = $id;
        $args->checked = $files->exists($id) ? false : true;
        $items .= $html->parsearg($tml, $args);
      }
      $args->items = $items;
      
      $table = '<table>
      <thead>
      <tr>
      <th align="center"><input type="checkbox" name="invertcheckbox" onclick="invertcheck(this);" /></th>
      <th align="left">Video</th>
      </tr>
      </thead>
      <tbody>
      $items
      </tbody >
      </table>';
      
      $args->items = $html->parsearg($table, $args);
      $result = $html->checkallscript;
      $result .= $html->adminform('$items [hidden:step]', $args);
      return $result;
      
      default:
      $args->step = 2;
      $args->formtitle = $about['feedtitle'];
      $args->data['$lang.url'] = $about['feedurl'];
      $args->url = $feed->url;
      $result = $html->adminform('[text:url] [hidden:step]', $args);
      
      $args->step = 'options';
      $args->formtitle = $about['options'];
      $args->data['$lang.player'] = $about['player'];
      $args->player = $feed->player;
      $result .= $html->adminform('[editor:player] [hidden:step]', $args);
      return $result;
    }
  }
  
  public function processform() {
    $feed = tyoutubefeed::instance();
    switch ($_POST['step']) {
      case 'options':
      $feed->player = $_POST['player'];
      $feed->save();
      ttheme::clearcache();
      break;
      
      case 2:
      $feed->items = array();
      $feed->url = trim($_POST['url']);
      if ($s = http::get($feed->url))  $feed->items = $feed->feedtoitems($s);
      $feed->save();
      break;
      
      case 3:
      $files = tfiles::instance();
      $files->lock();
      foreach ($_POST as $k => $v) {
        if (strbegin($k, 'youtubeid-') && isset($feed->items[$v]) && !$files->exists($v)) {
          $feed->addtofiles($feed->items[$v]);
        }
      }
      $files->unlock();
      break;
    }
  }
  
}//class
?>