<?php

require_once __DIR__ . '/../conversion.php';
require_once __DIR__ . '/../paths.php';

global $db, $cloSettings;

$clickid = trim((string)($_REQUEST['clickid'] ?? ''));
if ($clickid === '') {
    postback_response(null, ['accepted' => false, 'code' => 'missing_clickid', 'message' => 'No clickid found.'], 400);
}

$click = $db->get_click_by_clickid($clickid);
if ($click === []) {
    postback_response(null, ['accepted' => false, 'code' => 'click_not_found', 'message' => 'Clickid not found.'], 404);
}

$campaign = new Campaign((int)$click['campaign_id'], $db->get_campaign_settings((int)$click['campaign_id']));
if ($campaign->postback->pbkeyEnabled) {
    $providedPbkey = trim((string)($_REQUEST['pbkey'] ?? ''));
    if ($providedPbkey === '' || !in_array($providedPbkey, $campaign->postback->pbkeys, true)) {
        postback_response($campaign, ['accepted' => false, 'code' => 'invalid_pbkey', 'message' => 'Invalid pbkey.'], 403);
    }
}

$service = new ConversionService($db);
$result = $service->record(
    $campaign,
    $clickid,
    (string)($_REQUEST['status'] ?? ''),
    'postback',
    array_key_exists('payout', $_REQUEST) ? $_REQUEST['payout'] : null,
    (string)($_REQUEST['currency'] ?? 'USD'),
    isset($_REQUEST['tid']) ? (string)$_REQUEST['tid'] : null
);

$statusCode = match ($result['code'] ?? '') {
    'accepted' => 200,
    'click_not_found' => 404,
    'duplicate_tid', 'duplicate_status', 'duplicate_conversion', 'tid_required' => 409,
    'unknown_status', 'invalid_payout', 'invalid_currency', 'currency_error' => 422,
    default => 400,
};
postback_response($campaign, $result, $statusCode);

function postback_response(?Campaign $campaign, array $result, int $statusCode): never
{
    global $cloSettings;
    $accepted = ($result['accepted'] ?? false) === true;
    $hideFailure = !$accepted
        && $campaign !== null
        && $campaign->postback->pbkeyEnabled
        && !($cloSettings['debug'] ?? false);

    if ($hideFailure) {
        http_response_code(404);
        echo 'Not Found';
        exit;
    }

    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => $accepted,
        'code' => (string)($result['code'] ?? 'error'),
        'message' => (string)($result['message'] ?? 'Unknown error.'),
        'status' => $result['status'] ?? null,
        'conversion_id' => $result['conversion_id'] ?? null,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
