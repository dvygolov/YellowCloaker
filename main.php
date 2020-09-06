<?php
include 'htmlprocessing.php';

function white(){
	global $white_action,$white_folder_name,$white_redirect_url,$white_redirect_type,$white_curl_url,$white_error_code,$white_use_domain_specific,$white_domain_specific;
	
	$action = $white_action;
	$folder_name= $white_folder_name;
	$redirect_url= $white_redirect_url;
	$curl_url= $white_curl_url;
	$error_code= $white_error_code;
	
	if ($white_use_domain_specific){ //если у нас под каждый домен свой вайт
		$curdomain = $_SERVER['SERVER_NAME'];
		foreach ($white_domain_specific as $domain => $what_to_do){
			if ($domain==$curdomain){
				$wtd_arr = explode(":",$what_to_do,2);
				$action = $wtd_arr[0];
				switch ($action){
					case 'error':
						$error_code= intval($wtd_arr[1]);
						break;
					case 'site':
						$folder_name = $wtd_arr[1];
						break;
					case 'curl':
						$curl_url = $wtd_arr[1];
						break;
					case 'redirect':
						$redirect_url = $wtd_arr[1];
						break;
				}
				break;
			}
		}
	}
	
	switch($action){
		case 'error':
  	        http_response_code($error_code);
    		break;
		case 'site':
			echo load_white_content($folder_name);
			break;
		case 'curl':
			echo load_white_curl($curl_url);
			break;
		case 'redirect':
			if ($white_redirect_type==302){
				header('Location: '.$redirect_url);
				exit;
			}
			else{
				header('Location: '.$redirect_url, true, $white_redirect_type);
				exit;
			}
			break;
	}
	return;
}

function black($clkrdetect, $clkrresult, $check_result){
	global $black_action,$black_redirect_type, $black_redirect_url,$black_preland_folder_name,$black_land_folder_name,$save_user_flow;
    
    header('Access-Control-Allow-Credentials: true');
    if (isset($_SERVER['HTTP_REFERER'])){
        $parsed_url=parse_url($_SERVER['HTTP_REFERER']);
        header('Access-Control-Allow-Origin: '.$parsed_url['scheme'].'://'.$parsed_url['host']);
    }
    
	$cookietime=time()+60*60*24*5; //время, на которое ставятся куки, по умолчанию - 5 дней
	//устанавливаем пользователю в куки уникальный subid, либо берём его из куки, если он уже есть
	$cursubid=isset($_COOKIE['subid'])?$_COOKIE['subid']:uniqid();
	ywbsetcookie('subid',$cursubid,$cookietime,'/');

	//устанавливаем fbclid в куки, если получали его из фб
	if (isset($_GET['fbclid']) && $_GET['fbclid']!='')
		ywbsetcookie('fbclid',$_GET['fbclid'],$cookietime,'/');
	
	switch($black_action){
		case 'site':
			//если мы используем прокладки
			if ($black_preland_folder_name!='')
			{
				$prelanding='';
				if ($save_user_flow && isset($_COOKIE['prelanding'])){
					$prelanding = $_COOKIE['prelanding'];
				}
				else{
					//A-B тестирование прокладок
					$prelandings = explode(",", $black_preland_folder_name);
					$r = rand(0, count($prelandings) - 1);
					$prelanding = $prelandings[$r];
					ywbsetcookie('prelanding',$prelanding,$cookietime,'/');
				}
				
				$landing='';
				$t=0;
				$landings = explode(",", $black_land_folder_name);
				if ($save_user_flow && isset($_COOKIE['landing'])){
					$landing = $_COOKIE['landing'];
					$t = array_search($landing,$landings);
				}
				else{
					//A-B тестирование лендингов
					$t = rand(0, count($landings) - 1);
					$landing = $landings[$t];
					ywbsetcookie('landing',$landing,$cookietime,'/');
				}
			
				echo load_prelanding($prelanding,$t);
				write_black_to_log($cursubid,$clkrdetect,$clkrresult,$check_result,$prelanding,$landing);
			}
			else //если у нас только ленды без прокл
			{ 
				//A-B тестирование лендингов
				$landings = explode(",", $black_land_folder_name);
				$r = rand(0, count($landings) - 1);
				ywbsetcookie('landing',$landings[$r],$cookietime,'/');
				
				echo load_landing($landings[$r]);
				write_black_to_log($cursubid,$clkrdetect,$clkrresult,$check_result,'',$landings[$r]);
			}	
			break;
		case 'redirect':
			write_black_to_log($cursubid,$clkrdetect,$clkrresult,$check_result,'',$black_redirect_url);
			if ($black_redirect_type==302){
				header('Location: '.$black_redirect_url);
			}
			else{
				header('Location: '.$black_redirect_url, true, $black_redirect_type);
			}
			break;
	}
    return;
}

function ywbsetcookie($name,$value,$expires,$path){
	header("Set-Cookie: {$name}={$value}; Expires={$expires}; Path={$path}; SameSite=None; Secure");
}


?>