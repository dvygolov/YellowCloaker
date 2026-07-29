<?php
require_once __DIR__ . '/../../code/db/db.php';

global $db;

// Use SQLite directly since exec_read_query is private
$dbFile = __DIR__ . '/../../code/db/clicks.db';
$pdo = new PDO("sqlite:$dbFile");

$campaigns = $db->get_campaigns(0, PHP_INT_MAX, ['clicks']);
foreach ($campaigns as $c) {
    if ($c['name'] === 'LoadTest Campaign') {
        echo "Campaign ID: {$c['id']}\n";
        echo "Clicks (from get_campaigns): {$c['clicks']}\n";

        $settings = $db->get_campaign_settings($c['id']);
        echo "Domains: " . json_encode($settings['domains']) . "\n";

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM clicks WHERE campaign_id = :cid");
        $stmt->execute([':cid' => $c['id']]);
        echo "DB clicks count: " . $stmt->fetch()['cnt'] . "\n";

        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM blocked WHERE campaign_id = :cid");
        $stmt->execute([':cid' => $c['id']]);
        echo "DB blocked count: " . $stmt->fetch()['cnt'] . "\n";

        // Show last 3 clicks if any
        $stmt = $pdo->prepare("SELECT time, ip, country, ua, clickid FROM clicks WHERE campaign_id = :cid ORDER BY id DESC LIMIT 3");
        $stmt->execute([':cid' => $c['id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo "\nLast clicks:\n";
            foreach ($rows as $r) {
                echo "  {$r['time']} | {$r['ip']} | {$r['country']} | " . substr($r['ua'], 0, 50) . "\n";
            }
        }

        // Also check all campaigns for any clicks
        echo "\n--- All campaigns with clicks ---\n";
        break;
    }
}

$stmt = $pdo->query("SELECT c.id, c.name, COUNT(cl.id) as click_count FROM campaigns c LEFT JOIN clicks cl ON c.id = cl.campaign_id GROUP BY c.id ORDER BY click_count DESC LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  [{$r['id']}] {$r['name']}: {$r['click_count']} clicks\n";
}
