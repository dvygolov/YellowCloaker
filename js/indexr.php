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
$cloacker = new Cloacker();
$cloacker->os_white = $os_white; 
$cloacker->country_white = $country_white;
$cloacker->ip_black = $ip_black; 
$cloacker->tokens_black = $tokens_black;
$cloacker->ua_black = $ua_black;
$cloacker->block_without_referer = $block_without_referer;
$cloacker->isp_black = $isp_black;

//Проверяем зашедшего пользователя
$check_result = $cloacker->check();

if ($check_result == 0 || $disable_tds) //Обычный юзверь или отключена фильтрация
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