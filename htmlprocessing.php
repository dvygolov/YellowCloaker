<?php
//Подгрузка контента блэк проклы из другой папки через CURL
function load_prelanding($url,$land_number) {
	global $fb_use_pageview, $replace_prelanding, $replace_prelanding_address;
	$domain = $_SERVER['HTTP_HOST'];
	$prefix = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
	$fullpath = $prefix.$domain.'/'.$url.'/';
	$querystr = $_SERVER['QUERY_STRING'];
	if (!empty($querystr))
		$fullpath = $fullpath.'?'.$querystr;
	
	$curl = curl_init();
	$optArray = array(
			CURLOPT_URL => $fullpath,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false, );
	curl_setopt_array($curl, $optArray);
	$html = curl_exec($curl);
	curl_close($curl);
	$baseurl = '/'.$url.'/';
	//переписываем все относительные src,href & action (не начинающиеся с http)
	$html = preg_replace('/\ssrc=[\'\"](?!http)([^\'\"]+)[\'\"]/', " src=\"$baseurl\\1\"", $html);
	$html = preg_replace('/\shref=[\'\"](?!http|#)([^\'\"]+)[\'\"]/', " href=\"$baseurl\\1\"", $html);
	$html = preg_replace('/\saction=[\'\"](?!http)([^\'\"]+)[\'\"]/', " action=\"$baseurl\\1\"", $html);

	//добавляем в страницу скрипт GTM
	$html = insert_gtm_script($html);
	//добавляем в страницу скрипт Yandex Metrika
	$html = insert_yandex_script($html);
	//добавляем в страницу скрипт Facebook Pixel с событием PageView
	if ($fb_use_pageview)
		$html = insert_fb_pixel_script($html,'PageView');

	$html = replace_tel_type($html);
	
	//добавляем во все формы сабы
	$html = insert_subs($html);
	//добавляем в формы id пикселя фб
	$html = insert_fbpixel_id($html);
	
		
	//замена всех ссылок на прокле на универсальную ссылку ленда landing.php
	$replacement = "\\1".$prefix.$domain.'/landing.php?l='.$land_number.(!empty($querystr)?'&'.$querystr:'');
	
	//если мы будем подменять преленд при переходе на ленд, то ленд надо открывать в новом окне
	if ($replace_prelanding){ 
		$replacement=$replacement.'" target="_blank"';
		$url = replace_all_macros($replace_prelanding_address); //заменяем макросы
		$url = add_subs_to_link($url); //добавляем сабы
		$html = insert_file_content_with_replace($html,'replaceprelanding.js','</body>','{REDIRECT}',$url);
	}
	$html = preg_replace('/(<a[^>]+href=")([^"]*)/', $replacement, $html);

	$html = insert_additional_scripts($html);
	
	return $html;
}

//Подгрузка контента блэк ленда из другой папки через CURL
function load_landing($url) {
	global $fb_use_pageview,$black_land_use_phone_mask;
	$domain = $_SERVER['HTTP_HOST'];
	$prefix = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
	$fullpath = $prefix.$domain.'/'.$url.'/';
	$querystr = $_SERVER['QUERY_STRING'];
	if (!empty($querystr))
		$fullpath = $fullpath.'?'.$querystr;
	
	$curl = curl_init();
	$optArray = array(
			CURLOPT_URL => $fullpath,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false, );
	curl_setopt_array($curl, $optArray);
	$html = curl_exec($curl);
	curl_close($curl);
	$baseurl = '/'.$url.'/';
	//переписываем все относительные src,href & action (не начинающиеся с http)
	$html = preg_replace('/\ssrc=[\'\"](?!http)([^\'\"]+)[\'\"]/', " src=\"$baseurl\\1\"", $html);
	$html = preg_replace('/\shref=[\'\"](?!http|#)([^\'\"]+)[\'\"]/', " href=\"$baseurl\\1\"", $html);
	
	//меняем обработчик формы, чтобы у вайта и блэка была одна thankyou page
	$html = preg_replace('/\saction=[\'\"]([^\'\"]+)[\'\"]/', " action=\"../send.php?".http_build_query($_GET)."\"", $html);

	//добавляем в страницу скрипт GTM
	$html = insert_gtm_script($html);
	//добавляем в страницу скрипт Yandex Metrika
	$html = insert_yandex_script($html);
	//добавляем в страницу скрипт Facebook Pixel с событием PageView
	if ($fb_use_pageview)
		$html = insert_fb_pixel_script($html,'PageView');
	
	$html = insert_additional_scripts($html);
	
	//добавляем во все формы сабы
	$html = insert_subs($html);
	//добавляем в формы id пикселя фб
	$html = insert_fbpixel_id($html);
	
	//заменяем поле с телефоном на более удобный тип - tel
	$html = replace_tel_type($html);
	if ($black_land_use_phone_mask)
		$html = insert_phone_mask($html);
		
	return $html;
}

