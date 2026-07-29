<?php

require_once __DIR__ . '/../conversion.php';

header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'code' => 'method_not_allowed']);
    exit;
}

global $db;
$clickid = trim((string)($_POST['clickid'] ?? ''));
$click = $clickid === '' ? [] : $db->get_click_by_clickid($clickid);
if ($click === []) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'code' => 'click_not_found']);
    exit;
}

$campaign = new Campaign((int)$click['campaign_id'], $db->get_campaign_settings((int)$click['campaign_id']));
if (!$campaign->conversions->siteEnabled) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'code' => 'site_tracking_disabled']);
    exit;
}

$result = (new ConversionService($db))->record(
    $campaign,
    $clickid,
    (string)($_POST['status'] ?? ''),
    'site_script'
);
http_response_code(($result['accepted'] ?? false) ? 200 : 422);
echo json_encode([
    'ok' => ($result['accepted'] ?? false) === true,
    'code' => $result['code'] ?? 'error',
    'message' => $result['message'] ?? '',
    'status' => $result['status'] ?? null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
