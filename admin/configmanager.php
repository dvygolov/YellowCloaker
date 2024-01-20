<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
$passOk = check_password(false);
if (!$passOk)
    return send_configmanager_result("Error: password check not passed!");

$action = $_REQUEST['action'];
$config = $_REQUEST['name'];
$dupconfig = $_REQUEST['dupname']??'';
switch ($action) {
    case 'add':
        if (add_config($config))
            return send_configmanager_result("OK");
        else
            return send_configmanager_result("Error adding new config!");
    case 'dup':
        if (duplicate_config($config,$dupconfig ))
            return send_configmanager_result("OK");
        else
            return send_configmanager_result("Error duplicating config!");
    case 'del':
        if (del_config($config))
            return send_configmanager_result("OK");
        else
            return send_configmanager_result("Error deleting config!");
    case 'save':
        if(save_config($config))
            return send_configmanager_result("OK");
        else
            return send_configmanager_result("Error saving config!");
    default:
        return send_configmanager_result("Error: wrong action!");
        break;
}
function send_configmanager_result($msg): int
{
    $res = ["result" => $msg];
    header('Content-type: application/json');
    echo json_encode($res);
    return http_response_code(200);
}
