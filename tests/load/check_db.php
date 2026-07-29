<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../../code/db/clicks.db');

echo "=== All campaigns and their domains ===\n";
$stmt = $pdo->query("SELECT id, name, settings FROM campaigns ORDER BY id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $s = json_decode($c['settings'], true);
    $domains = $s['domains'] ?? [];
    echo "  [{$c['id']}] {$c['name']}: " . json_encode($domains) . "\n";
}

echo "\n=== Clicks per campaign ===\n";
$stmt = $pdo->query("SELECT campaign_id, COUNT(*) as cnt FROM clicks GROUP BY campaign_id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  campaign {$r['campaign_id']}: {$r['cnt']} clicks\n";
}

echo "\n=== Blocked per campaign ===\n";
$stmt = $pdo->query("SELECT campaign_id, COUNT(*) as cnt FROM blocked GROUP BY campaign_id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  campaign {$r['campaign_id']}: {$r['cnt']} blocked\n";
}

echo "\n=== Trafficback count ===\n";
echo "  " . $pdo->query("SELECT COUNT(*) FROM trafficback")->fetchColumn() . " rows\n";
