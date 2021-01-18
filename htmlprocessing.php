<?php
include __DIR__.'/js/obfuscator.php';
require_once 'ipcountry.php';
require_once 'requestfunc.php';
require_once 'fbpixel.php';

//Подгрузка контента блэк проклы из другой папки через CURL
function load_prelanding($url, $land_number)
{
    global $fb_use_pageview, $fb_use_viewcontent, $fb_view_content_time, $fb_view_content_percent;
	global $replace_prelanding, $replace_prelanding_address;

    $fullpath = get_abs_from_rel($url,true);
    $html = get_html($fullpath);
    $baseurl = '/'.$url.'/';
    //переписываем все относительные src,href & action (не начинающиеся с http)
    //TODO:сделать полный путь к форме в случае js-подключения
	$html = rewrite_relative_urls($html,$baseurl);
    $html = preg_replace('/\saction=[\'\"](?!http|\/\/)([^\'\"]+)[\'\"]/', " action=\"$baseurl\\1\"", $html);

    //добавляем в страницу скрипт GTM
    $html = insert_gtm_script($html);
    //добавляем в страницу скрипт Yandex Metrika
    $html = insert_yandex_script($html);
    //добавляем в страницу скрипт Facebook Pixel с событием PageView
    if ($fb_use_pageview){
        $html = insert_fb_pixel_script($html, 'PageView');
    }

	if ($fb_use_viewcontent){
		if ($fb_view_content_time>0){
	        $html = insert_fb_pixel_viewcontent_time_script($html, $fb_view_content_time, $url);
		}
		if ($fb_view_content_percent>0){
	        $html = insert_fb_pixel_viewcontent_percent_script($html, $fb_view_content_percent, $url);
		}
	}

    $html = replace_tel_type($html);

    //добавляем во все формы сабы
    $html = insert_subs($html);
    //добавляем в формы id пикселя фб
    $html = insert_fbpixel_id($html);

    $domain = get_domain_with_prefix();
    $querystr = $_SERVER['QUERY_STRING'];
    //замена всех ссылок на прокле на универсальную ссылку ленда landing.php
    $replacement = "\\1".$domain.'/landing.php?l='.$land_number.(!empty($querystr)?'&'.$querystr:'');

    //если мы будем подменять преленд при переходе на ленд, то ленд надо открывать в новом окне
    if ($replace_prelanding) {
        $replacement=$replacement.'" target="_blank"';
        $url = replace_all_macros($replace_prelanding_address); //заменяем макросы
        $url = add_subs_to_link($url); //добавляем сабы
        $html = insert_file_content_with_replace($html, 'replaceprelanding.js', '</body>', '{REDIRECT}', $url);
    }
    $html = preg_replace('/(<a[^>]+href=")([^"]*)/', $replacement, $html);

    $html = insert_additional_scripts($html);

    return $html;
}

