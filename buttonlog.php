<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

include 'logging.php';
$subid=$_COOKIE['subid'];
$name=$_POST['name'];
$phone=$_POST['phone'];
$is_duplicate = lead_is_duplicate($subid,$phone);
if (!$is_duplicate){
	write_leads_to_log($subid,$name,$phone,'');
}
?>