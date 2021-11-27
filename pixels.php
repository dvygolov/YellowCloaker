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
function insert_fbpixel_script($html, $event=null)
{
    $fb_pixel = get_fbpixel();
    if (empty($fb_pixel)) return $html;

    $file_name=__DIR__.'/scripts/pixels/facebook/fbpxcode.js';
    if (!file_exists($file_name)) {
        return $html;
    }
    $px_code = file_get_contents($file_name);
    if (empty($px_code)) {
        return $html;
    }

    $search='{PIXELID}';
    $px_code = str_replace($search, $fb_pixel, $px_code);
	if ($event==null){ //если не передали Event, значит добавляем только код пикселя без передачи событий
		$search = "fbq('track', '{EVENT}');";
		$px_code = str_replace($search, $event, $px_code);
	}
	else{
		$search='{EVENT}';
		$px_code = str_replace($search, $event, $px_code);
	}

    return insert_before_tag($html, '</head>', $px_code);
}

function insert_fbpixel_viewcontent($html,$url){
    global $fb_use_viewcontent,$fb_view_content_time,$fb_view_content_percent;
    if ($fb_use_viewcontent){
        if ($fb_view_content_time>0){
            $html= insert_file_content_with_replace($html,'pixels/facebook/fbpxviewcontenttime.js','</head>',['{SECONDS}','{PAGE}'],[$fb_view_content_time,$url]);
        }
        if ($fb_view_content_percent>0){
            $html= insert_file_content_with_replace($html,'pixels/facebook/fbpxviewcontentpercent.js','</head>',['{PERCENT}','{PAGE}'],[$fb_view_content_percent,$url]);
        }
    }
    return $html;
}

function full_fbpixel_processing($html,$url){
    global $fb_use_pageview, $fb_add_button_pixel, $fb_thankyou_event;

	$fb_pixel = get_fbpixel();
    if (!empty($fb_pixel)){
        //добавляем в страницу скрипт Facebook Pixel с событием PageView
        if ($fb_use_pageview) {
            $html = insert_fbpixel_script($html, 'PageView');
        }
        else if ($fb_add_button_pixel){
            $html = insert_fbpixel_script($html);
        }

        $html=insert_fbpixel_viewcontent($html,$url);

        if ($fb_add_button_pixel){
            $html= insert_file_content_with_replace($html,'pixels/facebook/fbpxbuttonconversion.js','</head>','{EVENT}',$fb_thankyou_event);
        }
    }
    return $html;
}

//если задан ID Google Tag Manager, то вставляем его скрипт
function insert_gtm_script($html)
{
    global $gtm_id;
    if ($gtm_id==='' || empty($gtm_id)) {
        return $html;
    }

    return insert_file_content_with_replace($html,'pixels/google/gtmcode.js','</head>','{GTMID}',$gtm_id);
}

//если задан ID Yandex Metrika, то вставляем её скрипт
function insert_yandex_script($html)
{
    global $ya_id;
    if ($ya_id=='' || empty($ya_id)) {
        return $html;
    }

    return insert_file_content_with_replace($html,'pixels/yandex/yacode.js','</head>','{YAID}',$ya_id);
}
?>