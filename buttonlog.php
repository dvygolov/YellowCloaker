<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

include 'db.php';
$subid=$_COOKIE['subid'];
$name=$_POST['name'];
$phone=$_POST['phone'];
$is_duplicate = lead_is_duplicate($subid,$phone);
if ($is_duplicate===false){
	add_lead($subid,$name,$phone);
}
?>