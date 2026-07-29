<?php
/**
 * Clean up load test data.
 * Removes the LoadTest campaign and all associated clicks.
 *
 * Usage: php tests/load/teardown.php
 */

require_once __DIR__ . '/../../code/db/db.php';

global $db;

echo "=== YellowTDS Load Test Teardown ===\n\n";

$campaigns = $db->get_campaigns(0, PHP_INT_MAX, ['clicks']);
$found = false;
foreach ($campaigns as $c) {
    if ($c['name'] === 'LoadTest Campaign') {
        $id = $c['id'];
        echo "Found LoadTest campaign (ID: $id). Deleting...\n";
        $db->delete_campaign($id);
        echo "Campaign deleted.\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "No LoadTest campaign found. Nothing to clean up.\n";
}

echo "\nDone.\n";
