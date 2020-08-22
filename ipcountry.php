<?php
require_once __DIR__.'/bases/geoip2.phar';
use GeoIp2\Database\Reader;

function getip(){
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
	return $ipfound;
}

function getcountry($ip){
	$reader = new Reader(__DIR__.'/bases/GeoLite2-Country.mmdb');
    $record = $reader->country($ip);
	return $record->country->isoCode;
}

// AlexSloth ISP
function getisp($ip){
	$reader = new Reader(__DIR__.'/bases/GeoLite2-ASN.mmdb');
    $record = $reader->asn($ip);
	return $record->autonomousSystemOrganization;
}
?>