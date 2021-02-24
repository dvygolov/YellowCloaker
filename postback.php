<?php
//Включение отладочной информации
ini_set('display_errors','1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once 'settings.php';
require_once 'db.php';

$subid= isset($_GET['subid'])?$_GET['subid']:(isset($_POST['subid'])?$_POST['subid']:'');
$status= isset($_GET['status'])?$_GET['status']:(isset($_POST['status'])?$_POST['status']:'');

//создаёт папку для лога постбэков, если её нет
if (!file_exists(__DIR__."/pblogs")) mkdir(__DIR__."/pblogs");
$file = __DIR__."/pblogs/".date("d.m.y").".pb.log";
$save_order = fopen($file, 'a+');
if ($subid==='' || $status==='') {
	fwrite($save_order, date("Y-m-d H:i:s")." Error! No subid or status!\n");
	fflush($save_order);
	fclose($save_order);
	die();
}
else{
	fwrite($save_order, date("Y-m-d H:i:s")." $subid, $status\n");
	fflush($save_order);
	fclose($save_order);
}


$dataDir = __DIR__ . "/logs";
$leadsStore = new \SleekDB\Store("leads", $dataDir);
$lead=$leadsStore->findOneBy([["subid","=",$subid]]);

switch($status){
    case $lead_status_name:
        break;
    case $purchase_status_name:
        $lead["status"]='Purchase';
        break;
    case $reject_status_name:
        $lead["status"]='Reject';
        break;
    case $trash_status_name:
        $lead["status"]='Trash';
        break;
}
$leadsStore->update($lead);
?>