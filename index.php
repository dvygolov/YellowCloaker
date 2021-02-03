<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require 'core.php';
include 'settings.php';
include 'db.php';
include 'main.php';

//передаём все параметры в кло
$cloaker = new Cloaker($os_white,$country_white,$ip_black_filename,$ip_black_cidr,$tokens_black,$url_should_contain,$ua_black,$isp_black,$block_without_referer,$block_vpnandtor);

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($tds_mode=='full') {
    add_white_click($cloaker->detect, ['fullcloak']);
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

	if ($check_result == 0 || $tds_mode==='off') { //Обычный юзверь или отключена фильтрация
		if ($tds_mode==='off') $cloaker->result=['TDS_DISABLED'];
		black($cloaker->detect, $cloaker->result);
		return;
	} else { //Обнаружили бота или модера
		add_white_click($cloaker->detect, $cloaker->result);
		white(false);
		return;
	}
}