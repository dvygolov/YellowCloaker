<?php
require_once __DIR__ . '/../../code/db/db.php';
require_once __DIR__ . '/../../code/campaign.php';

global $db;
$s = $db->get_campaign_settings(38);
$c = new Campaign(38, $s);

echo "=== statistics->allowed ===\n";
echo json_encode($c->statistics->allowed, JSON_PRETTY_PRINT) . "\n";

echo "\n=== statistics->blocked ===\n";
echo json_encode($c->statistics->blocked, JSON_PRETTY_PRINT) . "\n";

echo "\n=== statistics->timezone ===\n";
echo $c->statistics->timezone . "\n";
