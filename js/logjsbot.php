<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации
include '../settings.php';
require '../core.php';
include '../db.php';

$cloaker = new Cloaker($os_white,$country_white,$ip_black_filename,$ip_black_cidr,$tokens_black,$url_should_contain,$ua_black,$isp_black,$block_without_referer,$block_vpnandtor);
add_white_click($cloaker->detect, ['js_tests']);
?>