<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';
require_once __DIR__ . '/cookies.php';

//добавляем в лог факт пробива проклы
$db = new Db();
$db->add_lpctr(get_subid(), get_cookie('prelanding'), $cur_config); //запись пробива проклы

$l = $_GET['l'] ?? -1;

switch ($black_land_action) {
    case 'folder':
        $landing = select_item_by_index($black_land_folder_names, $l, true);
        echo load_landing($landing);
        break;
    case 'redirect':
        $fullpath = select_item_by_index($black_land_redirect_urls, $l, false);
        $fullpath = add_querystring($fullpath);
        $fullpath = replace_all_macros($fullpath);
        $fullpath = add_subs_to_link($fullpath);
        redirect($fullpath, $black_land_redirect_type, true,true,true);
        break;
}