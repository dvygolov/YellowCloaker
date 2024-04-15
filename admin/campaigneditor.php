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
        if ($db->add_campaign($name))
            return send_camp_result("OK");
        else
            return send_camp_result("Error adding new campaign!");
    case 'dup':
        if (duplicate_config($config,$dupconfig ))
            return send_camp_result("OK");
        else
            return send_camp_result("Error duplicating campaign!");
    case 'del':
        if (del_config($config))
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
function send_camp_result($msg): void
{
    $res = ["result" => $msg];
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode($res);
}
