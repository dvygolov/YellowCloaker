<?php
require_once __DIR__ . '/../logging.php';
require_once __DIR__ . '/../db/db.php';

global $db;

$clickid = $_GET['clickid'] ?? '';
if ($clickid === '') {
    http_response_code(400);
    $msg = 'No clickid provided in URL parameters';
    add_log('updateparams', $msg);
    echo $msg;
    exit;
}

try {
    $click = $db->get_click_by_clickid($clickid);
    if (empty($click)) {
        http_response_code(404);
        $msg = 'No click found for clickid: ' . $clickid;
        add_log('updateparams', $msg);
        echo $msg;
        exit;
    }

    $urlParams = $_GET;
    unset($urlParams['clickid']);

    if (empty($urlParams)) {
        http_response_code(200);
        $msg = 'No parameters to update for clickid: ' . $clickid;
        add_log('updateparams', $msg);
        echo $msg;
        exit;
    }

    $existingParams = $click['params'] ?? [];
    foreach ($urlParams as $key => $value) {
        $existingParams[$key] = $value;
    }

    $updated = $db->update_click_params($click['id'], $existingParams);
    if ($updated) {
        http_response_code(200);
        $updatedKeys = array_keys($urlParams);
        $msg = 'Successfully updated parameters for clickid: ' . $clickid . '. Updated keys: ' . implode(', ', $updatedKeys);
        add_log('updateparams', $msg);
        echo $msg;
    } else {
        http_response_code(500);
        $msg = 'Failed to update parameters for clickid: ' . $clickid;
        add_log('updateparams', $msg);
        echo $msg;
    }
} catch (Exception $e) {
    http_response_code(500);
    $msg = 'Error updating parameters for clickid ' . $clickid . ': ' . $e->getMessage();
    add_log('updateparams', $msg);
    echo $msg;
}
