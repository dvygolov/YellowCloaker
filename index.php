<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

require 'core.php';
include 'settings.php';
include 'logging.php';
include 'main.php';

//передаём все параметры в кло
$cloacker = new Cloacker();
$cloacker->os_white = $os_white; 
$cloacker->country_white = $country_white;
$cloacker->ip_black = $ip_black; 
$cloacker->tokens_black = $tokens_black;
$cloacker->ua_black = $ua_black;
$cloacker->block_without_referer = $block_without_referer;
$cloacker->isp_black = $isp_black;

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on) {
	write_white_to_log($cloacker->detect,['full_cloak'],1,'','');
	white();
	return;
}

//Проверяем зашедшего пользователя
$check_result = $cloacker->check();
if (!isset($cloacker->result)||
     count($cloacker->result)==0) 
     $cloacker->result=['OK'];

if ($check_result == 0 ||$disable_tds) //Обычный юзверь или отключена фильтрация
{
	black($cloacker->detect,$cloacker->result,$check_result);
	return;
} 
else //Обнаружили бота или модера
{
	write_white_to_log($cloacker->detect,$cloacker->result,$check_result,'','');
	white();
	return;
}
?>