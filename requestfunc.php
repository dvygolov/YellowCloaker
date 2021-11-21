<?php
require_once __DIR__.'/bases/ipcountry.php';
require_once __DIR__.'/url.php';
function get_prefix(){
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')  ? 'https://' : 'http://';
}

function get_port(){
    $curport = $_SERVER['SERVER_PORT'];
    if (isset($curport)&&$curport!=='80'&&$curport!=='443')
        return $curport;
	return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')  ? 443 : 80;
}

function get_domain_with_prefix(){
    $domain = $_SERVER['HTTP_HOST'];
    $prefix = get_prefix();
    return $prefix.$domain;
}

function get_abs_from_rel($url,$add_query_string=false){
    $domain = $_SERVER['HTTP_HOST'];
    $prefix = get_prefix();
    $fullpath= $prefix.$domain.'/'.$url;
    if (substr($url, -4) !== '.php') $fullpath=$fullpath.'/';
    if ($add_query_string===true)
    {
        $fullpath=add_querystring($fullpath);
    }
    return $fullpath;
}

function get_request_headers($ispost=false){
	$ip=getip();
    $headers=array(
				'X-YWBCLO-UIP: '.$ip,
				'X-FORWARDED-FOR '.$ip,
				//'CF-CONNECTING-IP: '.$ip,
				'FORWARDED-FOR: '.$ip,
				'X-COMING-FROM: '.$ip,
				'COMING-FROM: '.$ip,
				'FORWARDED-FOR-IP: '.$ip,
				'CLIENT-IP: '.$ip,
				'X-REAL-IP: '.$ip,
				'REMOTE-ADDR: '.$ip);
    if ($ispost)
        $headers[]="Content-Type: application/x-www-form-urlencoded";
    return $headers;
}

function get_html($url,$follow_location=false,$use_ua=false){
    $ispost=($_SERVER['REQUEST_METHOD']==='POST');

    $curl = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => get_request_headers($ispost)
			);
    if ($ispost){
        $optArray[CURLOPT_POST]=1;
        $optArray[CURLOPT_POSTFIELDS]=$_POST;
        $optArray[CURLOPT_FOLLOWLOCATION]=true;
    }

    if ($follow_location===true){
        $optArray[CURLOPT_FOLLOWLOCATION]=true ;
    }
    if ($use_ua===true){
        $optArray[CURLOPT_USERAGENT]='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36';
    }
    curl_setopt_array($curl, $optArray);
    $html = curl_exec($curl);
    $info = curl_getinfo($curl);
    $error= curl_error($curl);
    curl_close($curl);
    return $html;
}

function get($url){
    $curl = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => get_request_headers(false),
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_REFERER => $_SERVER['REQUEST_URI'],
            CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36'
			);
    curl_setopt_array($curl, $optArray);
    $content = curl_exec($curl);
    $info = curl_getinfo($curl);
    $error= curl_error($curl);
    curl_close($curl);
    return ["html"=>$content,"info"=>$info,"error"=>$error];
}

function post($url,$postfields){
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_POSTFIELDS => $postfields,
      CURLOPT_REFERER => $_SERVER['REQUEST_URI'],
      CURLOPT_HTTPHEADER => get_request_headers(true),
      CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36'
     ));

    $content = curl_exec($curl);
    $info = curl_getinfo($curl);
    $error= curl_error($curl);
    curl_close($curl);
    return ["html"=>$content,"info"=>$info,"error"=>$error];
}
?>