//добавляем доп.скрипты
function insert_additional_scripts($html){
	global $disable_text_copy, $disable_back_button, $replace_back_button, $replace_back_address, $add_tos;

	if($disable_text_copy)
		$html = insert_file_content($html,'disablecopy.js','</body>');
	
	if($disable_back_button)
		$html = insert_file_content($html,'disableback.js','</body>');
	
	if ($replace_back_button){
		$url= replace_all_macros($replace_back_address); //заменяем макросы
		$url = add_subs_to_link($url); //добавляем сабы
		$html = insert_file_content_with_replace($html,'replaceback.js','</body>','{RA}',$url);
	}
	
	if ($add_tos){
		$html = insert_file_content($html,'tos.html','</body>');
	}
	return $html;
}

//если тип поля телефона - text, меняем его на tel для более удобного ввода с мобильных
function replace_tel_type($html){
	$html = preg_replace('/(<input[^>]*name="(phone|tel)"[^>]*type=")(text)("[^>]*>)/', "\\1tel\\4", $html);
	$html = preg_replace('/(<input[^>]*type=")(text)("[^>]*name="(phone|tel)"[^>]*>)/', "\\1tel\\3", $html);
	return $html;
}

function insert_phone_mask($html){
	global $black_land_phone_mask;
	$html = insert_before_tag($html,'</head>','<script src="scripts/inputmask.js"></script>');
	$html = insert_before_tag($html,'</head>','<script src="scripts/inputmaskbinding.js"></script>');
	$html = preg_replace('/(<input[^>]*name="(phone|tel)"[^>]*)(>)/', 
		"\\1 data-inputmask=\"'mask': '".$black_land_phone_mask."'\">", $html);
	
	return $html;
}

//Подгрузка контента вайта ИЗ ПАПКИ
function load_white_content($url) {
	global $fb_use_pageview;
	$domain = $_SERVER['HTTP_HOST'];
	$prefix = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
	$fullpath = $prefix.$domain.'/'.$url.'/';
	$querystr = $_SERVER['QUERY_STRING'];
	if (!empty($querystr))
		$fullpath = $fullpath.'?'.$querystr;
	
	$curl = curl_init();
	$optArray = array(
			CURLOPT_URL => $fullpath,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false, );
	curl_setopt_array($curl, $optArray);
	$html = curl_exec($curl);
	curl_close($curl);
	$baseurl = '/'.$url.'/';
	//переписываем все относительные src и href (не начинающиеся с http)
	$html = preg_replace('/\ssrc=[\'\"](?!http)([^\'\"]+)[\'\"]/', " src=\"$baseurl\\1\"", $html);
	$html = preg_replace('/\shref=[\'\"](?!http|#)([^\'\"]+)[\'\"]/', " href=\"$baseurl\\1\"", $html);

	//добавляем в страницу скрипт GTM
	$html = insert_gtm_script($html);
	//добавляем в страницу скрипт Yandex Metrika
	$html = insert_yandex_script($html);
	//добавляем в страницу скрипт Facebook Pixel с событием PageView
	if ($fb_use_pageview)
		$html = insert_fb_pixel_script($html,'PageView');
	
	//если на вайте есть форма, то меняем её обработчик, чтобы у вайта и блэка была одна thankyou page
	$html = preg_replace('/\saction=[\'\"]([^\'\"]+)[\'\"]/', " action=\"../worder.php?".http_build_query($_GET)."\"", $html);
	
	//добавляем в <head> пару доп. метатегов
	$html= str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);

	return $html;
}

