<?php

require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../db/db.php';

header('Content-Type: application/json; charset=utf-8');
if (!check_password(false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

global $db;
$campaignId = (int)($_GET['campId'] ?? 0);
$status = trim((string)($_GET['status'] ?? ''));
$settings = $campaignId > 0 ? $db->get_campaign_settings($campaignId) : [];
if ($settings === [] || $status === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid campaign or status']);
    exit;
}

$usages = [];
$equals = static fn($value): bool => is_string($value) && strcasecmp($value, $status) === 0;
if ($equals($settings['conversions']['form']['status'] ?? null)) {
    $usages[] = 'Form submission conversion';
}
foreach (($settings['postback']['s2s'] ?? []) as $index => $s2s) {
    foreach (($s2s['statuses'] ?? []) as $s2sStatus) {
        if ($equals($s2sStatus)) {
            $usages[] = 'S2S postback #' . ($index + 1);
            break;
        }
    }
}
foreach (($settings['statistics']['tables'] ?? []) as $table) {
    foreach (($table['columns'] ?? []) as $column) {
        if (is_array($column) && !empty($column['status_metric']) && $equals($column['status'] ?? null)) {
            $usages[] = 'Statistics table: ' . (string)($table['name'] ?? 'Unnamed');
            break;
        }
    }
}

$scanRules = function ($node, string $flowName) use (&$scanRules, &$usages, $equals): void {
    if (!is_array($node)) {
        return;
    }
    if (in_array($node['id'] ?? '', ['conversion_cap_campaign', 'conversion_cap_flow'], true)) {
        $value = is_array($node['value'] ?? null) ? $node['value'] : [];
        foreach (($value['statuses'] ?? []) as $ruleStatus) {
            if ($equals($ruleStatus)) {
                $usages[] = 'Flow CAP: ' . $flowName;
                break;
            }
        }
    }
    foreach ($node as $value) {
        if (is_array($value)) {
            $scanRules($value, $flowName);
        }
    }
};
foreach (($settings['black']['flows'] ?? []) as $flow) {
    $scanRules($flow['filters'] ?? [], (string)($flow['name'] ?? 'Unnamed'));
}

$historyCount = $db->get_conversion_status_history_count($campaignId, $status);
$currentCount = $db->get_current_status_click_count($campaignId, $status);
if ($historyCount > 0) {
    $usages[] = "Conversion history: {$historyCount} rows";
}
if ($currentCount > 0) {
    $usages[] = "Current click status: {$currentCount} clicks";
}

echo json_encode(['status' => $status, 'usages' => array_values(array_unique($usages))], JSON_UNESCAPED_UNICODE);
