<?php
/**
 * One-off script to update indexes on existing DB.
 * Run once, then delete: php update_indexes.php
 */

require_once __DIR__ . '/../../code/settings.php';

$dbPath = __DIR__ . '/../../code/db/' . $cloSettings['dbConnection'];

if (!file_exists($dbPath)) {
    die("DB not found at: $dbPath\n");
}

$db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
$db->busyTimeout(5000);

$statements = [
    // Remove redundant indexes on clicks
    "DROP INDEX IF EXISTS idx_campid",
    "DROP INDEX IF EXISTS idx_time",
    "DROP INDEX IF EXISTS idx_status",

    // Add missing indexes on clicks
    "CREATE INDEX IF NOT EXISTS idx_subid ON clicks (subid)",
    "CREATE INDEX IF NOT EXISTS idx_camp_flow ON clicks (campaign_id, flow)",

    // Add filter indexes on blocked
    "CREATE INDEX IF NOT EXISTS idx_bcountry ON blocked (country)",
    "CREATE INDEX IF NOT EXISTS idx_bos ON blocked (os)",
    "CREATE INDEX IF NOT EXISTS idx_bdevice ON blocked (device)",
    "CREATE INDEX IF NOT EXISTS idx_bisp ON blocked (isp)",
    "CREATE INDEX IF NOT EXISTS idx_breason ON blocked (reason)",

    // Add filter indexes on trafficback
    "CREATE INDEX IF NOT EXISTS idx_tbcountry ON trafficback (country)",
    "CREATE INDEX IF NOT EXISTS idx_tbos ON trafficback (os)",
    "CREATE INDEX IF NOT EXISTS idx_tbdevice ON trafficback (device)",
    "CREATE INDEX IF NOT EXISTS idx_tbisp ON trafficback (isp)",
];

echo "Updating indexes on: $dbPath\n\n";

foreach ($statements as $sql) {
    $result = $db->exec($sql);
    $status = $result ? "OK" : "FAIL: " . $db->lastErrorMsg();
    echo "  $sql — $status\n";
}

// Verify
echo "\n--- Current indexes ---\n";
$res = $db->query("SELECT name, tbl_name FROM sqlite_master WHERE type='index' ORDER BY tbl_name, name");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    echo "  {$row['tbl_name']}: {$row['name']}\n";
}

// Check for idx_camp_time_status specifically
$res2 = $db->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_camp_time_status'");
$found = $res2->fetchArray(SQLITE3_ASSOC);
if (!$found) {
    echo "\n⚠ idx_camp_time_status is MISSING — recreating...\n";
    $db->exec("CREATE INDEX IF NOT EXISTS idx_camp_time_status ON clicks (campaign_id,time,status)");
    echo "  Created idx_camp_time_status\n";
} else {
    echo "\n✓ idx_camp_time_status exists\n";
}

$db->close();
echo "\nDone.\n";
