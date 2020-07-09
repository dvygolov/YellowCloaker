<?php
//Для отображение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации

include 'htmlprocessing.php';

$thankyoupage=file_get_contents('thankyou.html');
$thankyoupage=insert_gtm_script($thankyoupage);
$thankyoupage=insert_fb_pixel_script($thankyoupage,'Lead');
echo $thankyoupage;

