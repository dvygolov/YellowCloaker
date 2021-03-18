<?php
require_once __DIR__.'/geoip2.phar';
use GeoIp2\Database\Reader;

function getip(){
	if (!isset($ipfound)){
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
			//echo 'Cloud';
            $ipfound = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

	if (!isset($ipfound)){
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			//echo 'Client';
			$ipfound = $_SERVER['HTTP_CLIENT_IP'];
		} 
	}
	
	if(!isset($ipfound)){
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			//echo 'Forward';
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
			$ip=explode(", ", $ip);
			if(count($ip)<=1){$ip=explode(",", $ip[0]);}
			if(!empty($ip[0])){
				$ipfound=$ip[0];
			}
		}
	}
	
	if(!isset($ipfound)){
		if(isset($_SERVER['REMOTE_ADDR'])){
			//echo 'Remote';
			$ipfound=$_SERVER['REMOTE_ADDR'];
		}
	}
	
	if (!isset($ipfound))
		$ipfound='Unknown';
	if ($ipfound==='::1'||$ipfound==='127.0.0.1') $ipfound='31.177.76.70'; //for debugging
	return $ipfound;
}

function getcountry($ip=null){
	if (is_null($ip))
		$ip=getip();
	$reader = new Reader(__DIR__.'/GeoLite2-Country.mmdb');
	if ($ip==='::1'||$ip==='127.0.0.1') $ip='31.177.76.70'; //for debugging
    $record = $reader->country($ip);
	return $record->country->isoCode;
}

function getcity($ip,$locale){
	$reader = new Reader(__DIR__.'/GeoLite2-City.mmdb');
	if ($ip==='::1'||$ip==='127.0.0.1') $ip='31.177.76.70'; //for debugging
    $record = $reader->city($ip);
	if (array_key_exists($locale,$record->city->names))
        return $record->city->names[$locale];
	else
		return $record->city->name;
}

function getisp($ip){
	$reader = new Reader(__DIR__.'/GeoLite2-ASN.mmdb');
	if ($ip==='::1'||$ip==='127.0.0.1') $ip='31.177.76.70'; //for debugging
    $record = $reader->asn($ip);
	return $record->autonomousSystemOrganization;
}
?>