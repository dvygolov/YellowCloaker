<?php
// Test via raw SQLite
$dbPath = __DIR__ . '/../../code/db/clicks.db';
$raw = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);

echo "=== Raw SQLite check ===\n";
echo "Campaigns: " . $raw->querySingle('SELECT COUNT(*) FROM campaigns') . "\n";
echo "Clicks: " . $raw->querySingle('SELECT COUNT(*) FROM clicks') . "\n";
echo "Blocked: " . $raw->querySingle('SELECT COUNT(*) FROM blocked') . "\n";

$minT = $raw->querySingle('SELECT MIN(time) FROM clicks');
$maxT = $raw->querySingle('SELECT MAX(time) FROM clicks');
echo "Time range: " . date('Y-m-d H:i:s', $minT) . " - " . date('Y-m-d H:i:s', $maxT) . "\n";

$r = $raw->query('SELECT id, name FROM campaigns');
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    echo "  Campaign #{$row['id']}: {$row['name']}\n";
}
$raw->close();

// Test via Db class (same path as admin)
echo "\n=== Via Db class ===\n";
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'Test';

require_once __DIR__ . '/../../code/db/db.php';

echo "Db class dbPath resolves to settings dbConnection: " . $GLOBALS['cloSettings']['dbConnection'] . "\n";

$gs = $db->get_common_settings();
echo "Common settings timezone: " . $gs['statistics']['timezone'] . "\n";
echo "Table columns count: " . count($gs['statistics']['table']) . "\n";

// Simulate what admin/index.php does
date_default_timezone_set($gs['statistics']['timezone']);
$dtz = new DateTimeZone($gs['statistics']['timezone']);
$startdate = new DateTime("now", $dtz);
$enddate = new DateTime("now", $dtz);
$startdate->setTime(0, 0, 0);
$enddate->setTime(23, 59, 59);
$startTs = $startdate->getTimestamp();
$endTs = $enddate->getTimestamp();

echo "Date range (tz={$gs['statistics']['timezone']}): " . $startdate->format('Y-m-d H:i:s') . " ($startTs) - " . $enddate->format('Y-m-d H:i:s') . " ($endTs)\n";

$fields = array_column($gs['statistics']['table'], 'field');
echo "Fields: " . implode(', ', $fields) . "\n";

$campaigns = $db->get_campaigns($startTs, $endTs, $fields);
echo "get_campaigns returned: " . count($campaigns) . " rows\n";
foreach ($campaigns as $c) {
    echo "  #{$c['id']}: {$c['name']} - clicks=" . ($c['clicks'] ?? 'N/A') . "\n";
}
