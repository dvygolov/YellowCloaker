<?php

require_once __DIR__ . '/../campaign.php';
require_once __DIR__ . '/../s2spostback.php';

const YELLOWTDS_EVENTS_MAX_BODY_BYTES = 16384;
const YELLOWTDS_EVENTS_MAX_ELAPSED_MS = 2592000000;
const YELLOWTDS_PERFORMANCE_MAX_TIMING_MS = 86400000;
const YELLOWTDS_PERFORMANCE_METRICS = ['ttfb', 'fcp', 'lcp', 'inp', 'cls'];

/**
 * @return array{
 *     clickid: string,
 *     step_index: int,
 *     variant: string,
 *     type: 'event'|'performance',
 *     event?: string,
 *     value?: int,
 *     performance?: array<string, int|float>
 * }
 */
function yellowtds_parse_events_request(string $rawBody): array
{
    if ($rawBody === '' || strlen($rawBody) > YELLOWTDS_EVENTS_MAX_BODY_BYTES) {
        throw new InvalidArgumentException('Invalid event payload size');
    }

    try {
        $decoded = json_decode($rawBody, false, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        throw new InvalidArgumentException('Invalid JSON event payload');
    }
    if (!$decoded instanceof stdClass) {
        throw new InvalidArgumentException('Event payload must be a JSON object');
    }
    $payload = get_object_vars($decoded);

    $clickid = $payload['clickid'] ?? null;
    $stepIndex = $payload['step_index'] ?? null;
    $variant = $payload['variant'] ?? null;
    if (
        !is_string($clickid)
        || trim($clickid) === ''
        || strlen(trim($clickid)) > 255
        || !is_int($stepIndex)
        || $stepIndex < 0
        || !is_string($variant)
        || trim($variant) === ''
        || strlen($variant) > 2048
    ) {
        throw new InvalidArgumentException('Invalid click-step identity');
    }

    $hasEvent = array_key_exists('event', $payload) || array_key_exists('value', $payload);
    $hasPerformance = array_key_exists('performance', $payload);
    if ($hasEvent === $hasPerformance) {
        throw new InvalidArgumentException('Send exactly one event payload type');
    }

    $base = [
        'clickid' => trim($clickid),
        'step_index' => $stepIndex,
        'variant' => $variant,
    ];
    if ($hasEvent) {
        if (array_diff(array_keys($payload), ['clickid', 'step_index', 'variant', 'event', 'value']) !== []) {
            throw new InvalidArgumentException('Unknown ordinary event payload field');
        }
        $eventName = $payload['event'] ?? null;
        $value = $payload['value'] ?? null;
        if (
            !is_string($eventName)
            || preg_match(EventSettings::EVENT_NAME_PATTERN, $eventName) !== 1
            || (!is_int($value) && !is_float($value))
            || !is_finite((float)$value)
        ) {
            throw new InvalidArgumentException('Invalid ordinary event payload');
        }
        $elapsed = (int)round((float)$value);
        if ($elapsed < 0 || $elapsed > YELLOWTDS_EVENTS_MAX_ELAPSED_MS) {
            throw new InvalidArgumentException('Event elapsed time is out of range');
        }
        return $base + [
            'type' => 'event',
            'event' => $eventName,
            'value' => $elapsed,
        ];
    }

    if (array_diff(array_keys($payload), ['clickid', 'step_index', 'variant', 'performance']) !== []) {
        throw new InvalidArgumentException('Unknown performance payload field');
    }
    if (!$payload['performance'] instanceof stdClass) {
        throw new InvalidArgumentException('Performance must be a JSON object');
    }
    $performanceRaw = get_object_vars($payload['performance']);
    if ($performanceRaw === [] || count($performanceRaw) > count(YELLOWTDS_PERFORMANCE_METRICS)) {
        throw new InvalidArgumentException('Invalid performance packet');
    }
    foreach (array_keys($performanceRaw) as $metric) {
        if (!in_array($metric, YELLOWTDS_PERFORMANCE_METRICS, true)) {
            throw new InvalidArgumentException('Unknown performance metric');
        }
    }

    $performance = [];
    foreach (YELLOWTDS_PERFORMANCE_METRICS as $metric) {
        if (!array_key_exists($metric, $performanceRaw)) {
            continue;
        }
        $value = $performanceRaw[$metric];
        if ((!is_int($value) && !is_float($value)) || !is_finite((float)$value)) {
            throw new InvalidArgumentException('Invalid performance metric value');
        }
        if ($metric === 'cls') {
            $normalized = round((float)$value, 4);
            if ($normalized < 0 || $normalized > 100) {
                throw new InvalidArgumentException('CLS is out of range');
            }
            $performance[$metric] = $normalized;
            continue;
        }

        $normalized = (int)round((float)$value);
        if ($normalized < 0 || $normalized > YELLOWTDS_PERFORMANCE_MAX_TIMING_MS) {
            throw new InvalidArgumentException('Performance timing is out of range');
        }
        $performance[$metric] = $normalized;
    }

    return $base + [
        'type' => 'performance',
        'performance' => $performance,
    ];
}

function yellowtds_events_content_type_is_supported(string $contentType): bool
{
    $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));
    return in_array($mediaType, ['text/plain', 'application/json'], true);
}

