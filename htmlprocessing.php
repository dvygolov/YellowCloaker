<?php
//Подгрузка контента из другой папки через CURL
function load_content($url) {
	$domain = $_SERVER['HTTP_HOST'];
	$prefix = $_SERVER['HTTPS'] ? 'https://' : 'http://';
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

	//добавляем в страницу GTM
	$html = insert_gtm($html);
	//добавляем сабы
	$html = insert_subs($html);
	//добавляем пиксель фб
	$html = insert_fbpixel($html);

	return $html;
}

//если задан ID Google Tag Manager, то создаём его скрипт
function insert_gtm($html) {
	if (!file_exists('gtm.txt'))
		return $html;
	$gtm_id = file_get_contents('gtm.txt');
	if (empty($gtm_id))
		return $html;
	$needle = '</head>';
	$pos = strpos($html, $needle, 0);
	$gtm_text = "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
		new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
		j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
		'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
		})(window,document,'script','dataLayer','".$gtm_id."');</script>";
	$html = substr_replace($html, $gtm_text, $pos, 0);
	return $html;
}

//преобразует все utm метки в сабы (в hidden полях каждой формы)
function insert_subs($html) {
	$all_subs = '';
	if (!empty($_GET['utm_medium']))
		$all_subs = $all_subs.'<input type="hidden" name="sub1" value="'.$_GET['utm_medium'].'"/>';
	if (!empty($_GET['utm_source']))
		$all_subs = $all_subs.'<input type="hidden" name="sub2" value="'.$_GET['utm_source'].'"/>';
	if (!empty($_GET['utm_campaign']))
		$all_subs = $all_subs.'<input type="hidden" name="sub3" value="'.$_GET['utm_campaign'].'"/>';
	if (!empty($_GET['utm_content']))
		$all_subs = $all_subs.'<input type="hidden" name="sub4" value="'.$_GET['utm_content'].'"/>';
	if (!empty($_GET['utm_term']))
		$all_subs = $all_subs.'<input type="hidden" name="sub5" value="'.$_GET['utm_term'].'"/>';
	$needle = '</form>';
	$lastPos = 0;
	$positions = array();
	while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
		$positions[] = $lastPos;
		$lastPos = $lastPos + strlen($needle);
	}
	$positions = array_reverse($positions);

	foreach($positions as $pos) {
		$html = substr_replace($html, $all_subs, $pos, 0);
	}
	return $html;
}

//если в querystring есть id пикселя фб, то встраиваем его скрытым полем в форму на лендинге
//чтобы потом передать его на страницу "Спасибо" через send.php и там отстучать Lead
function insert_fbpixel($html) {
	$fbpixel_subname="fbpixel"; //имя параметра из querystring, в которой будет лежать ID пикселя
	
	if (empty($_GET[$fbpixel_subname]))
	{
		return $html;
	}
	$fb_pixel = $_GET[$fbpixel_subname];
	$fb_input = '<input type="hidden" name="'.$fbpixel_subname.'" value="'.$fb_pixel.'"/>';
	$needle = '</form>';
	$lastPos = 0;
	$positions = array();
	while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
		$positions[] = $lastPos;
		$lastPos = $lastPos + strlen($needle);
	}
	$positions = array_reverse($positions);

	foreach($positions as $pos) {
		if (!empty($fb_pixel)) {
			$html = substr_replace($html, $fb_input, $pos, 0);
		}
	}

	return $html;
}
?>