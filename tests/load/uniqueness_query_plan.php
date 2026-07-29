<?php

$rowCount = isset($argv[1]) ? max(1_000_000, (int)$argv[1]) : 1_000_000;
$dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yellowtds_uniqueness_plan_' . bin2hex(random_bytes(6)) . '.db';
$targetHash = hash('xxh128', 'query-plan-target', true);
$targetUserid = 'query-plan-user';

try {
    $db = new SQLite3($dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode = MEMORY');
    $db->exec('PRAGMA synchronous = OFF');
    $db->exec('PRAGMA temp_store = MEMORY');
    $db->exec(
        'CREATE TABLE clicks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER NOT NULL,
            flow TEXT NOT NULL,
            time INTEGER NOT NULL,
            userid TEXT NOT NULL,
            unique_hash BLOB NULL,
            unique_flags INTEGER NULL CHECK (unique_flags IS NULL OR unique_flags BETWEEN 0 AND 3)
        )'
    );
    $db->exec(
        "CREATE INDEX idx_unique_campaign_hash ON clicks (campaign_id,unique_hash,time DESC)
         WHERE unique_flags IS NOT NULL AND unique_hash IS NOT NULL"
    );
    $db->exec(
        "CREATE INDEX idx_unique_flow_hash ON clicks (campaign_id,flow,unique_hash,time DESC)
         WHERE unique_flags IS NOT NULL AND unique_hash IS NOT NULL"
    );
    $db->exec(
        "CREATE INDEX idx_unique_campaign_cookie ON clicks (campaign_id,userid,time DESC)
         WHERE unique_flags IS NOT NULL AND userid <> ''"
    );
    $db->exec(
        "CREATE INDEX idx_unique_flow_cookie ON clicks (campaign_id,flow,userid,time DESC)
         WHERE unique_flags IS NOT NULL AND userid <> ''"
    );

    $seed = $db->prepare(
        'WITH RECURSIVE counter(value) AS (
            SELECT 1
            UNION ALL
            SELECT value + 1 FROM counter WHERE value < :row_count
         )
         INSERT INTO clicks (campaign_id,flow,time,userid,unique_hash,unique_flags)
         SELECT
            1,
            CASE WHEN value % 2 = 0 THEN \'Flow A\' ELSE \'Flow B\' END,
            1700000000 + value,
            CASE WHEN value % 100000 = 0 THEN :target_user ELSE \'user-\' || value END,
            CASE WHEN value % 100000 = 0 THEN :target_hash ELSE randomblob(16) END,
            value % 4
         FROM counter'
    );
    $seed->bindValue(':row_count', $rowCount, SQLITE3_INTEGER);
    $seed->bindValue(':target_user', $targetUserid, SQLITE3_TEXT);
    $seed->bindValue(':target_hash', $targetHash, SQLITE3_BLOB);
    $started = microtime(true);
    if ($seed->execute() === false) {
        throw new RuntimeException('Failed to seed benchmark: ' . $db->lastErrorMsg());
    }
    $seedSeconds = microtime(true) - $started;

    $queries = [
        'campaign_hash' => [
            "SELECT 1 FROM clicks WHERE campaign_id = 1 AND unique_hash = x'" . bin2hex($targetHash) . "'
             AND unique_flags IS NOT NULL AND time > 0 ORDER BY time DESC LIMIT 1",
            'idx_unique_campaign_hash',
        ],
        'flow_hash' => [
            "SELECT 1 FROM clicks WHERE campaign_id = 1 AND flow = 'Flow A' AND unique_hash = x'" . bin2hex($targetHash) . "'
             AND unique_flags IS NOT NULL AND time > 0 ORDER BY time DESC LIMIT 1",
            'idx_unique_flow_hash',
        ],
        'campaign_cookie' => [
            "SELECT 1 FROM clicks WHERE campaign_id = 1 AND userid = '" . SQLite3::escapeString($targetUserid) . "' AND userid <> ''
             AND unique_flags IS NOT NULL AND time > 0 ORDER BY time DESC LIMIT 1",
            'idx_unique_campaign_cookie',
        ],
        'flow_cookie' => [
            "SELECT 1 FROM clicks WHERE campaign_id = 1 AND flow = 'Flow A' AND userid = '" . SQLite3::escapeString($targetUserid) . "' AND userid <> ''
             AND unique_flags IS NOT NULL AND time > 0 ORDER BY time DESC LIMIT 1",
            ['idx_unique_flow_cookie'],
        ],
        'combined_hash' => [
            "SELECT
                NOT EXISTS (SELECT 1 FROM clicks AS c WHERE c.campaign_id = 1 AND c.unique_hash = x'" . bin2hex($targetHash) . "'
                    AND c.unique_hash IS NOT NULL AND c.unique_flags IS NOT NULL AND c.time > 0 LIMIT 1),
                NOT EXISTS (SELECT 1 FROM clicks AS f WHERE f.campaign_id = 1 AND f.flow = 'Flow A' AND f.unique_hash = x'" . bin2hex($targetHash) . "'
                    AND f.unique_hash IS NOT NULL AND f.unique_flags IS NOT NULL AND f.time > 0 LIMIT 1)",
            ['idx_unique_campaign_hash', 'idx_unique_flow_hash'],
        ],
        'combined_cookie' => [
            "SELECT
                NOT EXISTS (SELECT 1 FROM clicks AS c WHERE c.campaign_id = 1 AND c.userid = '" . SQLite3::escapeString($targetUserid) . "'
                    AND c.userid <> '' AND c.unique_flags IS NOT NULL AND c.time > 0 LIMIT 1),
                NOT EXISTS (SELECT 1 FROM clicks AS f WHERE f.campaign_id = 1 AND f.flow = 'Flow A' AND f.userid = '" . SQLite3::escapeString($targetUserid) . "'
                    AND f.userid <> '' AND f.unique_flags IS NOT NULL AND f.time > 0 LIMIT 1)",
            ['idx_unique_campaign_cookie', 'idx_unique_flow_cookie'],
        ],
    ];

    foreach (['campaign_hash', 'flow_hash', 'campaign_cookie'] as $name) {
        $queries[$name][1] = [$queries[$name][1]];
    }

    $report = [];
    foreach ($queries as $name => [$sql, $expectedIndexes]) {
        $planRows = [];
        $plan = $db->query('EXPLAIN QUERY PLAN ' . $sql);
        while ($row = $plan->fetchArray(SQLITE3_ASSOC)) {
            $planRows[] = (string)$row['detail'];
        }
        $planText = implode(' | ', $planRows);
        $missingIndex = false;
        foreach ($expectedIndexes as $expectedIndex) {
            if (!str_contains($planText, $expectedIndex)) {
                $missingIndex = true;
                break;
            }
        }
        if ($missingIndex || preg_match('/\bSCAN (clicks|c|f)\b/i', $planText)) {
            throw new RuntimeException("Unexpected $name plan: $planText");
        }

        $durations = [];
        for ($i = 0; $i < 500; $i++) {
            $queryStarted = hrtime(true);
            $db->querySingle($sql);
            $durations[] = (hrtime(true) - $queryStarted) / 1_000_000;
        }
        sort($durations, SORT_NUMERIC);
        $report[$name] = [
            'plan' => $planText,
            'p95_ms' => $durations[(int)floor((count($durations) - 1) * 0.95)],
        ];
    }

    echo json_encode([
        'rows' => $rowCount,
        'seed_seconds' => round($seedSeconds, 3),
        'queries' => $report,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} finally {
    if (isset($db)) {
        $db->close();
    }
    @unlink($dbPath);
    @unlink($dbPath . '-wal');
    @unlink($dbPath . '-shm');
}
