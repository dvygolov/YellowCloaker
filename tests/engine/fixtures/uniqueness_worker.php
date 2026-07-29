<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestDb.php';

[$script, $dbPath, $barrierPath, $hashBase64, $workerId] = $argv;
$hash = base64_decode($hashBase64, true);
if ($hash === false) {
    fwrite(STDERR, "Invalid hash\n");
    exit(2);
}

$deadline = microtime(true) + 10;
while (!is_file($barrierPath)) {
    if (microtime(true) >= $deadline) {
        fwrite(STDERR, "Barrier timeout\n");
        exit(3);
    }
    usleep(1000);
}

$db = new TestDb($dbPath);
$flag = $db->immediate_transaction(function (SQLite3 $connection) use ($hash, $workerId): int {
    $stmt = $connection->prepare(
        'SELECT 1 FROM clicks WHERE campaign_id = 1 AND unique_hash = :hash '
        . 'AND unique_flags IS NOT NULL AND time > :cutoff ORDER BY time DESC LIMIT 1'
    );
    $stmt->bindValue(':hash', $hash, SQLITE3_BLOB);
    $stmt->bindValue(':cutoff', time() - 3600, SQLITE3_INTEGER);
    $isUnique = $stmt->execute()->fetchArray(SQLITE3_NUM) === false;

    // Keep the first writer long enough for the second process to contend on BEGIN IMMEDIATE.
    usleep(150000);
    $insert = $connection->prepare(
        'INSERT INTO clicks (campaign_id,time,ip,userid,unique_hash,unique_flags,clickid,flow) '
        . 'VALUES (1,:time,:ip,\'\',:hash,:flags,:clickid,\'Flow 1\')'
    );
    $insert->bindValue(':time', time(), SQLITE3_INTEGER);
    $insert->bindValue(':ip', '127.0.0.' . $workerId, SQLITE3_TEXT);
    $insert->bindValue(':hash', $hash, SQLITE3_BLOB);
    $insert->bindValue(':flags', $isUnique ? 3 : 0, SQLITE3_INTEGER);
    $insert->bindValue(':clickid', 'worker-' . $workerId . '-' . bin2hex(random_bytes(4)), SQLITE3_TEXT);
    if ($insert->execute() === false) {
        throw new RuntimeException($connection->lastErrorMsg());
    }
    return $isUnique ? 3 : 0;
});

echo 'FLAG=' . $flag . PHP_EOL;
