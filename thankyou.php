<?php
//Для отображение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации
include 'settings.php';
include 'htmlprocessing.php';
$filepath = __DIR__.'/thankyou/'.$thankyou_page_language.'.html';
if (!file_exists($filepath))
	$filepath = __DIR__.'/thankyou/EN.html';

$html = file_get_contents($filepath);
//добавляем в страницу скрипт GTM
$html = insert_gtm_script($html);
//добавляем в страницу скрипт Yandex Metrika
$html = insert_yandex_script($html);
$html = insert_fb_pixel_script($html,$fb_thankyou_event);

$search='{NAME}';
$html = str_replace($search,$_COOKIE['name'],$html);
$search='{PHONE}';
$html = str_replace($search,$_COOKIE['phone'],$html);

$needle = '</form>';
$str_to_insert = '<input type="hidden" name="name" value="'.$_COOKIE['name'].'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
$str_to_insert = '<input type="hidden" name="phone" value="'.$_COOKIE['phone'].'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
$str_to_insert = '<input type="hidden" name="subid" value="'.$_COOKIE['subid'].'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
$str_to_insert = '<input type="hidden" name="language" value="'.$thankyou_page_language.'"/>';
$html = insert_before_tag($html,$needle,$str_to_insert);
echo $html;