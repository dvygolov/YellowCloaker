<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации
require '../core.php';
include '../logging.php';

$cloacker = new Cloacker();
write_white_to_log($cloacker->detect, ['js_tests'], 1, '', '');
?>