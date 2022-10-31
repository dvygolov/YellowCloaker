<?php
require_once __DIR__.'/settings.php';
require_once __DIR__.'/htmlprocessing.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/url.php';
require_once __DIR__.'/redirect.php';
require_once __DIR__.'/abtest.php';
require_once __DIR__.'/cookies.php';

//Включение отладочной информации
ini_set('display_errors','1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

//добавляем в лог факт пробива проклы
$prelanding = get_cookie('prelanding');
$subid = get_subid();
add_lpctr($subid,$prelanding); //запись пробива проклы

$l= $_GET['l'] ?? -1;

switch ($black_land_action){
    case 'folder':
        $landing=select_item_by_index($black_land_folder_names,$l,true);
        echo load_landing($landing);
        break;
    case 'redirect':
        $fullpath = select_item_by_index($black_land_redirect_urls,$l,false);
        $fullpath = add_querystring($fullpath);
        $fullpath = replace_all_macros($fullpath);
        $fullpath = add_subs_to_link($fullpath);
        redirect($fullpath,$black_land_redirect_type,false);
        break;
}