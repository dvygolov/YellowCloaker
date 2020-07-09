<?php
//Включение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

require 'bnc.php';
include 'settings.php';
include 'htmlprocessing.php';
include 'logging.php';

//передаём все параметры в кло
$cloacker = new Cloacker();
$cloacker->os_white = $os_white; 
$cloacker->country_white = $country_white;
$cloacker->ip_black = $ip_black; 
$cloacker->tokens_black = $tokens_black;
$cloacker->ua_black = $ua_black;
$cloacker->block_without_referer = $block_without_referer;

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on) {
	write_visitors_to_log($cloacker->detect,['full_cloak'],1,'','');
	white();
	return;
}

//Проверяем зашедшего пользователя
$check_result = $cloacker->check();
if (!isset($cloacker->result))
	$cloacker->result=['OK'];

if ($check_result == 0) //Обычный юзверь
{
	//если мы используем прокладки
	if ($preland_folder_name!='')
	{
		//A-B тестирование прокладок
		$prelandings = explode(",", $preland_folder_name);
		$r = rand(0, count($prelandings) - 1);

		//A-B тестирование лендингов
		$landings = explode(",", $land_folder_name);
		$t = rand(0, count($landings) - 1);
		
		write_visitors_to_log($cloacker->detect,$cloacker->result,$check_result,$prelandings[$r],$landings[$t]);
		echo load_content($prelandings[$r],$t);
	}
	else //если у нас только ленды без прокл
	{ 
		//A-B тестирование лендингов
		$landings = explode(",", $land_folder_name);
		$r = rand(0, count($landings) - 1);
		write_visitors_to_log($cloacker->detect,$cloacker->result,$check_result,'',$landings[$r]);
		echo load_content($landings[$r],-1);
	}
} 
else //Обнаружили бота или модера
{
	write_visitors_to_log($cloacker->detect,$cloacker->result,$check_result,'','');
	white();
	return;
}

function white(){
	global $white_action,$white_folder_name,$redirect_url,$redirect_type,$curl_url,$error_code;
	switch($white_action){
		case 'error':
  	        http_response_code($error_code);
    		break;
		case 'site':
			echo load_content($white_folder_name,-1);
			break;
		case 'curl':
			echo load_white_curl($curl_url);
			break;
		case 'redirect':
			if ($redirect_type==302){
				header('Location: '.$redirect_url);
				exit;
			}
			else{
				header('Location: '.$redirect_url, true, $redirect_type);
				exit;
			}
			break;
	}
	return;
}
?>