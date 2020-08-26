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

if (!isset($cloacker->result)||
     count($cloacker->result)==0) {
    $cloacker->result=['OK'];
}

if ($check_result === 0 || $disable_tds) { //Обычный юзверь или отключена фильтрация
    header("Access-Control-Expose-Headers: YWBRedirect", false, 200);
    header("YWBRedirect: false", true, 200);
    black($cloacker->detect, $cloacker->result, $check_result);
    if (!headers_sent()) {
        //если был редирект, то для js xhr запроса надо его модифицировать
        if (strpos(implode(',', headers_list()), "Location")!==false) {
            header_remove("Location"); //удаляем редирект
            header("Access-Control-Expose-Headers: YWBLocation", false, 200);
            header("YWBRedirect: true", true, 200);
            header("YWBLocation: ".$black_redirect_url, true, 200);
        }
    }
} else { //это бот!
    header("Access-Control-Expose-Headers: YWBRedirect", false, 200);
    header("YWBRedirect: false", true, 200);
    header('Access-Control-Allow-Credentials: true');
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parsed_url=parse_url($_SERVER['HTTP_REFERER']);
        header('Access-Control-Allow-Origin: '.$parsed_url['scheme'].'://'.$parsed_url['host']);
    }
    return http_response_code(200);
}
