<?php
	include 'settings.php';
	include 'htmlprocessing.php';
	include 'logging.php';
	//Включение отладочной информации
	ini_set('display_errors','1'); 
	ini_set('display_startup_errors', 1); 
	error_reporting(E_ALL);
	//Конец включения отладочной информации
	
	//добавляем в лог факт пробива проклы
	$prelanding = isset($_COOKIE['prelanding'])?$_COOKIE['prelanding']:'';
	$subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';
	write_lpctr_to_log($subid,$prelanding);
	
	$l=isset($_GET['l'])?$_GET['l']:-1;
	if ($black_land_use_url) //если используем внешний ленд (или несколько через запятую)
	{ 
		$redirects = explode(",", $black_land_url);
		$fullpath='';
		if ($l<count($redirects) && $l>=0)
			$fullpath = $redirects[$l];
		else{
			$r = rand(0, count($redirects) - 1);
			$fullpath=$redirects[$r];
		}
		$querystr = $_SERVER['QUERY_STRING'];
		if (!empty($querystr)) {
			$fullpath = $fullpath.'?'.$querystr;
		}
		$fullpath = replace_all_macros($fullpath);
		$fullpath = add_subs_to_link($fullpath);
		header('Location: '.$fullpath);
	}
	else //если используем локальный ленд
	{ 
		$l=isset($_GET['l'])?$_GET['l']:-1;
		//A-B тестирование лендингов
		$landings = explode(",", $black_land_folder_name);
		if ($l<count($landings) && $l>=0)
			echo load_landing($landings[$l]);
		else{
			$r = rand(0, count($landings) - 1);
			echo load_landing($landings[$r]);
		}
	}
?>