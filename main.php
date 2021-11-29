<?php
require_once 'htmlprocessing.php';
require_once 'cookies.php';
require_once 'redirect.php';
require_once 'pixels.php';
require_once 'abtest.php';

//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

function white($use_js_checks)
{
    global $white_action,$white_folder_names,$white_redirect_urls,$white_redirect_type;
	global $white_curl_urls,$white_error_codes,$white_use_domain_specific,$white_domain_specific;
    global $save_user_flow;

    $action = $white_action;
    $folder_names= $white_folder_names;
    $redirect_urls= $white_redirect_urls;
    $curl_urls= $white_curl_urls;
    $error_codes= $white_error_codes;

    //грязный хак для прокидывания реферера через куки
    if ($use_js_checks && 
		isset($_SERVER['HTTP_REFERER']) && 
        !empty($_SERVER['HTTP_REFERER'])){
        ywbsetcookie("referer",$_SERVER['HTTP_REFERER']);
    }

    if ($white_use_domain_specific) { //если у нас под каждый домен свой вайт
        $curdomain = $_SERVER['SERVER_NAME'];
        foreach ($white_domain_specific as $wds) {
            if ($wds['name']==$curdomain) {
                $wtd_arr = explode(":", $wds['action'], 2);
                $action = $wtd_arr[0];
                switch ($action) {
                    case 'error':
                        $error_codes= [intval($wtd_arr[1])];
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
                $curfolder= select_item($folder_names,$save_user_flow,'white',true);
                echo load_white_content($curfolder[0], $use_js_checks);
                break;
            case 'curl':
                $cururl=select_item($curl_urls,$save_user_flow,'white',false);
                echo load_white_curl($cururl[0], $use_js_checks);
                break;
        }
    } else {
        switch ($action) {
            case 'error':
                $curcode= select_item($error_codes,$save_user_flow,'white',true);
                http_response_code($curcode[0]);
                break;
            case 'folder':
                $curfolder= select_item($folder_names,$save_user_flow,'white',true);
                echo load_white_content($curfolder[0], false);
                break;
            case 'curl':
                $cururl=select_item($curl_urls,$save_user_flow,'white',false);
                echo load_white_curl($cururl[0], false);
                break;
            case 'redirect':
                $cururl=select_item($redirect_urls,$save_user_flow,'white',false);
                redirect($cururl[0], $white_redirect_type,false);
                break;
        }
    }
    return;
}

function black($clkrdetect)
{
    global $black_preland_action,$black_preland_folder_names;
	global $black_land_action, $black_land_folder_names, $save_user_flow;
	global $black_land_redirect_type,$black_land_redirect_urls;
	global $fbpixel_subname;

    header('Access-Control-Allow-Credentials: true');
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parsed_url=parse_url($_SERVER['HTTP_REFERER']);
        header('Access-Control-Allow-Origin: '.$parsed_url['scheme'].'://'.$parsed_url['host']);
    }
	
	$cursubid=set_subid();
    set_facebook_cookies();


	$landings=[];
    $isfolderland=false;
	if ($black_land_action=='redirect')
		$landings = $black_land_redirect_urls;
	else if ($black_land_action=='folder')
    {
		$landings = $black_land_folder_names;
        $isfolderland=true;
    }
	
    switch ($black_preland_action) {
        case 'none':
            $res=select_landing($save_user_flow,$landings,$isfolderland);
            $landing=$res[0];
            add_black_click($cursubid, $clkrdetect, '', $landing);

            switch ($black_land_action){
                case 'folder':
                    echo load_landing($landing);
                    break;
                case 'redirect':
                    redirect($landing,$black_land_redirect_type,true);
                    break;
            }
            break;
        case 'folder': //если мы используем локальные проклы
            $prelandings=$black_preland_folder_names;
            if (empty($prelandings)) break;
            $prelanding='';
            $res=select_prelanding($save_user_flow,$prelandings);
            $prelanding = $res[0];
            $res=select_landing($save_user_flow,$landings,$isfolderland);
            $landing=$res[0];
            $t=$res[1];

            echo load_prelanding($prelanding, $t);
            add_black_click($cursubid, $clkrdetect, $prelanding, $landing);
			break;
    }
    return;
}

?>