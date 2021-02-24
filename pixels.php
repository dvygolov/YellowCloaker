<?php
require_once 'settings.php';
require_once 'htmlinject.php';

function get_fbpixel(){
	global $fbpixel_subname;
    //если пиксель не лежит в querystring, то также ищем его в куки
    $fb_pixel = isset($_GET[$fbpixel_subname])?$_GET[$fbpixel_subname]:(isset($_COOKIE[$fbpixel_subname])?$_COOKIE[$fbpixel_subname]:'');
	return $fb_pixel;
}

//если в querystring есть id пикселя фб, то встраиваем его скрытым полем в форму на лендинге
//чтобы потом передать его на страницу "Спасибо" через send.php и там отстучать Lead
function insert_fbpixel_id($html)
{
    global $fbpixel_subname;
    $fb_pixel = get_fbpixel();
    if (empty($fb_pixel)) return $html;

    $fb_input = '<input type="hidden" name="'.$fbpixel_subname.'" value="'.$fb_pixel.'"/>';
    $needle = '</form>';
    return insert_before_tag($html, $needle, $fb_input);
}

//вставляет в head полный код пикселя фб с указанным в $event событием (Lead,PageView,Purchase итп)
//если событие не указано, то и не шлём его
function insert_fbpixel_script($html, $event)
{
    global $use_cloaked_pixel;
    $fb_pixel = get_fbpixel();
    if (empty($fb_pixel)) return $html;

	$file_name='';
	if ($use_cloaked_pixel)
	    $file_name=__DIR__.'/scripts/fbpxcloaked.js';
	else
		$file_name=__DIR__.'/scripts/fbpxcode.js';
    if (!file_exists($file_name)) {
        return $html;
    }
    $px_code = file_get_contents($file_name);
    if (empty($px_code)) {
        return $html;
    }

    $search='{PIXELID}';
    $px_code = str_replace($search, $fb_pixel, $px_code);
	if ($event===''){ //если не передали Event, значит добавляем только код пикселя без передачи событий
		$search = "fbq('track', '{EVENT}');";
		$px_code = str_replace($search, $event, $px_code);
	}
	else{
		$search='{EVENT}';
		$px_code = str_replace($search, $event, $px_code);
	}

    $needle='</head>';
    return insert_before_tag($html, $needle, $px_code);
}

//если задан ID Google Tag Manager, то вставляем его скрипт
function insert_gtm_script($html)
{
    global $gtm_id;
    if ($gtm_id==='' || empty($gtm_id)) {
        return $html;
    }

    return insert_file_content_with_replace($html,'gtmcode.js','</head>','{GTMID}',$gtm_id);
}

//если задан ID Yandex Metrika, то вставляем её скрипт
function insert_yandex_script($html)
{
    global $ya_id;
    if ($ya_id=='' || empty($ya_id)) {
        return $html;
    }

    return insert_file_content_with_replace($html,'yacode.js','</head>','{YAID}',$ya_id);
}
?>