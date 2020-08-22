<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

include 'settings.php';

if (!isset($_GET['subid'])) die();
if (!isset($_GET['status'])) die();
$subid = $_GET['subid'];
$status = $_GET['status'];

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