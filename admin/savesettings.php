<?php
use Noodlehaus\Config;
use Noodlehaus\Writer\Json;
require_once '../config/ConfigInterface.php';
require_once '../config/AbstractConfig.php';
require_once '../config/Config.php';
require_once '../config/Parser/ParserInterface.php';
require_once '../config/Parser/Json.php';
require_once '../config/Writer/WriterInterface.php';
require_once '../config/Writer/AbstractWriter.php';
require_once '../config/Writer/Json.php';
require_once '../config/ErrorException.php';
require_once '../config/Exception.php';
require_once '../config/Exception/ParseException.php';
require_once '../config/Exception/FileNotFoundException.php';
require_once '../redirect.php';

//var_dump($_POST);
$conf = new Config('../settings.json');
foreach($_POST as $key=>$value){
    $confkey=str_replace('_','.',$key);
    if (is_string($value)&&strpos($value,',')!==false){
        $value=explode(',',$value);
    }
    else if (is_string($value)&&($value==='false'|| $value==='true'))
        $value=filter_var($value,FILTER_VALIDATE_BOOLEAN);

    $conf[$confkey]=$value;
}
$conf->toFile('../settings.json',new Json());
require_once('../settings.php');
redirect('settings.php?password='.$log_password,302);

?>