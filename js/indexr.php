<?php
//Этот файл необходимо подключить к любому конструктору, используя
//следующий код: <script src="https://ваш.домен/js/indexr.php"></script>
//в случае прохождения пользователем проверки, будет совершен редирект на https://ваш.домен

// Включаем доступ к скрипту сторонним сайтам
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/javascript');

//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

require '../core.php';
include '../settings.php';

//передаём все параметры в кло
$cloaker = new Cloaker($os_white,$country_white,$ip_black_filename,$ip_black_cidr,$tokens_black,$url_should_contain,$ua_black,$isp_black,$block_without_referer,$block_vpnandtor);

//Проверяем зашедшего пользователя
$check_result = $cloaker->check();

if ($check_result == 0 || $tds_mode==='off') //Обычный юзверь или отключена фильтрация
{
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
		$url = "https://";   
	else  
		$url = "http://";   
	$url.= $_SERVER['HTTP_HOST'];  

	echo "window.location='{$url}'+window.location.search;";
} 
else
	echo 'console.log("OK");';