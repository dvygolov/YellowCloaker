<?php
$db = new SQLite3(__DIR__ . '/../../code/db/clicks.db', SQLITE3_OPEN_READWRITE);
$db->exec('DELETE FROM clicks WHERE campaign_id > 1');
$db->exec('DELETE FROM blocked WHERE campaign_id > 1');
$db->exec('DELETE FROM campaigns WHERE id > 1');
echo "Cleaned up seeded campaigns (kept #1).\n";
$db->close();
