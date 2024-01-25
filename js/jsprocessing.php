<?php

global $filters, $tds_mode, $black_jsconnect_action;
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../core.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../main.php';

$cloaker = new Cloaker($tds_filters);
//Проверяем зашедшего пользователя
$is_bad_click = $cloaker->is_bad_click();

//Добавляем, по какому из js-событий пользователь прошёл сюда
if (isset($_GET['reason']))
    $cloaker->block_reason[] = $_GET['reason'];

if (isset($_SERVER['HTTP_REFERER'])) {
    $parsed_url = parse_url($_SERVER['HTTP_REFERER']);
    header('Access-Control-Allow-Origin: ' . $parsed_url['scheme'] . '://' . $parsed_url['host']);
}
header('Access-Control-Allow-Credentials: true');

if ($is_bad_click === true || $tds_mode === 'full') {
    //это бот, который прошёл javascript-проверку, ну или эта проверка выключена
    $db = new Db();
    $db->add_white_click($cloaker->click_params, $cloaker->block_reason, $cur_config);
    header("Access-Control-Expose-Headers: YWBAction", false, 200);
    header("YWBAction: none", true, 200);
    return http_response_code(200);
} else if ($is_bad_click === false || $tds_mode === 'off') { //Обычный юзверь или отключена фильтрация

    if ($black_jsconnect_action === 'redirect') { //если в настройках JS-подключения у нас редирект
        $url = rtrim(get_cloaker_path(), '/');
        $from = rtrim(strtok($_GET['uri'], '?'), '/');
        //если у нас одинаковый адрес, откуда мы вызываем скрипт и наш собственный
        //значит у нас просто включена JS-проверка и нам не нужно опять редиректить
        if ($url !== $from) {
            header("Access-Control-Expose-Headers: YWBAction", false, 200);
            header("Access-Control-Expose-Headers: YWBLocation", false, 200);
            header("YWBAction: redirect", true, 200);
            header("YWBLocation: " . $url, true, 200);
            return http_response_code(200);
        }
    }
    //если в настройках JS-подключения у нас подмена или iframe
    header("Access-Control-Expose-Headers: YWBAction", false, 200);
    header("YWBAction: " . $black_jsconnect_action, true, 200);
    black($cloaker->click_params);

    if (!headers_sent()) {
        //если в настройках кло для блэка стоит редирект, то для js xhr запроса надо его модифицировать
        $all_headers = implode(',', headers_list());
        if (str_contains($all_headers, "Location")) {
            header_remove("Location"); //удаляем редирект
            $matches = [];
            preg_match("/Location: ([^ ]+)/", $all_headers, $matches);
            $redirect_url = $matches[1];
            header("Access-Control-Expose-Headers: YWBLocation", false, 200);
            header("YWBAction: redirect", true, 200);
            header("YWBLocation: " . $redirect_url, true, 200);
            return http_response_code(200);
        }
    }
}