//когда подгружаем вайт методом CURL
function load_white_curl($url){
	$curl = curl_init();
	$optArray = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false, 
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36');
	curl_setopt_array($curl, $optArray);
	$html = curl_exec($curl);
	curl_close($curl);
	//переписываем все относительные src и href (не начинающиеся с http)
	$html = preg_replace('/\ssrc=[\'\"](?!http)([^\'\"]+)[\'\"]/', " src=\"$url\\1\"", $html);
	$html = preg_replace('/\shref=[\'\"](?!http|#)([^\'\"]+)[\'\"]/', " href=\"$url\\1\"", $html);

	//удаляем лишние палящие теги
	$html = preg_replace('/(<meta property=\"og:url\" [^>]+>)/', "", $html);
	$html = preg_replace('/(<link rel=\"canonical\" [^>]+>)/', "", $html);

	//добавляем в страницу скрипт Facebook Pixel
	$html = insert_fb_pixel_script($html,'PageView');
	
	//добавляем в <head> пару доп. метатегов
	$html= str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);
	return $html;	
}

function add_subs_to_link($url){
	global $sub_ids;
	foreach ($sub_ids as $key => $value){
		$delimiter= strpos($url,'?')===false?'?':'&';
		if ($key=='subid' && isset($_COOKIE['subid']))
			$url.= $delimiter.$value.'='.$_COOKIE['subid'];			
		else if ($key=='prelanding' && isset($_COOKIE['prelanding']))
			$url.= $delimiter.$value.'='.$_COOKIE['prelanding'];			
		else if ($key=='landing' && isset($_COOKIE['landing']))
			$url.= $delimiter.$value.'='.$_COOKIE['landing'];			
		else if (!empty($_GET[$key]))
			$url.= $delimiter.$value.'='.$_GET[$key];
	}
	return $url;
}

//вставляет все сабы в hidden полях каждой формы
function insert_subs($html) {
	global $sub_ids;
	$all_subs = '';
	foreach ($sub_ids as $key => $value){
		if ($key=='subid' && isset($_COOKIE['subid']))
			$all_subs = $all_subs.'<input type="hidden" name="'.$value.'" value="'.$_COOKIE['subid'].'"/>';			
		else if ($key=='prelanding' && isset($_COOKIE['prelanding']))
			$all_subs = $all_subs.'<input type="hidden" name="'.$value.'" value="'.$_COOKIE['prelanding'].'"/>';
		else if ($key=='landing' && isset($_COOKIE['landing']))
			$all_subs = $all_subs.'<input type="hidden" name="'.$value.'" value="'.$_COOKIE['landing'].'"/>';
		else if (!empty($_GET[$key]))
			$all_subs = $all_subs.'<input type="hidden" name="'.$value.'" value="'.$_GET[$key].'"/>';
	}
	$needle = '</form>';
	return insert_before_tag($html,$needle,$all_subs);
}

//если в querystring есть id пикселя фб, то встраиваем его скрытым полем в форму на лендинге
//чтобы потом передать его на страницу "Спасибо" через send.php и там отстучать Lead
function insert_fbpixel_id($html) {
	$fbpixel_subname="px"; //имя параметра из querystring, в которой будет лежать ID пикселя
	$fb_pixel = isset($_GET[$fbpixel_subname])?$_GET[$fbpixel_subname]:'';
	if (empty($fb_pixel)) return $html;
	$fb_input = '<input type="hidden" name="'.$fbpixel_subname.'" value="'.$fb_pixel.'"/>';
	$needle = '</form>';
	return insert_before_tag($html,$needle,$fb_input);
}

