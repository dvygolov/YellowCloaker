<?php
//ini_set('display_errors','1'); //Для отображение отладочной информации
require 'bnc.php';
include 'htmlprocessing.php';
include 'logging.php';
$cloacker = new Cloacker();

//Тут начинаются настройки кло
$white_folder_name = 'white'; //папка, где лежит вайтпейдж
$black_folder_name = 'land1'; //папка, где лежит основной ленд или набор папок через запятую (для сплит-теста)
$full_cloak_on = 0; //если 1, то всегда возвращает whitepage, используем при модерации
$cloacker->os_white = 'Android,iOS,Windows,Linux'; //Список разрешённых ОС
$cloacker->country_white = 'RU'; //Строка двухбуквенных обозначений стран через запятую, допущенных к blackpage
$cloacker->ip_black = '0.0.0.1'; //Доп. список адресов через запятую, которые будут отправлены на white page
$cloacker->tokens_black = ''; //Список слов через запятую, при наличии которых в адресе перехода (в ссылке, по которой перешли), юзер будет отправлен на whitepage
$cloacker->ua_black = 'facebook,Facebot,curl,gce-spider,yandex.com/bots'; //Список слова через запятую, при наличии которых в UserAgent, юзер будет отправлен на whitepage
$cloacker->referer = '0'; //при ='1' все запросы без referer будут идти на whitepage
//Тут заканчиваются настройки кло

//Проверяем зашедшего пользователя
$check_result = $cloacker->check();
//запись посетителей в файл visitors.csv
write_visitors_to_log();

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on == 1) {
	echo load_content($white_folder_name);
	return;
}

if ($check_result == 0) //Обычный юзверь
{
	//A/B тестирование лендингов
	//TODO:добавить при тестировании проброс суб-метки с номером ленда
	$landings = explode(",", $black_folder_name);
	$r = rand(0, count($landings) - 1);
	echo load_content($landings[$r]);
} else //Обнаружили бота или модера
{
	echo load_content($white_folder_name);
}
?>