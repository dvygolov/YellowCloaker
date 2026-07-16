<?php

require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../systemstatus.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    global $cloSettings;
    $status = (new SystemStatus(dirname(__DIR__), $cloSettings))->get();
    echo json_encode($status, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    ytds_log('error', 'admin', $exception->getMessage(), ['action' => 'system-status']);
    http_response_code(500);
    echo json_encode(['error' => 'Unable to read system status']);
}