//вставляет в head полный код пикселя фб с указанным в $event событим (Lead,PageView,Purchase итп)
function insert_fb_pixel_script($html,$event){
	//имя параметра из querystring, в которой будет лежать ID пикселя
	$fbpixel_subname="px"; 
	//если пиксель не лежит в querystring, то также ищем его в куки
	$fb_pixel = isset($_GET[$fbpixel_subname])?$_GET[$fbpixel_subname]:(isset($_COOKIE[$fbpixel_subname])?$_COOKIE[$fbpixel_subname]:'');
	if (empty($fb_pixel)) return $html;
	$file_name=__DIR__.'/scripts/fbpxcode.js';
	if (!file_exists($file_name)) return $html;
	$px_code = file_get_contents($file_name);	
	if (empty($px_code)) return $html;

	$search='{PIXELID}';
	$px_code = str_replace($search,$fb_pixel,$px_code);
	$search='{EVENT}';
	$px_code = str_replace($search,$event,$px_code);

	$needle='</head>';
	return insert_before_tag($html,$needle,$px_code);
}

//если задан ID Google Tag Manager, то вставляем его скрипт
function insert_gtm_script($html) {
	global $gtm_id;
	if ($gtm_id=='' || empty($gtm_id)) return $html;

	$code_file_name=__DIR__.'/scripts/gtmcode.js';
	if (!file_exists($code_file_name)) return $html;
	$gtm_code = file_get_contents($code_file_name);
	if (empty($gtm_code))	return $html;

	$search = '{GTMID}';
	$gtm_code = str_replace($search,$gtm_id,$gtm_code);
	$needle='</head>';
	return insert_before_tag($html,$needle,$gtm_code);
}

//если задан ID Yandex Metrika, то вставляем её скрипт
function insert_yandex_script($html) {
	global $ya_id;
	if ($ya_id=='' || empty($ya_id)) return $html;
	
	$code_file_name=__DIR__.'/scripts/yacode.js';
	if (!file_exists($code_file_name)) return $html;
	$ya_code = file_get_contents($code_file_name);
	if (empty($ya_code))	return $html;

	$search = '{YAID}';
	$ya_code = str_replace($search,$ya_id,$ya_code);
	$needle='</head>';
	return insert_before_tag($html,$needle,$ya_code);
}

//заменяем все макросы на реальные значения из куки
function replace_all_macros($url){
	$fbpixel_subname='px';
	$px=isset($_GET[$fbpixel_subname])?$_GET[$fbpixel_subname]:(isset($_COOKIE[$fbpixel_subname])?$_COOKIE[$fbpixel_subname]:'');
	$prelanding = isset($_COOKIE['prelanding'])?$_COOKIE['prelanding']:'';
	$subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';
	
	$tmp_url = str_replace('{px}', $px, $url);
	$tmp_url = str_replace('{prelanding}', $prelanding, $tmp_url);
	$tmp_url = str_replace('{subid}', $subid, $tmp_url);
	return $tmp_url;
}

function insert_file_content_with_replace($html,$scriptname,$needle,$search,$replacement) {
	$code_file_name=__DIR__.'/scripts/'.$scriptname;
	if (!file_exists($code_file_name)) {
		echo 'File Not Found '.$code_file_name;
		return $html;
	}
	$script_code = file_get_contents($code_file_name);
	$script_code = str_replace($search,$replacement,$script_code);
	return insert_before_tag($html,$needle,$script_code);
}

function insert_file_content($html,$scriptname,$needle) {
	$code_file_name=__DIR__.'/scripts/'.$scriptname;
	if (!file_exists($code_file_name)) {
		echo 'File Not Found '.$code_file_name;
		return $html;
	}
	$script_code = file_get_contents($code_file_name);
	return insert_before_tag($html,$needle,$script_code);
}


function insert_before_tag($html,$needle,$str_to_insert){
	$lastPos = 0;
	$positions = array();
	while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
		$positions[] = $lastPos;
		$lastPos = $lastPos + strlen($needle);
	}
	$positions = array_reverse($positions);

	foreach($positions as $pos) {
		$html = substr_replace($html, $str_to_insert, $pos, 0);
	}
	return $html;
}
?>