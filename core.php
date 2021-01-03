<?php 
require 'bases/browser/DetectorInterface.php';
require 'bases/browser/UserAgent.php';
require 'bases/browser/Os.php';
require 'bases/browser/OsDetector.php';
require 'bases/iputils.php';
require 'ipcountry.php';
use Sinergi\BrowserDetector\Os;

class Cloaker{
	var $os_white;
	var $country_white;
	var $tokens_black;
	var $ua_black;
	var $ip_black;
	var $block_without_referer;
    var $isp_black;
    var $result;
	
	public function __construct($os_white,$country_white,$ip_black,$tokens_black,$ua_black,$isp_black,$block_without_referer){
		$this->os_white = $os_white;
		$this->country_white = $country_white;
		$this->ip_black = $ip_black;
		$this->tokens_black = $tokens_black;
		$this->ua_black = $ua_black;
		$this->isp_black = $isp_black;
		$this->block_without_referer = $block_without_referer;
		$this->detect();
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
	
	public function check(){
		$result=0;
		
		$os_white_checker = in_array($this->detect['os'],$this->os_white);
		$country_white_checker = in_array($this->detect['country'],$this->country_white);
		$current_ip=$this->detect['ip'];
		
		$cidr = file(__DIR__."/bases/bots.txt", FILE_IGNORE_NEW_LINES);
		$checked=IpUtils::checkIp($current_ip, $cidr);
		if($this->ua_black!=[])
		{
			$ua=$this->detect['ua'];
			foreach($this->ua_black as $ua_black_single){
				if(!empty(stristr($ua,$ua_black_single))){
					$result=1;
					$this->result[]='ua';
				}
			}
		}
		
		if(!$checked && !empty($this->ip_black))
		{
			$ip_black_checker=  in_array($current_ip, $this->ip_black);
			if($ip_black_checker===true){
				$result=1;
				$this->result[]='ip';
			}
		}

		if(!empty($this->os_white) && $os_white_checker===false){
			$result=1;
			$this->result[]='os';
		}
		
		if($this->country_white!=[] && 
			in_array('WW',$this->country_white)===false && 
			$country_white_checker===false){
			$result=1;
			$this->result[]='country';
		}
		

		
		if($this->block_without_referer===true && (int)$this->detect['referer']==0){
			$result=1;
			$this->result[]='referer';
		}
		
		if(!empty($this->tokens_black)){
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
			foreach($this->isp_black as $isp_black_single){
				if(!empty(stristr($isp,$isp_black_single))){
					$result=1;
					$this->result[]='isp';
				}
			}
		}
		
		return $result;
	}
}
?>