<?php

function update527() {
litepublisher::$classes->items['tcommentform'][2] = dbversion ? 'comments.form.class.db.php' : 'comments.form.class.files.php';
litepublisher::$classes->items['tcommentmanager'][2] = dbversion ? 'comments.manager.class.db.php' : 'comments.manager.class.files.php';
litepublisher::$classes->add('tjsonserver', 'jsonserver.class.php');
litepublisher::$classes->add('TjsonComments', 'json.comments.class.php');
  litepublisher::$options->comments_status = 'guest';

if (dbversion) {
$db = litepublisher::$db;
$db->table = 'users';
$db->insertrow($db->assoctorow(array(
'id' =>1,
    'email' =>litepublisher::$options->email,
    'name' => litepublisher::$site->author,
    'website' => litepublisher::$site->url . '/',
    'password' => litepublisher::$options->password,
    'cookie' => litepublisher::$options->cookie,
    'expired' => sqldate(litepublisher::$options->cookieexpired ),
    'status' => 'approved',
    'idgroups' => '1',
)));

$db->table = 'usergroup';
        $db->add(array(
        'iduser' => '1',
        'idgroup' => 1
        ));

$man = tdbmanager::i();
//$man->alter($table, "add `includeparents` boolean default " . ($tags->includeparents ? 'true' : 'false'));
$man->delete_enum('users', 'status', 'lock');
$man->addenum('users', 'status', 'comuser');

$man->alter('posts', "add `comments_status` enum('closed','reg','guest','comuser') default 'comuser'");

$db->table = 'posts';
$db->update("comments_status = 'closed'", "commentsenabled = 0");
$man->alter('posts', "drop commentsenabled");

$groups = tusergroups::i();
if ($idgroup = $groups->getidgroup('subscriber')) {
$groups->items[$idgroup]['name'] = 'commentator';
$groups->save();
} else {
$idgroup = $groups->getidgroup('commentator');
}

$man->alter('comments', "add tmp int unsigned NOT NULL default '0'");

    $from = 0;
$db->table = 'comusers';
    while ($items = $db->res2assoc($db->query("select * from $db->comusers limit $from, 100"))) {
$from += count($items);
      foreach ($items as $item) {
$db->table = 'users';
if ($id = $db->findid('email = '. dbquote($item['email']))) {
$u = $db->getitem($id);
//echo $item['id'], ' ', $item['email'], ' ', $item['name'], '<br>';
//echo "found $id ", $u['email'], ' ', $u['name'], '<br>';
} else {
$id = $db->add(array(
    'email' => $item['email'],
    'name' =>$item['name'],
    'website' => $item['url'],
    'password' => '',
    'cookie' =>  $item['cookie'],
    'expired' => sqldate(),
    'idgroups' => "$idgroup",
    'trust' => $item['trust'],
    'status' => 'comuser',
));

//echo "added $id ", $item['name'], ' ', $item['email'], '<br>';
}

        $db->query("update $db->comments set tmp = '$id' where author= '" . $item['id'] . "'");

      }

$db->table = 'comusers';
    }

$man->alter('comments', "drop index author");
$man->alter('comments', "drop author");
$man->alter('comments', "change tmp author int unsigned NOT NULL default '0'");
$man->alter('comments', "add KEY `author` (`author`)");

$man->deletetable('comusers');
}
}