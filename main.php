<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';

function white($use_js_checks)
{
    global $white_action, $white_folder_names, $white_redirect_urls, $white_redirect_type;
    global $white_curl_urls, $white_error_codes, $white_use_domain_specific, $white_domain_specific;
    global $save_user_flow;

    $action = $white_action;
    $folder_names = $white_folder_names;
    $redirect_urls = $white_redirect_urls;
    $curl_urls = $white_curl_urls;
    $error_codes = $white_error_codes;

    //HACK: грязный хак для прокидывания реферера через куки
    if ($use_js_checks && !empty($_SERVER['HTTP_REFERER'])) {
        ywbsetcookie("referer", $_SERVER['HTTP_REFERER']);
    }

    if ($white_use_domain_specific) { //если у нас под каждый домен свой вайт
        $curdomain = $_SERVER['HTTP_HOST'];
        if (str_ends_with($curdomain, ':' . $_SERVER['SERVER_PORT'])) {
            $portLength = strlen(':' . $_SERVER['SERVER_PORT']);
            $curdomain = substr($curdomain, 0, - $portLength);
        }
        foreach ($white_domain_specific as $wds) {
            if ($wds['name'] == $curdomain) {
                $wtd_arr = explode(":", $wds['action'], 2);
                $action = $wtd_arr[0];
                switch ($action) {
                    case 'error':
                        $error_codes = [intval($wtd_arr[1])];
                        break;
                    case 'folder':
                        $folder_names = [$wtd_arr[1]];
                        break;
                    case 'curl':
                        $curl_urls = [$wtd_arr[1]];
                        break;
                    case 'redirect':
                        $redirect_urls = [$wtd_arr[1]];
                        break;
                }
                break;
            }
        }
    }

    //при js-проверках либо показываем специально подготовленный вайт
    //либо вставляем в имеющийся вайт код проверки
    if ($use_js_checks) {
        switch ($action) {
            case 'error':
            case 'redirect':
                echo load_js_testpage();
                break;
            case 'folder':
                $curfolder = select_item($folder_names, $save_user_flow, 'white', true);
                echo load_white_content($curfolder[0], $use_js_checks);
                break;
            case 'curl':
                $cururl = select_item($curl_urls, $save_user_flow, 'white', false);
                echo load_white_curl($cururl[0], $use_js_checks);
                break;
        }
    } else {
        switch ($action) {
            case 'error':
                $curcode = select_item($error_codes, $save_user_flow, 'white', true);
                http_response_code($curcode[0]);
                break;
            case 'folder':
                $curfolder = select_item($folder_names, $save_user_flow, 'white', true);
                echo load_white_content($curfolder[0], false);
                break;
            case 'curl':
                $cururl = select_item($curl_urls, $save_user_flow, 'white', false);
                echo load_white_curl($cururl[0], false);
                break;
            case 'redirect':
                $cururl = select_item($redirect_urls, $save_user_flow, 'white', false);
                redirect($cururl[0], $white_redirect_type);
                break;
        }
    }
}

function black($clkrdetect)
{
    global $black_preland_action, $black_preland_folder_names;
    global $black_land_action, $black_land_folder_names, $save_user_flow;
    global $black_land_redirect_type, $black_land_redirect_urls;
    global $cur_config;

    header('Access-Control-Allow-Credentials: true');
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parsed_url = parse_url($_SERVER['HTTP_REFERER']);
        header('Access-Control-Allow-Origin: ' . $parsed_url['scheme'] . '://' . $parsed_url['host']);
    }

    $cursubid = set_subid();
    set_px();

    $landings = [];
    $isfolderland = false;
    if ($black_land_action == 'redirect')
        $landings = $black_land_redirect_urls;
    else if ($black_land_action == 'folder') {
        $landings = $black_land_folder_names;
        $isfolderland = true;
    }

    $db = new Db();
    switch ($black_preland_action) {
        case 'none':
            $res = select_item($landings, $save_user_flow, 'landing', $isfolderland);
            $landing = $res[0];
            $db->add_black_click($cursubid, $clkrdetect, '', $landing, $cur_config);

            switch ($black_land_action) {
                case 'folder':
                    echo load_landing($landing);
                    break;
                case 'redirect':
                    redirect($landing,$black_land_redirect_type,false,true,true);
                    break;
            }
            break;
        case 'folder': //если мы используем локальные проклы
            $prelandings = $black_preland_folder_names;
            if (empty($prelandings)) break;
            $res = select_item($prelandings, $save_user_flow, 'prelanding', true);
            $prelanding = $res[0];
            $res = select_item($landings, $save_user_flow, 'landing', $isfolderland);
            $landing = $res[0];
            $t = $res[1];

            echo load_prelanding($prelanding, $t);
            $db->add_black_click($cursubid, $clkrdetect, $prelanding, $landing, $cur_config);
            break;
    }
}