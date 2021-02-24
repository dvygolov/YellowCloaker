<?php
require_once 'settings.php';
require_once 'htmlprocessing.php';
require_once 'db.php';
require_once 'redirect.php';

//Включение отладочной информации
ini_set('display_errors','1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

//добавляем в лог факт пробива проклы

$prelanding = isset($_COOKIE['prelanding'])?$_COOKIE['prelanding']:'';
$subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';
add_lpctr($subid,$prelanding); //запись пробива проклы

$l=isset($_GET['l'])?$_GET['l']:-1;
$landings=[];
if ($black_land_action=='redirect')
    $landings = $black_land_redirect_urls;
else if ($black_land_action=='folder')
    $landings = $black_land_folder_names;

switch ($black_land_action){
    case 'folder':
        //A-B тестирование лендингов
        if ($l<count($landings) && $l>=0)
            echo load_landing($landings[$l]);
        else{
            $r = rand(0, count($landings) - 1);
            echo load_landing($landings[$r]);
        }
        break;
    case 'redirect':
        $fullpath='';
        if ($l<count($landings) && $l>=0)
            $fullpath = $landings[$l];
        else{
            $r = rand(0, count($landings) - 1);
            $fullpath=$landings[$r];
        }
        $querystr = $_SERVER['QUERY_STRING'];
        if (!empty($querystr)) {
            $fullpath = $fullpath.'?'.$querystr;
        }
        $fullpath = replace_all_macros($fullpath);
        $fullpath = add_subs_to_link($fullpath);
        redirect($fullpath);
        break;
}
?>