//Подгрузка контента блэк ленда из другой папки через CURL
function load_landing($url)
{
    global $fb_use_pageview,$fb_thankyou_event,$fb_add_button_pixel;
	global $fb_use_viewcontent, $fb_view_content_time, $fb_view_content_percent;
	global $black_land_log_conversions_on_button_click,$black_land_use_custom_thankyou_page;

    $fullpath = get_abs_from_rel($url);
    $fpwqs = get_abs_from_rel($url,true);

    $html=get_html($fpwqs);
    $baseurl = '/'.$url.'/';

    if($black_land_use_custom_thankyou_page===false){
		$html=insert_after_tag($html,"<head>","<base href='".$fullpath."'>");
	}
	else{
		//переписываем все относительные src,href & action (не начинающиеся с http)
		$html = rewrite_relative_urls($html,$baseurl);
		//меняем обработчик формы, чтобы у вайта и блэка была одна thankyou page
		$html = preg_replace('/\saction=[\'\"]([^\'\"]+)[\'\"]/', " action=\"../send.php?".http_build_query($_GET)."\"", $html);
	}

    //добавляем в страницу скрипт GTM
    $html = insert_gtm_script($html);
    //добавляем в страницу скрипт Yandex Metrika
    $html = insert_yandex_script($html);
    //добавляем в страницу скрипт Facebook Pixel с событием PageView
    if ($fb_use_pageview) {
        $html = insert_fb_pixel_script($html, 'PageView');
    }
	else if ($fb_add_button_pixel){
		$html = insert_fb_pixel_script($html, '');
	}

	if ($fb_use_viewcontent){
		if ($fb_view_content_time>0){
	        $html = insert_fb_pixel_viewcontent_time_script($html, $fb_view_content_time, $url);
		}
		if ($fb_view_content_percent>0){
	        $html = insert_fb_pixel_viewcontent_percent_script($html, $fb_view_content_percent, $url);
		}
	}

	if ($fb_add_button_pixel){
		$html = insert_fb_pixel_button_conversion_script($html,$fb_thankyou_event);
	}
	if ($black_land_log_conversions_on_button_click){
		$html = insert_log_conversions_on_buttonclick_script($html);
	}

    $html = insert_additional_scripts($html);

    //добавляем во все формы сабы
    $html = insert_subs($html);
    //добавляем в формы id пикселя фб
    $html = insert_fbpixel_id($html);

    //заменяем поле с телефоном на более удобный тип - tel
    $html = replace_tel_type($html);
    $html = insert_phone_mask($html);

    return $html;
}

//добавляем доп.скрипты
function insert_additional_scripts($html)
{
    global $disable_text_copy, $back_button_action, $replace_back_button, $replace_back_address, $add_tos;

    if ($disable_text_copy) {
        $html = insert_file_content($html, 'disablecopy.js', '</body>');
    }

	switch($back_button_action){
		case 'disable':
			$html = insert_file_content($html, 'disableback.js', '</body>');
			break;
		case 'replace':
			$url= replace_all_macros($replace_back_address); //заменяем макросы
			$url = add_subs_to_link($url); //добавляем сабы
			$html = insert_file_content_with_replace($html, 'replaceback.js', '</body>', '{RA}', $url);
			break;
	}

    if ($add_tos) {
        $html = insert_file_content($html, 'tos.html', '</body>');
    }
    return $html;
}

//если тип поля телефона - text, меняем его на tel для более удобного ввода с мобильных
function replace_tel_type($html)
{
    $html = preg_replace('/(<input[^>]*name="(phone|tel)"[^>]*type=")(text)("[^>]*>)/', "\\1tel\\4", $html);
    $html = preg_replace('/(<input[^>]*type=")(text)("[^>]*name="(phone|tel)"[^>]*>)/', "\\1tel\\3", $html);
    return $html;
}

function insert_phone_mask($html)
{
    global $black_land_use_phone_mask,$black_land_phone_mask;
	if (!$black_land_use_phone_mask) return $html;
    $html = insert_before_tag($html, '</head>', '<script src="scripts/inputmask.js"></script>');
    $html = insert_before_tag($html, '</head>', '<script src="scripts/inputmaskbinding.js"></script>');
    $html = preg_replace(
        '/(<input[^>]*name="(phone|tel)"[^>]*)(>)/',
        "\\1 data-inputmask=\"'mask': '".$black_land_phone_mask."'\">",
        $html
    );

    return $html;
}

