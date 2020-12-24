<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require 'core.php';
include 'settings.php';
include 'logging.php';
include 'main.php';

//передаём все параметры в кло
$cloaker = new Cloaker($os_white,$country_white,$ip_black,$tokens_black,$ua_black,$isp_black,$block_without_referer);

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on) {
    write_white_to_log($cloaker->detect, ['fullcloak'], 1);
    white(false);
    return;
}

//если используются js-проверки, то сначала используются они
//проверка же обычная идёт далее в файле js/jsprocessing.php
if ($use_js_checks===true) { 
	white(true);
}
else{
	//Проверяем зашедшего пользователя
	$check_result = $cloaker->check();
	if (!isset($cloaker->result)||count($cloaker->result)==0) {
		$cloaker->result=['OK'];
	}

	if ($check_result == 0 || $disable_tds) { //Обычный юзверь или отключена фильтрация
		black($cloaker->detect, $cloaker->result, $check_result);
		return;
	} else { //Обнаружили бота или модера
		write_white_to_log($cloaker->detect, $cloaker->result, $check_result);
		white(false);
		return;
	}
}