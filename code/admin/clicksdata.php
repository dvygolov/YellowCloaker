<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/dates.php';

$campId = isset($_GET['campId']) ? (int)$_GET['campId'] : null;
$view = $_GET['view'] ?? 'allowed';
$allowedViews = ['allowed', 'blocked', 'leads', 'trafficback'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'allowed';
}

if ($view === 'trafficback') {
    require_once __DIR__ . '/../db/db.php';
    global $db;
    $gs = $db->get_common_settings();
    $tz = $gs['statistics']['timezone'];
} else {
    if ($campId === null) {
        header('Content-Type: application/json');
        echo json_encode(['last_page' => 1, 'data' => []]);
        exit;
    }
    require_once __DIR__ . '/campinit.php';
    global $db, $c;
    $tz = $c->statistics->timezone;
}

$timeRange = Dates::get_time_range($tz);
$startDate = $timeRange[0];
$endDate = $timeRange[1];

$page = max(1, (int)($_GET['page'] ?? 1));
$size = max(1, min(5000, (int)($_GET['size'] ?? 500)));

$sortField = $_GET['sort'] ?? 'time';
$sortDir = $_GET['dir'] ?? 'desc';
$searchTerm = trim((string)($_GET['search'] ?? ''));

// Read filters and columns from saved settings
$filters = [];
$paramColumns = [];
$filterKeyMap = ['allowed' => 'allowedFilters', 'blocked' => 'blockedFilters', 'leads' => 'leadsFilters', 'trafficback' => 'trafficBackFilters'];
$filterKey = $filterKeyMap[$view] ?? 'allowedFilters';

if ($view === 'trafficback') {
    $filters = $gs['statistics'][$filterKey] ?? [];
    $tableColumns = $gs['statistics']['trafficBack'] ?? [];
} else {
    $s = $db->get_campaign_settings($campId);
    $filters = $s['statistics'][$filterKey] ?? [];
    $columnKey = ($view === 'leads') ? 'leads' : (($view === 'blocked') ? 'blocked' : 'allowed');
    $tableColumns = $s['statistics'][$columnKey] ?? [];
}

// Extract param.* column keys for extraction
foreach ($tableColumns as $col) {
    $f = is_array($col) ? ($col['field'] ?? '') : $col;
    if (str_starts_with($f, 'param.')) {
        $paramColumns[] = substr($f, 6);
    }
}

$result = $db->get_clicks_paginated($view, $startDate, $endDate, $campId, $page, $size, $sortField, $sortDir, $filters, $paramColumns, $searchTerm);

header('Content-Type: application/json');
echo json_encode($result);