//Подгрузка контента вайта ИЗ ПАПКИ
function load_white_content($url, $add_js_check)
{
    global $fb_use_pageview;
    $fullpath = get_abs_from_rel($url,true);

    $html = get_html($fullpath);
    $baseurl = '/'.$url.'/';
    //переписываем все относительные src и href (не начинающиеся с http)
	$html = rewrite_relative_urls($html,$baseurl);
    //добавляем в страницу скрипт GTM
    $html = insert_gtm_script($html);
    //добавляем в страницу скрипт Yandex Metrika
    $html = insert_yandex_script($html);
    //добавляем в страницу скрипт Facebook Pixel с событием PageView
    if ($fb_use_pageview) {
        $html = insert_fb_pixel_script($html, 'PageView');
    }

    //если на вайте есть форма, то меняем её обработчик, чтобы у вайта и блэка была одна thankyou page
    $html = preg_replace('/\saction=[\'\"]([^\'\"]+)[\'\"]/', " action=\"../worder.php?".http_build_query($_GET)."\"", $html);

    //добавляем в <head> пару доп. метатегов
    $html= str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);

    if ($add_js_check) {
        $html = add_js_testcode($html);
    }
    return $html;
}

//когда подгружаем вайт методом CURL
function load_white_curl($url, $add_js_check)
{
    $html=get_html($url,true,true);
	$html = rewrite_relative_urls($html,$url);

    //удаляем лишние палящие теги
    $html = preg_replace('/(<meta property=\"og:url\" [^>]+>)/', "", $html);
    $html = preg_replace('/(<link rel=\"canonical\" [^>]+>)/', "", $html);

    //добавляем в страницу скрипт Facebook Pixel
    $html = insert_fb_pixel_script($html, 'PageView');

    //добавляем в <head> пару доп. метатегов
    $html= str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);

    if ($add_js_check) {
        $html = add_js_testcode($html);
    }
    return $html;
}

function load_js_testpage()
{
    $test_page= file_get_contents(__DIR__.'/js/testpage.html');
    return add_js_testcode($test_page);
}

function add_js_testcode($html)
{
    global $js_obfuscate;
    $port = get_port();
    $jsCode= str_replace('{DOMAIN}', $_SERVER['SERVER_NAME'].":".$port, file_get_contents(__DIR__.'/js/connect.js'));
    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($jsCode);
        $jsCode = $hunter->Obfuscate();
    }
	$needle = '</body>';
	if (strpos($html,$needle)===false) $needle = '</html>';
    return str_replace($needle, "<script id='connect'>".$jsCode."</script>".$needle, $html);
}

function add_subs_to_link($url)
{
    global $sub_ids;
    foreach ($sub_ids as $sub) {
    	$key = $sub["name"];
	$value = $sub["rewrite"];
        $delimiter= (strpos($url, '?')===false?"?":"&");
        if ($key=='subid' && isset($_COOKIE['subid'])) {
            $url.= $delimiter.$value.'='.$_COOKIE['subid'];
        } elseif ($key=='prelanding' && isset($_COOKIE['prelanding'])) {
            $url.= $delimiter.$value.'='.$_COOKIE['prelanding'];
        } elseif ($key=='landing' && isset($_COOKIE['landing'])) {
            $url.= $delimiter.$value.'='.$_COOKIE['landing'];
        } elseif (!empty($_GET[$key])) {
            $url.= $delimiter.$value.'='.$_GET[$key];
        }
    }
    return $url;
}

//вставляет все сабы в hidden полях каждой формы
function insert_subs($html)
{
    global $sub_ids;
    $all_subs = '';
    foreach ($sub_ids as $sid) {
        if ($sid['name']=='subid' && isset($_COOKIE['subid'])) {
            $all_subs = $all_subs.'<input type="hidden" name="'.$sid['rewrite'].'" value="'.$_COOKIE['subid'].'"/>';
        } elseif ($sid['name']=='prelanding' && isset($_COOKIE['prelanding'])) {
            $all_subs = $all_subs.'<input type="hidden" name="'.$sid['rewrite'].'" value="'.$_COOKIE['prelanding'].'"/>';
        } elseif ($sid['name']=='landing' && isset($_COOKIE['landing'])) {
            $all_subs = $all_subs.'<input type="hidden" name="'.$sid['rewrite'].'" value="'.$_COOKIE['landing'].'"/>';
        } elseif (!empty($_GET[$sid['name']])) {
            $all_subs = $all_subs.'<input type="hidden" name="'.$sid['rewrite'].'" value="'.$_GET[$sid['name']].'"/>';
        }
    }
    $needle = '<form';
    return insert_after_tag($html, $needle, $all_subs);
}

