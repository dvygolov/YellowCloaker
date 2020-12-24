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
?>