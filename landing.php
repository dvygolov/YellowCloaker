<?php
require_once 'settings.php';
require_once 'htmlprocessing.php';
require_once 'db.php';
require_once 'url.php';
require_once 'redirect.php';
require_once 'abtest.php';
require_once 'cookies.php';

//Включение отладочной информации
ini_set('display_errors','1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

//добавляем в лог факт пробива проклы

$prelanding = get_cookie('prelanding');
$subid = get_subid();
add_lpctr($subid,$prelanding); //запись пробива проклы

$l=isset($_GET['l'])?$_GET['l']:-1;

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
?>