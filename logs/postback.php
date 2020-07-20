<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

if (!isset($_GET['subid'])) die();
if (!isset($_GET['status'])) die();
$subid = $_GET['subid'];
$status = $_GET['status'];

$leadfiles = glob('*leads.csv'); //бежим по всем файлам лидов в папке

foreach ($leadfiles as $lf){
	echo nl2br("Processing file: ".$lf."\n");
	$reading = fopen($lf, 'r');
	$writing = fopen($lf.'.tmp', 'w');

	$replaced = false;

	while (!feof($reading)) {
		$line = fgets($reading);
		if (stristr($line,$subid)) {
			$arr = explode(',',$line);
			//!!!далее идут настройки статусов для ПП CTR, если вам нужна другая ПП, 
			//смотрите, какие она шлёт статусы в постбэке!!!
			switch($status){
				case '0': //это два статуса, когда лид только попал в ПП, а у нас он уже записан, ничего делать не нужно
				case '1':
					break;
				case '3':
					$arr[5]='Purchase';
					break;
				case '4':
					$arr[5]='Reject';					
					break;
				case '13':						
					$arr[5]='Duplicate';			
				case '77':												
				case '88':
				case '99':
					$arr[5]='Trash';			
			}
			$line = implode(',',$arr);
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