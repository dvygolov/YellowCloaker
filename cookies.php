<?php
function ywbsetcookie($name,$value,$path){
	$expires = time()+60*60*24*5; //время, на которое ставятся куки, по умолчанию - 5 дней
	header("Set-Cookie: {$name}={$value}; Expires={$expires}; Path={$path}; SameSite=None; Secure",false);
}

function set_subid(){
    //устанавливаем пользователю в куки уникальный subid, либо берём его из куки, если он уже есть
    $cursubid=isset($_COOKIE['subid'])?$_COOKIE['subid']:uniqid();
    ywbsetcookie('subid',$cursubid,'/');
    return $cursubid;
}

//проверяем, если у пользователя установлена куки, что он уже конвертился, а также имя и телефон, то сверяем время
//если прошло менее суток, то хуй ему, а не лид, обнуляем время
function has_conversion_cookie($name,$phone){
	$date = new DateTime();
	$ts = $date->getTimestamp();
	$is_duplicate=false;
	if (!empty($_COOKIE['ctime'])&&!empty($_COOKIE['name'])&&!empty($_COOKIE['phone'])){
		$cname = $_COOKIE['name'];
		$cphone = $_COOKIE['phone'];
		$ctime = $_COOKIE['ctime'];
		if ($cname===$name&&$cphone===$phone){
			$hourdiff = round((strtotime($ts) - strtotime($ctime))/3600, 1);
			if ($hourdiff<24)
			{
				$is_duplicate=true;
				ywbsetcookie('ctime',$ts,'/');
			}
		}
	}
	return $is_duplicate;
}
?>