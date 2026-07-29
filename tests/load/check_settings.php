<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/../../code/db/clicks.db');
$s = $pdo->query("SELECT settings FROM campaigns WHERE id = 38")->fetchColumn();
$j = json_decode($s, true);
echo "=== White filters ===\n";
echo json_encode($j['white']['filters'], JSON_PRETTY_PRINT) . "\n";
echo "\n=== Black flow names ===\n";
foreach ($j['black']['flows'] as $i => $f) {
    echo "  Flow $i: {$f['name']}\n";
    if (isset($f['filters']['rules'])) {
        foreach ($f['filters']['rules'] as $r) {
            echo "    {$r['id']} {$r['operator']} {$r['value']}\n";
        }
    }
}
