<?php 
require 'bases/browser/DetectorInterface.php';
require 'bases/browser/UserAgent.php';
require 'bases/browser/Os.php';
require 'bases/browser/OsDetector.php';
require 'ipcountry.php';
require 'bases/iputils.php';

use Sinergi\BrowserDetector\Os;

class Cloacker{
	var $os_white;
	var $country_white;
	var $tokens_black;
	var $ua_black;
	var $ip_black;
	var $block_without_referer;
    var $isp_black;
    var $result;

	function __construct(){
		$this->detect();
	}

	public function check(){
		$result=0;
		
		$os_white_checker = stristr($this->os_white, $this->detect['os']);
		$country_white_checker = stristr($this->country_white, $this->detect['country']);
		$current_ip=$this->detect['ip'];
		
		$cidr = file(__DIR__."/bases/bots.txt", FILE_IGNORE_NEW_LINES);
		$checked=IpUtils::checkIp($current_ip, $cidr);
		if ($checked)
		{
			$ip_black_checker=1;
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
		
		if($this->block_without_referer===true && (int)$this->detect['referer']==0){
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
		
		if(!empty($this->isp_black))
		{
			$isp=$this->detect['isp'];
			$isp_black_list=explode(',',$this->isp_black);
			
			foreach($isp_black_list as $isp_black_single){
				if(!empty(stristr($isp,$isp_black_single))){
					$result=1;
					$this->result[]='isp';
					break;
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
		$a['ip'] = getip();
		$a['ua']=isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:'Not Found!';
		$a['country'] = getcountry($a['ip']);
		$a['isp'] = getisp($a['ip']);
		$this->detect=$a;
	}
}
?>