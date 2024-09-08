<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../campaign.php';

$passOk = check_password(false);
if (!$passOk)
    return send_camp_result("Error: password check not passed!");

$action = $_REQUEST['action'];
$name = $_REQUEST['name']??'';
$campId = $_REQUEST['campId']??-1;
$db = new Db();

switch ($action) {
    case 'add':
        $campId = $db->add_campaign($name);
        if ($campId===false)
            return send_camp_result("Error adding new campaign!",true);
        break;
    case 'dup':
        $clonedId = $db->clone_campaign($campId);
        if ($clonedId===false)
            return send_camp_result("Error duplicating campaign!",true);
        break;
    case 'del':
        $delRes = $db->delete_campaign($campId);
        if ($delRes===false)
            return send_camp_result("Error deleting campaign!",true);
        break;
    case 'ren':
        $renRes = $db->rename_campaign($campId, $name);
        if ($renRes===false)
            return send_camp_result("Error renaming campaign!",true);
        break;
    case 'save':
        $body = file_get_contents('php://input');
        $c = new Campaign($campId,$body);
        $saveRes = $db->save_campaign_settings($campId, $settings);
        if($saveRes===false)
            return send_camp_result("Error saving campaign!",true);
        break;
    default:
        return send_camp_result("Error: wrong action!",true);
}
return send_camp_result("OK");

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
