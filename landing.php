<?php
	include 'settings.php';
	include 'htmlprocessing.php';
	//Включение отладочной информации
	ini_set('display_errors','1'); 
	ini_set('display_startup_errors', 1); 
	error_reporting(E_ALL);
	//Конец включения отладочной информации
	
	
	$l=isset($_GET['l'])?$_GET['l']:-1;
	//A-B тестирование лендингов
	$landings = explode(",", $land_folder_name);
	if ($l<count($landings) && $l>=0)
		echo load_content($landings[$l],-1);
	else{
		$r = rand(0, count($landings) - 1);
		echo load_content($landings[$r],-1);
	}
?>