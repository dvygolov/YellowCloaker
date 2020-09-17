<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

include 'settings.php';

$subid= isset($_GET['subid'])?$_GET['subid']:(isset($_POST['subid'])?$_POST['subid']:'');
$status= isset($_GET['status'])?$_GET['status']:(isset($_POST['status'])?$_POST['status']:'');

//создаёт папку для логов, если её нет
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


$leadfiles = glob('logs/*leads.csv'); //бежим по всем файлам лидов в папке

foreach ($leadfiles as $lf){
	echo nl2br("Processing file: ".$lf."\n");
	$reading = fopen($lf, 'r');
	$writing = fopen($lf.'.tmp', 'w');

	$replaced = false;

	while (!feof($reading)) {
		$line = fgets($reading);
		if (stristr($line,$subid)) {
			$arr = explode(',',$line);
			switch($status){
				case $lead_status_name: 
					break;
				case $purchase_status_name:
					$arr[5]='Purchase';
					break;
				case $reject_status_name:
					$arr[5]='Reject';					
					break;
				case $trash_status_name:												
					$arr[5]='Trash';			
			}
			$line = implode(',',$arr);
			$line = $line."\n";
			$replaced = true;
		}
		fputs($writing, $line);
	}
	fclose($reading); 
	fclose($writing);
	
	if ($replaced){
	  rename($lf.'.tmp', $lf);
	  echo nl2br("OK, record found!\n");
	  break; //как только где-то что-то заменили - выходим
	} else {
	  echo nl2br("Nothing found!\n");
	  unlink($lf.'.tmp');
	}
}
?>