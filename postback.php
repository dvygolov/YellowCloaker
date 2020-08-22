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

if (){
	write_leads_to_log($subid,$name,$phone,$status);
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
			//!!!далее идут настройки статусов для ПП Adcombo, если вам нужна другая ПП, 
			//смотрите, какие она шлёт статусы в постбэке!!!
			switch($status){
				case 'Lead': //это два статуса, когда лид только попал в ПП, а у нас он уже записан, ничего делать не нужно
				case 'Hold':
					break;
				case 'Purchase':
					$arr[5]='Purchase';
					break;
				case 'Reject':
					$arr[5]='Reject';					
					break;
				case 'Trash':												
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