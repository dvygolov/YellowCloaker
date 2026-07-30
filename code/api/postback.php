<?php

require_once __DIR__ . '/../conversion.php';
require_once __DIR__ . '/../paths.php';

global $db, $cloSettings;

$query = is_array($_GET) ? $_GET : [];
$body = is_array($_POST) ? $_POST : [];
$clickidInput = PostbackInput::readString($query, $body, 'clickid', 255);
if (!($clickidInput['ok'] ?? false)) {
    postback_response(null, [
        'accepted' => false,
        'code' => (string)($clickidInput['code'] ?? 'invalid_parameter'),
        'message' => (string)($clickidInput['message'] ?? 'Invalid clickid.'),
    ], 400);
}
$clickid = trim((string)($clickidInput['value'] ?? ''));
if ($clickid === '') {
    postback_response(null, ['accepted' => false, 'code' => 'missing_clickid', 'message' => 'No clickid found.'], 400);
}

$click = $db->get_click_by_clickid($clickid);
if ($click === []) {
    postback_response(null, ['accepted' => false, 'code' => 'click_not_found', 'message' => 'Clickid not found.'], 404);
}

$campaign = new Campaign((int)$click['campaign_id'], $db->get_campaign_settings((int)$click['campaign_id']));
$pbkeyInput = PostbackInput::readString($query, $body, 'pbkey', 255);
if (!($pbkeyInput['ok'] ?? false)) {
    postback_response($campaign, [
        'accepted' => false,
        'code' => (string)($pbkeyInput['code'] ?? 'invalid_parameter'),
        'message' => (string)($pbkeyInput['message'] ?? 'Invalid pbkey.'),
    ], 400);
}
if ($campaign->postback->pbkeyEnabled) {
    $providedPbkey = trim((string)($pbkeyInput['value'] ?? ''));
    if ($providedPbkey === '' || !in_array($providedPbkey, $campaign->postback->pbkeys, true)) {
        postback_response($campaign, ['accepted' => false, 'code' => 'invalid_pbkey', 'message' => 'Invalid pbkey.'], 403);
    }
}

$statusInput = PostbackInput::readString($query, $body, 'status', 255);
$payoutInput = PostbackInput::readString($query, $body, 'payout', 128);
$currencyInput = PostbackInput::readString($query, $body, 'currency', 16);
foreach ([$statusInput, $payoutInput, $currencyInput] as $input) {
    if (!($input['ok'] ?? false)) {
        postback_response($campaign, [
            'accepted' => false,
            'code' => (string)($input['code'] ?? 'invalid_parameter'),
            'message' => (string)($input['message'] ?? 'Invalid postback parameter.'),
        ], 400);
    }
}

$transactionId = $campaign->conversions->resolveTransactionIdFromRequest($query, $body);
if (!($transactionId['ok'] ?? false)) {
    postback_response($campaign, [
        'accepted' => false,
        'code' => (string)($transactionId['code'] ?? 'invalid_tid'),
        'message' => (string)($transactionId['message'] ?? 'Invalid transaction ID.'),
    ], 400);
}

$service = new ConversionService($db);
$result = $service->record(
    $campaign,
    $clickid,
    (string)($statusInput['value'] ?? ''),
    'postback',
    $payoutInput['value'],
    (string)($currencyInput['value'] ?? 'USD'),
    $transactionId['tid'],
    $transactionId['parameter']
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
    $publicStatusCode = $hideFailure ? 404 : $statusCode;

    ytds_log_postback(
        'incoming',
        $accepted ? 'accepted' : 'rejected',
        $accepted ? 'Incoming postback accepted' : 'Incoming postback rejected',
        array_filter([
            'method' => strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            'campaign_id' => $campaign?->campaignId,
            'clickid' => postback_log_input_value('clickid'),
            'status' => postback_log_input_value('status'),
            'resolved_status' => isset($result['status']) ? (string)$result['status'] : null,
            'payout' => postback_log_input_value('payout'),
            'currency' => postback_log_input_value('currency'),
            'code' => (string)($result['code'] ?? 'error'),
            'http_code' => $publicStatusCode,
            'result_http_code' => $statusCode,
            'masked' => $hideFailure,
            'conversion_id' => isset($result['conversion_id']) ? (int)$result['conversion_id'] : null,
        ], static fn(mixed $value): bool => $value !== null && $value !== '')
    );

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

function postback_log_input_value(string $name): ?string
{
    $queryHas = array_key_exists($name, $_GET);
    $bodyHas = array_key_exists($name, $_POST);
    if ($queryHas === $bodyHas) {
        return null;
    }
    $value = $queryHas ? $_GET[$name] : $_POST[$name];
    if (!is_scalar($value)) {
        return null;
    }
    return substr(trim((string)$value), 0, 512);
}
