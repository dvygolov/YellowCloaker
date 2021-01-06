<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require '../core.php';
include '../settings.php';
include '../logging.php';
include '../main.php';

//передаём все параметры в кло
$cloaker = new Cloaker($os_white,$country_white,$ip_black_filename,$ip_black_cidr,$tokens_black,$url_should_contain,$ua_black,$isp_black,$block_without_referer,$block_vpnandtor);
//Проверяем зашедшего пользователя
$check_result = $cloaker->check();

if (!isset($cloaker->result)||
     count($cloaker->result)==0) {
    $cloaker->result=['OK'];
}

//Добавляем, по какому из js-событий пользователь прошёл сюда
array_push($cloaker->result,$_GET['reason']);

if ($check_result === 0 || $tds_mode==='off') { //Обычный юзверь или отключена фильтрация
    header("Access-Control-Expose-Headers: YWBRedirect", false, 200);
    header("YWBRedirect: false", true, 200);
    black($cloaker->detect, $cloaker->result, $check_result);

	if (!headers_sent()) {
        //если был редирект, то для js xhr запроса надо его модифицировать
		$all_headers=implode(',', headers_list());
        if (strpos($all_headers, "Location")!==false) {
			header_remove("Location"); //удаляем редирект
			$matches=[];
			preg_match("/Location: ([^ ]+)/",$all_headers,$matches);
			$redirect_url=$matches[1];
            header("Access-Control-Expose-Headers: YWBLocation", false, 200);
            header("YWBRedirect: true", true, 200);
            header("YWBLocation: ".$redirect_url, true, 200);
        }
    }
} else if ($check_result===1 || $tds_mode==='full') 
{ 
	//это бот, который прошёл javascript-проверку!
	write_white_to_log($cloaker->detect, $cloaker->result, $check_result, '', '');
    header("Access-Control-Expose-Headers: YWBRedirect", false, 200);
    header("YWBRedirect: false", true, 200);
    header('Access-Control-Allow-Credentials: true');
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parsed_url=parse_url($_SERVER['HTTP_REFERER']);
        header('Access-Control-Allow-Origin: '.$parsed_url['scheme'].'://'.$parsed_url['host']);
    }
    return http_response_code(200);
}
