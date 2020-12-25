<?php
use Noodlehaus\Config;
require '../config/ConfigInterface.php';
require '../config/AbstractConfig.php';
require '../config/Config.php';
require '../config/Parser/ParserInterface.php';
require '../config/Parser/Json.php';
require '../config/ErrorException.php';
require '../config/Exception.php';
require '../config/Exception/ParseException.php';
require '../config/Exception/FileNotFoundException.php';

var_dump($_POST);
//$conf = new Config('../settings.json');

//var_dump($conf);
?>