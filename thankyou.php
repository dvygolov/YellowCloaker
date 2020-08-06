<?php
//Для отображение отладочной информации
ini_set('display_errors','1'); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);
//Конец включения отладочной информации
include 'settings.php';
include 'htmlprocessing.php';

$html=file_get_contents($thankyou_page_path);
//добавляем в страницу скрипт GTM
$html = insert_gtm_script($html);
//добавляем в страницу скрипт Yandex Metrika
$html = insert_yandex_script($html);
$html = insert_fb_pixel_script($html,$fb_thankyou_event);

//заменяем на странице фио, телефон и subid (для формы сбора мыла и для отображение их на странице пользователю для проверки)
$search='{NAME}';
$html = str_replace($search,$_COOKIE['name'],$html);
$search='{PHONE}';
$html = str_replace($search,$_COOKIE['phone'],$html);
$search='{SUBID}';
$html = str_replace($search,$_COOKIE['subid'],$html);
echo $html;