//если в querystring есть id пикселя фб, то встраиваем его скрытым полем в форму на лендинге
//чтобы потом передать его на страницу "Спасибо" через send.php и там отстучать Lead
function insert_fbpixel_id($html)
{
    global $fbpixel_subname;
    $fb_pixel = getpixel();
    if (empty($fb_pixel)) return $html;

    $fb_input = '<input type="hidden" name="'.$fbpixel_subname.'" value="'.$fb_pixel.'"/>';
    $needle = '</form>';
    return insert_before_tag($html, $needle, $fb_input);
}

//вставляет в head полный код пикселя фб с указанным в $event событием (Lead,PageView,Purchase итп)
//если событие не указано, то и не шлём его
function insert_fb_pixel_script($html, $event)
{
    global $use_cloaked_pixel;
    $fb_pixel = getpixel();
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

function insert_fb_pixel_button_conversion_script($html, $event){
	$fb_pixel = getpixel();
    if (empty($fb_pixel)) return $html;

	$file_name=__DIR__.'/scripts/fbpxbuttonconversion.js';
    if (!file_exists($file_name)) {
        return $html;
    }
    $px_code = file_get_contents($file_name);
    if (empty($px_code)) {
        return $html;
    }

	$search='{EVENT}';
	$px_code = str_replace($search, $event, $px_code);

    $needle='</head>';
    return insert_before_tag($html, $needle, $px_code);
}

function insert_log_conversions_on_buttonclick_script($html){
	$file_name=__DIR__.'/scripts/btnclicklog.js';
    if (!file_exists($file_name)) {
        return $html;
    }
    $log_code = file_get_contents($file_name);
    if (empty($log_code)) {
        return $html;
    }

    $needle='</head>';
    return insert_before_tag($html, $needle, $log_code);
}

function insert_fb_pixel_viewcontent_time_script($html, $seconds, $page){
	$fb_pixel = getpixel();
    if (empty($fb_pixel)) return $html;

	$file_name=__DIR__.'/scripts/fbpxviewcontenttime.js';
    if (!file_exists($file_name)) {
        return $html;
    }
    $px_code = file_get_contents($file_name);
    if (empty($px_code)) {
        return $html;
    }

	$search='{SECONDS}';
	$px_code = str_replace($search, $seconds, $px_code);
	$search='{PAGE}';
	$px_code = str_replace($search, $page, $px_code);

    $needle='</head>';
    return insert_before_tag($html, $needle, $px_code);
}

function insert_fb_pixel_viewcontent_percent_script($html, $percent, $page){
	$fb_pixel = getpixel();
    if (empty($fb_pixel)) return $html;

	$file_name=__DIR__.'/scripts/fbpxviewcontentpercent.js';
    if (!file_exists($file_name)) {
        return $html;
    }
    $px_code = file_get_contents($file_name);
    if (empty($px_code)) {
        return $html;
    }

	$search='{PERCENT}';
	$px_code = str_replace($search, $percent, $px_code);
	$search='{PAGE}';
	$px_code = str_replace($search, $page, $px_code);

    $needle='</head>';
    return insert_before_tag($html, $needle, $px_code);
}

//если задан ID Google Tag Manager, то вставляем его скрипт
function insert_gtm_script($html)
{
    global $gtm_id;
    if ($gtm_id=='' || empty($gtm_id)) {
        return $html;
    }

    $code_file_name=__DIR__.'/scripts/gtmcode.js';
    if (!file_exists($code_file_name)) {
        return $html;
    }
    $gtm_code = file_get_contents($code_file_name);
    if (empty($gtm_code)) {
        return $html;
    }

    $search = '{GTMID}';
    $gtm_code = str_replace($search, $gtm_id, $gtm_code);
    $needle='</head>';
    return insert_before_tag($html, $needle, $gtm_code);
}

//если задан ID Yandex Metrika, то вставляем её скрипт
function insert_yandex_script($html)
{
    global $ya_id;
    if ($ya_id=='' || empty($ya_id)) {
        return $html;
    }

    $code_file_name=__DIR__.'/scripts/yacode.js';
    if (!file_exists($code_file_name)) {
        return $html;
    }
    $ya_code = file_get_contents($code_file_name);
    if (empty($ya_code)) {
        return $html;
    }

    $search = '{YAID}';
    $ya_code = str_replace($search, $ya_id, $ya_code);
    $needle='</head>';
    return insert_before_tag($html, $needle, $ya_code);
}

//заменяем все макросы на реальные значения из куки
function replace_all_macros($url)
{
    global $fbpixel_subname;
    $px=isset($_GET[$fbpixel_subname])?$_GET[$fbpixel_subname]:(isset($_COOKIE[$fbpixel_subname])?$_COOKIE[$fbpixel_subname]:'');
    $landing = isset($_COOKIE['landing'])?$_COOKIE['landing']:'';
    $prelanding = isset($_COOKIE['prelanding'])?$_COOKIE['prelanding']:'';
    $subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';

    $tmp_url = str_replace('{px}', $px, $url);
    $tmp_url = str_replace('{landing}', $landing, $tmp_url);
    $tmp_url = str_replace('{prelanding}', $prelanding, $tmp_url);
    $tmp_url = str_replace('{subid}', $subid, $tmp_url);
    return $tmp_url;
}

function insert_file_content_with_replace($html, $scriptname, $needle, $search, $replacement)
{
    $code_file_name=__DIR__.'/scripts/'.$scriptname;
    if (!file_exists($code_file_name)) {
        echo 'File Not Found '.$code_file_name;
        return $html;
    }
    $script_code = file_get_contents($code_file_name);
    $script_code = str_replace($search, $replacement, $script_code);
    return insert_before_tag($html, $needle, $script_code);
}

function insert_file_content($html, $scriptname, $needle)
{
    $code_file_name=__DIR__.'/scripts/'.$scriptname;
    if (!file_exists($code_file_name)) {
        echo 'File Not Found '.$code_file_name;
        return $html;
    }
    $script_code = file_get_contents($code_file_name);
    return insert_before_tag($html, $needle, $script_code);
}

function insert_after_tag($html, $needle, $str_to_insert)
{
    $lastPos = 0;
    $positions = array();
    while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
        $positions[] = $lastPos;
        $lastPos = $lastPos + strlen($needle);
    }
    $positions = array_reverse($positions);
    foreach ($positions as $pos) {
        $finalpos=$pos+strlen($needle);
        //если у нас задан НЕ закрытый тег, то надо найти его конец
        if (strpos($needle,'>')===false)
        {
            while($html[$finalpos]!=='>')
                $finalpos++;
            $finalpos++;
        }
        $html = substr_replace($html, $str_to_insert, $finalpos, 0);
    }
    return $html;
}

function insert_before_tag($html, $needle, $str_to_insert)
{
    $lastPos = 0;
    $positions = array();
    while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
        $positions[] = $lastPos;
        $lastPos = $lastPos + strlen($needle);
    }
    $positions = array_reverse($positions);

    foreach ($positions as $pos) {
        $html = substr_replace($html, $str_to_insert, $pos, 0);
    }
    return $html;
}

//переписываем все относительные src и href (не начинающиеся с http или с //)
function rewrite_relative_urls($html,$url)
{
	$modified = preg_replace('/\ssrc=[\'\"](?!http|\/\/)([^\'\"]+)[\'\"]/', " src=\"$url\\1\"", $html);
	$modified = preg_replace('/\shref=[\'\"](?!http|#|\/\/)([^\'\"]+)[\'\"]/', " href=\"$url\\1\"", $modified);
	return $modified;
}
?>