function yellowtds_read_events_body(): string
{
    $body = file_get_contents(
        'php://input',
        false,
        null,
        0,
        YELLOWTDS_EVENTS_MAX_BODY_BYTES + 1
    );
    if ($body === false) {
        throw new RuntimeException('Failed to read event payload');
    }
    return $body;
}

function yellowtds_events_status_for_result(string $result): int
{
    return match ($result) {
        Db::STEP_EVENT_CREATED, Db::STEP_EVENT_SAVED => 200,
        Db::STEP_EVENT_NOT_FOUND => 404,
        Db::STEP_EVENT_NOT_ALLOWED => 422,
        default => 503,
    };
}

function yellowtds_events_respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Completes the successful browser response before any optional outbound S2S
 * work starts. FPM can close the client connection immediately; other SAPIs
 * still receive a framed response before the best-effort flush.
 */
function yellowtds_events_finish_success_response(?callable $finisher = null): void
{
    $body = json_encode(
        ['ok' => true],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
    http_response_code(200);
    header('Content-Length: ' . strlen($body));
    echo $body;

    if ($finisher !== null) {
        $finisher();
        return;
    }
    ignore_user_abort(true);
    if (function_exists('fastcgi_finish_request') && @fastcgi_finish_request()) {
        return;
    }

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @flush();
}

function yellowtds_dispatch_first_written_event(
    Db $db,
    array $payload,
    string $storageResult,
    ?S2sPostbackTransport $transport = null
): void {
    if (
        $storageResult !== Db::STEP_EVENT_CREATED
        || ($payload['type'] ?? null) !== 'event'
    ) {
        return;
    }

    try {
        $click = $db->get_click_by_clickid((string)($payload['clickid'] ?? ''));
        if ($click === []) {
            add_log('postback', 'Event S2S skipped because the click no longer exists.');
            return;
        }
        $campaignId = (int)($click['campaign_id'] ?? 0);
        $settings = $campaignId > 0 ? $db->get_campaign_settings($campaignId) : [];
        $postback = is_array($settings['postback'] ?? null) ? $settings['postback'] : [];
        $s2sPostbacks = is_array($postback['s2s'] ?? null) ? $postback['s2s'] : [];
        process_event_s2s_postbacks(
            $s2sPostbacks,
            (string)$payload['event'],
            (int)$payload['value'],
            (int)$payload['step_index'],
            (string)$payload['variant'],
            $click,
            $transport
        );
    } catch (Throwable $e) {
        // Event persistence and its HTTP response must never depend on an
        // optional outbound recipient.
        add_log('postback', 'Event S2S dispatch failed: ' . $e->getMessage());
    }
}

function yellowtds_events_finish_response_and_dispatch(
    Db $db,
    array $payload,
    string $storageResult,
    ?S2sPostbackTransport $transport = null,
    ?callable $finisher = null
): void {
    yellowtds_events_finish_success_response($finisher);
    yellowtds_dispatch_first_written_event($db, $payload, $storageResult, $transport);
}

function yellowtds_run_events_api(
    Db $db,
    ?S2sPostbackTransport $transport = null
): never
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Cache-Control: no-store');

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    if ($method !== 'POST') {
        header('Allow: POST, OPTIONS');
        yellowtds_events_respond(405, ['error' => 'Method not allowed']);
    }

    $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    if (!yellowtds_events_content_type_is_supported($contentType)) {
        yellowtds_events_respond(400, ['error' => 'Expected a JSON event payload']);
    }
    $contentLength = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT);
    if (is_int($contentLength) && $contentLength > YELLOWTDS_EVENTS_MAX_BODY_BYTES) {
        yellowtds_events_respond(400, ['error' => 'Event payload is too large']);
    }

    try {
        $payload = yellowtds_parse_events_request(yellowtds_read_events_body());
    } catch (InvalidArgumentException $e) {
        yellowtds_events_respond(400, ['error' => $e->getMessage()]);
    } catch (RuntimeException) {
        yellowtds_events_respond(503, ['error' => 'Event payload is unavailable']);
    }

    $result = $payload['type'] === 'event'
        ? $db->save_step_event(
            $payload['clickid'],
            $payload['step_index'],
            $payload['variant'],
            $payload['event'],
            $payload['value']
        )
        : $db->save_step_performance(
            $payload['clickid'],
            $payload['step_index'],
            $payload['variant'],
            $payload['performance']
        );

    $status = yellowtds_events_status_for_result($result);
    if ($status === 200) {
        yellowtds_events_finish_response_and_dispatch(
            $db,
            $payload,
            $result,
            $transport
        );
        exit;
    }
    if ($status === 404) {
        yellowtds_events_respond(404, ['error' => 'Click step not found']);
    }
    if ($status === 422) {
        yellowtds_events_respond(422, ['error' => 'Event is not enabled for this campaign']);
    }

    yellowtds_events_respond(503, ['error' => 'Event storage is unavailable']);
}

if (!defined('YELLOWTDS_EVENTS_API_NO_RUN')) {
    global $db;
    yellowtds_run_events_api($db);
}
