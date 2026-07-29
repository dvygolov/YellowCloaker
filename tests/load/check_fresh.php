<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../../code/db/clicks.db');

echo "=== Campaign 38 clicks ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM clicks WHERE campaign_id = 38");
echo "clicks: " . $stmt->fetchColumn() . "\n";

$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM blocked WHERE campaign_id = 38");
echo "blocked: " . $stmt->fetchColumn() . "\n";

echo "\n=== Last 5 blocked for campaign 38 ===\n";
$stmt = $pdo->query("SELECT id, time, ip, country, reason, ua FROM blocked WHERE campaign_id = 38 ORDER BY id DESC LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  [{$r['id']}] {$r['time']} | {$r['ip']} | {$r['country']} | {$r['reason']} | " . substr($r['ua'], 0, 50) . "\n";
}

echo "\n=== Last 5 clicks for campaign 38 ===\n";
$stmt = $pdo->query("SELECT id, time, ip, country, clickid, ua FROM clicks WHERE campaign_id = 38 ORDER BY id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "  (none)\n";
} else {
    foreach ($rows as $r) {
        echo "  [{$r['id']}] {$r['time']} | {$r['ip']} | {$r['country']} | {$r['clickid']} | " . substr($r['ua'], 0, 50) . "\n";
    }
}

echo "\n=== Last 5 trafficback ===\n";
$stmt = $pdo->query("SELECT * FROM trafficback ORDER BY rowid DESC LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  " . json_encode($r) . "\n";
}
