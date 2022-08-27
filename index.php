<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once 'core.php';
require_once 'settings.php';
require_once 'db.php';
require_once 'main.php';
//передаём все параметры в кло
$cloaker = new Cloaker($os_white,$country_white,$lang_white,$ip_black_filename,$ip_black_cidr,$tokens_black,$url_should_contain,$ua_black,$isp_black,$block_without_referer,$referer_stopwords,$block_vpnandtor);

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

	if ($check_result == 0 || $tds_mode==='off') { //Обычный юзверь или отключена фильтрация
		black($cloaker->detect);
		return;
	} else { //Обнаружили бота или модера
		add_white_click($cloaker->detect, $cloaker->result);
		white(false);
		return;
	}
}