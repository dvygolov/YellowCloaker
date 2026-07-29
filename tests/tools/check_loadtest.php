<?php
$db = new SQLite3(__DIR__ . '/../../code/db/clicks.db', SQLITE3_OPEN_READONLY);
$res = $db->query("SELECT id, name FROM campaigns");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) { echo $row['id'] . ': ' . $row['name'] . PHP_EOL; }
echo PHP_EOL;

$res = $db->query("SELECT id, name FROM campaigns WHERE name LIKE '%LoadTest%'");
$camp = $res->fetchArray(SQLITE3_ASSOC);
if ($camp) {
    $id = $camp['id'];
    echo "Found: #$id {$camp['name']}\n";

    $res2 = $db->query("SELECT date(time, 'unixepoch') as d, count(*) as cnt FROM clicks WHERE campaign_id=$id GROUP BY d ORDER BY d");
    echo "\nClicks per day:\n";
    while ($row = $res2->fetchArray(SQLITE3_ASSOC)) { echo "  {$row['d']}: {$row['cnt']}\n"; }

    echo "\nSample click:\n";
    $res3 = $db->query("SELECT * FROM clicks WHERE campaign_id=$id LIMIT 1");
    $sample = $res3->fetchArray(SQLITE3_ASSOC);
    print_r($sample);

    echo "\nFlows:\n";
    $res4 = $db->query("SELECT flow, count(*) as cnt FROM clicks WHERE campaign_id=$id GROUP BY flow");
    while ($row = $res4->fetchArray(SQLITE3_ASSOC)) { echo "  {$row['flow']}: {$row['cnt']}\n"; }

    echo "\nCurrent step distribution:\n";
    $res5 = $db->query("SELECT step, count(*) as cnt FROM clicks WHERE campaign_id=$id GROUP BY step ORDER BY step");
    while ($row = $res5->fetchArray(SQLITE3_ASSOC)) { echo "  step {$row['step']}: {$row['cnt']}\n"; }

    echo "\nStep entries (click_steps):\n";
    $res6 = $db->query("SELECT cs.step, cs.variant, count(*) as cnt FROM click_steps cs INNER JOIN clicks c ON c.clickid = cs.clickid WHERE c.campaign_id=$id GROUP BY cs.step, cs.variant ORDER BY cs.step, cnt DESC");
    while ($row = $res6->fetchArray(SQLITE3_ASSOC)) { echo "  step {$row['step']} / {$row['variant']}: {$row['cnt']}\n"; }

    echo "\nStatuses:\n";
    $res7 = $db->query("SELECT status, count(*) as cnt FROM clicks WHERE campaign_id=$id GROUP BY status");
    while ($row = $res7->fetchArray(SQLITE3_ASSOC)) { echo "  " . ($row['status'] ?? 'NULL') . ": {$row['cnt']}\n"; }

    echo "\nCountries (top 10):\n";
    $res8 = $db->query("SELECT country, count(*) as cnt FROM clicks WHERE campaign_id=$id GROUP BY country ORDER BY cnt DESC LIMIT 10");
    while ($row = $res8->fetchArray(SQLITE3_ASSOC)) { echo "  {$row['country']}: {$row['cnt']}\n"; }

    echo "\nOS:\n";
    $res9 = $db->query("SELECT os, count(*) as cnt FROM clicks WHERE campaign_id=$id GROUP BY os ORDER BY cnt DESC");
    while ($row = $res9->fetchArray(SQLITE3_ASSOC)) { echo "  {$row['os']}: {$row['cnt']}\n"; }

    echo "\nCost/payout sample:\n";
    $res10 = $db->query("SELECT avg(cost) as avg_cost, avg(payout) as avg_pay, sum(cost) as sum_cost, sum(payout) as sum_pay FROM clicks WHERE campaign_id=$id");
    $row = $res10->fetchArray(SQLITE3_ASSOC);
    print_r($row);
} else {
    echo "No LoadTest campaign found!\n";
}
$db->close();
