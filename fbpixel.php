<?php
include_once 'settings.php';

function getpixel(){
	global $fbpixel_subname;
    //если пиксель не лежит в querystring, то также ищем его в куки
    $fb_pixel = isset($_GET[$fbpixel_subname])?$_GET[$fbpixel_subname]:(isset($_COOKIE[$fbpixel_subname])?$_COOKIE[$fbpixel_subname]:'');
	return $fb_pixel;
}
?>