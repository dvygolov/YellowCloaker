<?php
require_once 'js/obfuscator.php';
require_once 'bases/ipcountry.php';
require_once 'requestfunc.php';
require_once 'pixels.php';
require_once 'htmlinject.php';
require_once 'url.php';

//Подгрузка контента блэк проклы из другой папки через CURL
function load_prelanding($url, $land_number)
{
    global $fb_use_pageview, $fb_use_viewcontent, $fb_view_content_time, $fb_view_content_percent;
	global $replace_prelanding, $replace_prelanding_address;

    $fullpath = get_abs_from_rel($url);
    $fpwqs = get_abs_from_rel($url,true);

    $html=get_html($fpwqs);
    $html=remove_scrapbook($html);
    $html=remove_from_html($html,'removepreland.html');

    $html=insert_after_tag($html,"<head","<base href='".$fullpath."'>");
    //добавляем в страницу скрипт GTM
    $html = insert_gtm_script($html);
    //добавляем в страницу скрипт Yandex Metrika
    $html = insert_yandex_script($html);
	$fb_pixel = get_fbpixel();
    if (!empty($fb_pixel)){
        //добавляем в страницу скрипт Facebook Pixel с событием PageView
        if ($fb_use_pageview){
            $html = insert_fbpixel_script($html, 'PageView');
        }

        if ($fb_use_viewcontent){
            if ($fb_view_content_time>0){
                $html= insert_file_content_with_replace($html,'fbpxviewcontenttime.js','</head>',['{SECONDS}','{PAGE}'],[$fb_view_content_time,$url]);
            }
            if ($fb_view_content_percent>0){
                $html= insert_file_content_with_replace($html,'fbpxviewcontentpercent.js','</head>',['{PERCENT}','{PAGE}'],[$fb_view_content_percent,$url]);
            }
        }
    }

    $html = replace_city_macros($html);
    $html = replace_tel_type($html);
    $html = insert_phone_mask($html);
    //добавляем во все формы сабы
    $html = insert_subs_into_forms($html);
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
	global $replace_landing, $replace_landing_address;

    $fullpath = get_abs_from_rel($url);
    $fpwqs = get_abs_from_rel($url,true);

    $html=get_html($fpwqs);
    $html=remove_scrapbook($html);
    $html=remove_from_html($html,'removeland.html');
    $html=insert_after_tag($html,"<head","<base href='".$fullpath."'>");

    if($black_land_use_custom_thankyou_page===true){
		//меняем обработчик формы, чтобы у вайта и блэка была одна thankyou page
        $send=" action=\"../send.php";
        $query=http_build_query($_GET);
        if ($query!=='') $send.="?".$query;
        $send.="\"";
		$html = preg_replace('/\saction=[\'\"]([^\'\"]*)[\'\"]/', $send, $html);
	}

    //если мы будем подменять ленд при переходе на страницу Спасибо, то Спасибо надо открывать в новом окне
    if ($replace_landing) {
        $replacelandurl = replace_all_macros($replace_landing_address); //заменяем макросы
        $replacelandurl = add_subs_to_link($replacelandurl); //добавляем сабы
        $html = insert_file_content_with_replace($html, 'replacelanding.js', '</body>', '{REDIRECT}', $replacelandurl);
    }

    //добавляем в страницу скрипт GTM
    $html = insert_gtm_script($html);
    //добавляем в страницу скрипт Yandex Metrika
    $html = insert_yandex_script($html);

	$fb_pixel = get_fbpixel();
    if (!empty($fb_pixel)){
        //добавляем в страницу скрипт Facebook Pixel с событием PageView
        if ($fb_use_pageview) {
            $html = insert_fbpixel_script($html, 'PageView');
        }
        else if ($fb_add_button_pixel){
            $html = insert_fbpixel_script($html, '');
        }

        if ($fb_use_viewcontent){
            if ($fb_view_content_time>0){
                $html= insert_file_content_with_replace($html,'fbpxviewcontenttime.js','</head>',['{SECONDS}','{PAGE}'],[$fb_view_content_time,$url]);
            }
            if ($fb_view_content_percent>0){
                $html= insert_file_content_with_replace($html,'fbpxviewcontentpercent.js','</head>',['{PERCENT}','{PAGE}'],[$fb_view_content_percent,$url]);
            }
        }

        if ($fb_add_button_pixel){
            $html= insert_file_content_with_replace($html,'fbpxbuttonconversion.js','</head>','{EVENT}',$fb_thankyou_event);
        }
    }
	if ($black_land_log_conversions_on_button_click){
        $html= insert_file_content($html,'btnclicklog.js','</head>');
	}

    $html = insert_additional_scripts($html);

    //добавляем во все формы сабы
    $html = insert_subs_into_forms($html);
    //добавляем в формы id пикселя фб
    $html = insert_fbpixel_id($html);

    $html = fix_anchors($html);
    $html = replace_city_macros($html);
    //заменяем поле с телефоном на более удобный тип - tel
    $html = replace_tel_type($html);
    $html = insert_phone_mask($html);

    return $html;
}

