<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
$passOk = check_password(false);
if (!$passOk)
    return send_configmanager_result("Error: password check not passed!");

$action = $_REQUEST['action'];
$config = $_REQUEST['name'];
switch ($action) {
    case 'add':
        add_config($config);
        return send_configmanager_result("OK");
        break;
    case 'del':
        del_config($config);
        return send_configmanager_result("OK");
        break;
    case 'save':
        save_config($config);
        return send_configmanager_result("OK");
        break;
    default:
        return send_configmanager_result("Error: wrong action!");
        break;
}
function send_configmanager_result($msg)
{
    $res = ["result" => $msg];
    header('Content-type: application/json');
    echo json_encode($res);
}
