<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once 'db.php';
require_once 'cookies.php';
$subid=get_subid();
$name=$_POST['name'];
$phone=$_POST['phone'];
$is_duplicate = lead_is_duplicate($subid,$phone);
if ($is_duplicate===false){
	add_lead($subid,$name,$phone);
}
?>