//добавляем доп.скрипты
function insert_additional_scripts($html)
{
    global $disable_text_copy, $back_button_action, $replace_back_button, $replace_back_address, $add_tos;
    global $comebacker, $callbacker, $addedtocart;

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

    if ($callbacker){
        $html = insert_file_content($html,'callbacker/head.html','</head>');
        $html = insert_file_content($html,'callbacker/template.html','</body>');
    }

    if ($comebacker){
        $html = insert_file_content($html,'comebacker/head.html','</head>');
        $html = insert_file_content($html,'comebacker/template.html','</body>');
    }

    if ($addedtocart){
        $html = insert_file_content($html,'addedtocart/head.html','</head>');
        $html = insert_file_content($html,'addedtocart/template.html','</body>');
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

function replace_city_macros($html){
    $ip = getip();
    $html=preg_replace_callback('/\{CITY,([^\}]+)\}/',function ($m) use($ip){return getcity($ip,$m[1]);},$html);
    return $html;
}

function fix_anchors($html){
    $html = preg_replace('/(<a[^>]+href=")(#[^"]*)/', "\\1../\\2", $html);
    return $html;
}

function insert_phone_mask($html)
{
    global $black_land_use_phone_mask,$black_land_phone_mask;
	if (!$black_land_use_phone_mask) return $html;
	$domain = get_domain_with_prefix();
    $html = insert_before_tag($html, '</head>', "<script src=\"".$domain."/scripts/inputmask.js\"></script>");
    $html = insert_file_content_with_replace($html,'inputmaskbinding.js','</body>','{MASK}',$black_land_phone_mask);
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
        $html = insert_fbpixel_script($html, 'PageView');
    }

    //если на вайте есть форма, то меняем её обработчик, чтобы у вайта и блэка была одна thankyou page
    $html = preg_replace('/\saction=[\'\"]([^\'\"]+)[\'\"]/', " action=\"../worder.php?".http_build_query($_GET)."\"", $html);

    //добавляем в <head> пару доп. метатегов
    $html= str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);
    $html= remove_scrapbook($html);

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
    $html = insert_fbpixel_script($html, 'PageView');

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

//вставляет все сабы в hidden полях каждой формы
function insert_subs_into_forms($html)
{
    global $sub_ids;
    $all_subs = '';
    $preset=['subid','prelanding','landing'];
    foreach ($sub_ids as $sub) {
    	$key = $sub["name"];
        $value = $sub["rewrite"];

        if (in_array($key,$preset)&& isset($_COOKIE[$key])) {
            $html = preg_replace('/(<input[^>]*name="'.$value.'"[^>]*>)/', "", $html);
            $all_subs = $all_subs.'<input type="hidden" name="'.$value.'" value="'.$_COOKIE[$key].'"/>';
        } elseif (!empty($_GET[$key])) {
            $html = preg_replace('/(<input[^>]*name="'.$value.'"[^>]*>)/', "", $html);
            $all_subs = $all_subs.'<input type="hidden" name="'.$value.'" value="'.$_GET[$key].'"/>';
        }
    }
    if (!empty($all_subs)) {
        $needle = '<form';
        return insert_after_tag($html, $needle, $all_subs);
    }
}

//переписываем все относительные src и href (не начинающиеся с http или с //)
function rewrite_relative_urls($html,$url)
{
	$modified = preg_replace('/\ssrc=[\'\"](?!http|\/\/)([^\'\"]+)[\'\"]/', " src=\"$url\\1\"", $html);
	$modified = preg_replace('/\shref=[\'\"](?!http|#|\/\/)([^\'\"]+)[\'\"]/', " href=\"$url\\1\"", $modified);
	$modified = preg_replace('/background-image:\s*url\((?!http|#|\/\/)([^\)]+)\)/', "background-image: url($url\\1)", $modified);
	return $modified;
}

function remove_scrapbook($html){
	$modified = preg_replace('/data\-scrapbook\-source=[\'\"][^\'\"]+[\'\"]/', '', $html);
	$modified = preg_replace('/data\-scrapbook\-create=[\'\"][^\'\"]+[\'\"]/', '', $modified);
    return $modified;
}

function remove_from_html($html,$filename){
    $remove_file_name=__DIR__.'/scripts/'.$filename;
    if (!file_exists($remove_file_name)) {
        echo 'File Not Found '.$remove_file_name;
        return $html;
    }
    $modified=$html;
    foreach(file($remove_file_name) as $line){
        $linetype=substr(trim($line), -3);
        $l=trim($line);
        switch($linetype){
            case '.js':
                $r="/<script.*?".$l.".*?script>/";
                $modified=preg_replace($r,'',$modified);
                break;
            case 'css':
                $r="/<link.*?rel=[\'\"]stylesheet.*?".$l."[^>]*>/";
                $modified=preg_replace($r,'',$modified);
                break;
            default:
                $modified=str_replace(trim($line),'',$modified);
                break;
        }
    }
    return $modified;
}
?>