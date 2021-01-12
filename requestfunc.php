<?php
function get_prefix(){
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')  ? 'https://' : 'http://';
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
        $querystr = $_SERVER['QUERY_STRING'];
        if (!empty($querystr)) {
            $fullpath = $fullpath.'?'.$querystr;
        }
    }
    return $fullpath;
}

function get_request_headers(){
	$ip=getip();
    return array(
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
}

function get_html($url,$follow_location=false,$use_ua=false){
    $curl = curl_init();
    $optArray = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HTTPHEADER => get_request_headers()
			);
    //случай, когда у нас в форме ленда стоит в форме action=index.php, значит надо пробросить POST
    if ($_SERVER['REQUEST_METHOD']==='POST'){
        $optArray[CURLOPT_POST]=1;
        $optArray[CURLOPT_POSTFIELDS]=$_POST;
        $optArray[CURLOPT_FOLLOWLOCATION]=true ;
    }

    if ($follow_location===true){
        $optArray[CURLOPT_FOLLOWLOCATION]=true ;
    }
    if ($use_ua===true){
        $optArray[CURLOPT_USERAGENT]='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/84.0.4147.89 Safari/537.36';
    }
    curl_setopt_array($curl, $optArray);
    $html = curl_exec($curl);
    curl_close($curl);
    return $html;
}
?>