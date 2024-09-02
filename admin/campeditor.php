<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db.php';

$passOk = check_password(false);
if (!$passOk)
    return send_camp_result("Error: password check not passed!");

$action = $_REQUEST['action'];
$name = $_REQUEST['name'];
$campId = $_REQUEST['campid'];
$dupId = $_REQUEST['dupid']??'';
$db = new Db();

switch ($action) {
    case 'add':
        $campId = $db->add_campaign($name);
        if ($campId===false)
            return send_camp_result("Error adding new campaign!",true);
        return send_camp_result("OK");
    case 'dup':
        if ($db->clone_campaign($campId))
            return send_camp_result("OK");
        else
            return send_camp_result("Error duplicating campaign!");
    case 'del':
        if ($db->delete_campaign($campId))
            return send_camp_result("OK");
        else
            return send_camp_result("Error deleting campaign!");
    case 'save':
        $campSettings->to_json_string($config)
        if(save_config($config))
            return send_camp_result("OK");
        else
            return send_camp_result("Error saving campaign!");
    default:
        return send_camp_result("Error: wrong action!");
}
function send_camp_result($msg,$error=false): void
{
    $res = ["result" => $msg];
    if ($error){
        $res['error']=true;
    }
    header('Content-type: application/json');
    http_response_code(200);
    $json = json_encode($res);
    echo $json;
}
