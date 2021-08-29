<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации
require_once '../settings.php';
require_once '../core.php';
require_once '../db.php';

$cloaker = new Cloaker($os_white,$country_white,$lang_white,$ip_black_filename,$ip_black_cidr,$tokens_black,$url_should_contain,$ua_black,$isp_black,$block_without_referer,$referer_stopwords,$block_vpnandtor);
$check_result = $cloaker->check();
//Добавляем, по какому из js-событий мы поймали бота
$reason=isset($_GET['reason'])?$_GET['reason']:'js_tests';
$cloaker->result[]=$reason;
add_white_click($cloaker->detect, $cloaker->result);
?>