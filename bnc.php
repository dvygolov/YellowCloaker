<?php 
require 'vendor/sinergi/browser-detector/src/DetectorInterface.php';
require 'vendor/sinergi/browser-detector/src/UserAgent.php';
require 'vendor/sinergi/browser-detector/src/Os.php';
require 'vendor/sinergi/browser-detector/src/OsDetector.php';
use Sinergi\BrowserDetector\Os;

class Cloacker{
	var $os_white;
	var $country_white;
	var $tokens_black;
	var $ua_black;
	var $ip_black;
	var $referer;
	var $is_bot;

	function __construct(){
		$this->detect();
	}

	public function check(){
		$result=0;
		
		$os_white_checker = stristr($this->os_white, $this->detect['os']);
		$country_white_checker = stristr($this->country_white, $this->detect['country']);
		$current_ip=$this->detect['ip'];
		$bot_ips=file_get_contents("bots.dat");
		$ip_black_checker=  stristr($bot_ips, $current_ip);
		
		if (!empty($ip_black_checker))
		{
			$is_bot=1;
		}
		
		if(!empty($this->ua_black))
		{
			$ua=$this->detect['ua'];
			$ua_black_list=explode(',',$this->ua_black);
			foreach($ua_black_list as $ua_black_single){
				if(!empty(stristr($ua,$ua_black_single))){
					$result=1;
					$this->result[]='ua';
					break;
				}
			}
		}
		
		if(empty($ip_black_checker)&& !empty($this->ip_black))
		{
			$ip_black_checker=  stristr($this->ip_black, $current_ip);
		}

		if(!empty($this->os_white) && empty($os_white_checker) ){
			$result=1;
			$this->result[]='os';
		}
		
		if(!empty($this->country_white) && empty($country_white_checker) ){
			$result=1;
			$this->result[]='country';
		}
		
		if(!empty($ip_black_checker) ){
			$result=1;
			$this->result[]='ip';
		}
		
		if(!empty($this->referer) && (int)$this->referer==1 && (int)$this->detect['referer']==0){
			$result=1;
			$this->result[]='referer';
		}
		
		if(!empty($this->tokens_black)){
			$this->tokens_black=explode(',',$this->tokens_black);
			foreach($_GET AS $token){
				if(empty($token)){$token='Unknown';}
				if (in_array($token, $this->tokens_black)) {
					$result=1;
					$this->result[]='token';
				}
			}
		}
		return $result;
	}

	public function detect(){
		$a['os']='Unknown';
		$a['country']='Unknown';
		if(isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])){
			$a['referer']=1;
		}
		else{
			$a['referer']=0;
		}

		$os = new Os();
	    $a['os']=$os->getName();
		$a['ip'] = $this->getip();
		$a['ua']=$_SERVER['HTTP_USER_AGENT'];
		include("geoip.inc");
		$g = geoip_open("GeoIP.dat", GEOIP_STANDARD);
		$a['country'] = geoip_country_code_by_addr($g, $a['ip']);
		geoip_close($g);
		$this->detect=$a;
	}

	public function getip(){
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
}
?>