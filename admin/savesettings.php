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

require_once 'password.php';
check_password();

$conf = new Config('../settings.json');
foreach($_POST as $key=>$value){
    $confkey=str_replace('_','.',$key);
    if (is_string($value)&&is_array($conf[$confkey])){
        if ($value===''){
            $value=[];
        }
        else{
            $value=explode(',',$value);
        }
        $conf[$confkey]=$value;
    }
    else if ($value==='false'|| $value==='true'){
        $value=filter_var($value,FILTER_VALIDATE_BOOLEAN);
        $conf[$confkey]=$value;
    }
    else{
        $conf[$confkey]=$value;
    }

}
$conf->toFile('../settings.json',new Json());
redirect('editsettings.php?password='.$log_password,302,false);
?>