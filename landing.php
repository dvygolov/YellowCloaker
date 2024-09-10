<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';
require_once __DIR__ . '/cookies.php';

global $c;
//adding the fact that user reached landing to the database
$db = new Db();
$db->add_lpctr(get_cookie('subid'));

$l = $_GET['l'] ?? -1;

switch ($c->black->land->action) {
    case 'folder':
        $landing = select_item_by_index($c->black->land->folderNames, $l, true);
        echo load_landing($landing);
        break;
    case 'redirect':
        $fullpath = select_item_by_index($c->black->land->redirectUrls, $l, false);
        redirect($fullpath, $$c->black->land->redirectType, true);
        break;
}