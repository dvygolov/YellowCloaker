<?php
use Noodlehaus\Config;
use Noodlehaus\Writer\Json;
require_once __DIR__.'/../config/ConfigInterface.php';
require_once __DIR__.'/../config/AbstractConfig.php';
require_once __DIR__.'/../config/Config.php';
require_once __DIR__.'/../config/Parser/ParserInterface.php';
require_once __DIR__.'/../config/Parser/Json.php';
require_once __DIR__.'/../config/Writer/WriterInterface.php';
require_once __DIR__.'/../config/Writer/AbstractWriter.php';
require_once __DIR__.'/../config/Writer/Json.php';
require_once __DIR__.'/../config/ErrorException.php';
require_once __DIR__.'/../config/Exception.php';
require_once __DIR__.'/../config/Exception/ParseException.php';
require_once __DIR__.'/../config/Exception/FileNotFoundException.php';
require_once __DIR__.'/../redirect.php';
require_once __DIR__.'/password.php';
check_password();

$conf = new Config(__DIR__.'/../settings.json');
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
$conf->toFile(__DIR__.'/../settings.json',new Json());
redirect('editsettings.php?password='.$log_password,302,false);
?>