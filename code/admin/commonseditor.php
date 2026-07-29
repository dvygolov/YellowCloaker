<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../logging.php';

$passOk = check_password(false);
if (!$passOk) {
    return send_common_result('Error: password check not passed!', true);
}

$action = $_REQUEST['action'] ?? '';
$postData = file_get_contents('php://input');

switch ($action) {
    case 'trafficback':
        $settings = $db->get_common_settings();
        $settings['trafficBackUrl'] = $postData;
        $saved = $db->set_common_settings($settings);
        if ($saved === false) {
            return send_common_result('Error saving settings!', true);
        }
        return send_common_result('OK');
    case 'savetimezone':
        $timezone = trim((string)($_POST['timezone'] ?? ''));
        if ($timezone === '' || !in_array($timezone, timezone_identifiers_list(), true)) {
            return send_common_result('Error: invalid timezone', true);
        }

        $settings = $db->get_common_settings();
        $settings['statistics']['timezone'] = $timezone;
        $saved = $db->set_common_settings($settings);
        if ($saved === false) {
            return send_common_result('Error saving timezone!', true);
        }
        return send_common_result('OK');
    default:
        return send_common_result('Error: unknown action', true);
}

function send_common_result(string $msg, bool $error = false): void
{
    $res = ['result' => $msg];
    if ($error) {
        $res['error'] = true;
    }
    header('Content-type: application/json');
    http_response_code(200);
    echo json_encode($res);
}
