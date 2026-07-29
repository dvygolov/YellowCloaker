<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../../code/db/clicks.db');

echo "=== Block reasons for LoadTest Campaign (38) ===\n";
$stmt = $pdo->query("SELECT reason, COUNT(*) as cnt FROM blocked WHERE campaign_id = 38 GROUP BY reason ORDER BY cnt DESC LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  [{$r['cnt']}] {$r['reason']}\n";
}

echo "\n=== Sample blocked UAs ===\n";
$stmt = $pdo->query("SELECT ua, reason FROM blocked WHERE campaign_id = 38 ORDER BY id DESC LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  reason: {$r['reason']}\n";
    echo "  ua: " . substr($r['ua'], 0, 80) . "\n\n";
}
