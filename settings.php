<?php
//Настройка вайта
//если 'site' - показывает локальный вайт-пейдж из папки $white_folder_name 
//если 'redirect' - редиректит на $redirect_url при помощи $redirect_type
//если 'curl' - подгружает $curl_url
//если 'error' - возвращает ошибку $error_code
$white_action = 'site'; 
$white_folder_name = 'white'; //папка, где лежит вайтпейдж
$redirect_url = 'http://ya.ru';
$redirect_type = 301; //можно использовать 301 или 302 редирект
$curl_url = 'https://ya.ru';
$error_code = 404; //код ошибки для возврата вместо вайта, по умолчанию 404 = Not Found

//Настройка скриптов
$gtm_id=''; //идентификатор Google Tag Manager
$ya_id=''; //идентификатор Яндекс.Метрики

//Настройки папок лендов-прокл
//папка, где лежит прокла или набор прокл через запятую (для сплит-теста), если проклы не нужны - ставим ''
$preland_folder_name = ''; 
//папка, где лежит основной ленд или набор папок через запятую (для сплит-теста)
$land_folder_name = 'land'; 

//Настройка TDS
$full_cloak_on = false; //если true, то всегда возвращает whitepage, используем при модерации
$os_white = 'Android,iOS,Windows'; //Список разрешённых ОС
$country_white = 'RU,RS'; //Строка двухбуквенных обозначений стран через запятую, допущенных к blackpage
$ip_black = '0.0.0.1'; //Доп. список адресов через запятую, которые будут отправлены на white page
$tokens_black = ''; //Список слов через запятую, при наличии которых в адресе перехода (в ссылке, по которой перешли), юзер будет отправлен на whitepage
$ua_black = 'facebook,Facebot,curl,gce-spider,yandex.com/bots'; //Список слова через запятую, при наличии которых в UserAgent, юзер будет отправлен на whitepage
$block_without_referer = false; //при =true все запросы без referer будут идти на whitepage
?>