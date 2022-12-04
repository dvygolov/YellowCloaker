<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/settings.php';

$subid = get_subid();
$name = $_POST['name'];
$phone = $_POST['phone'];
$is_duplicate = lead_is_duplicate($subid, $phone);
if ($is_duplicate === false)
    add_lead($subid, $name, $phone, $cur_config);