<?php

/**
 * Destructively rebuild the configured YellowTDS SQLite database and seed one
 * self-contained Events demo campaign.
 *
 * This is deliberately a one-shot operator tool. It has no migration or
 * compatibility path: the replacement database is created from the current
 * db/db.sql and db/common.json files.
 *
 * It is intended only for the disposable ywbtest/test database. The file lock
 * prevents two rebuild commands from overlapping, but the script does not
 * coordinate with live PHP traffic or manage PHP-FPM.
 *
 * Usage:
 *   php tests/load/rebuild_events_demo.php --confirm=REBUILD:clicks.db
 *   php tests/load/rebuild_events_demo.php --confirm=REBUILD:clicks.db --domain=ywbtest.site
 *   php tests/load/rebuild_events_demo.php --confirm=REBUILD:clicks.db --backup-dir=/safe/local/path
 */

declare(strict_types=1);

const EVENTS_DEMO_CAMPAIGN = 'Events Demo — Flow → Step → Landing';
const EVENTS_DEMO_FLOW = 'Events Demo Flow';
const EVENTS_DEMO_INTRO = 'events-demo-intro';
const EVENTS_DEMO_OFFER = 'events-demo-offer';
const EVENTS_DEMO_CLICK_COUNT = 120;

final class EventsDemoPartialSwapException extends RuntimeException
{
}

function fail(string $message, int $code = 2): never
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit($code);
}

function json_file(string $path): array
{
    $contents = @file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException("Cannot read JSON source: {$path}");
    }
    $decoded = json_decode($contents, true, 128, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException("JSON source must contain an object: {$path}");
    }
    return $decoded;
}

function safe_basename(string $value, string $label): string
{
    if (
        $value === ''
        || $value === '.'
        || $value === '..'
        || basename($value) !== $value
        || preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $value) !== 1
    ) {
        throw new RuntimeException("{$label} must be a plain file or directory name without a path.");
    }
    return $value;
}

function absolute_path(string $path, string $relativeRoot): string
{
    $isAbsolute = preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{2}|\/)/', $path) === 1;
    return $isAbsolute
        ? rtrim($path, "/\\")
        : $relativeRoot . DIRECTORY_SEPARATOR . trim($path, "/\\");
}

function ensure_directory(string $path): void
{
    if (is_link($path)) {
        throw new RuntimeException("Refusing to use a symlinked directory: {$path}");
    }
    if (!is_dir($path) && !@mkdir($path, 0750, true) && !is_dir($path)) {
        throw new RuntimeException("Cannot create directory: {$path}");
    }
    if (!is_writable($path)) {
        throw new RuntimeException("Directory is not writable: {$path}");
    }
}

function sqlite_quick_check(SQLite3 $database, string $label): string
{
    $result = $database->querySingle('PRAGMA quick_check');
    if ($result !== 'ok') {
        throw new RuntimeException("{$label} failed PRAGMA quick_check: " . var_export($result, true));
    }
    return 'ok';
}

function sqlite_counts(SQLite3 $database): array
{
    $tables = ['campaigns', 'clicks', 'click_steps', 'conversions', 'common'];
    $counts = [];
    foreach ($tables as $table) {
        $counts[$table] = (int)$database->querySingle("SELECT COUNT(*) FROM {$table}");
    }
    $counts['event_steps'] = (int)$database->querySingle(
        "SELECT COUNT(*) FROM click_steps WHERE events <> '{}'"
    );
    $counts['performance_steps'] = (int)$database->querySingle(
        "SELECT COUNT(*) FROM click_steps WHERE json_type(events, '$.performance') = 'object'"
    );
    return $counts;
}

function prepare_statement(SQLite3 $database, string $sql): SQLite3Stmt
{
    $statement = $database->prepare($sql);
    if ($statement === false) {
        throw new RuntimeException('Failed to prepare seed statement: ' . $database->lastErrorMsg());
    }
    return $statement;
}

function execute_statement(SQLite3Stmt $statement, SQLite3 $database): void
{
    $result = $statement->execute();
    if ($result === false) {
        throw new RuntimeException('Failed to execute seed statement: ' . $database->lastErrorMsg());
    }
    $result->finalize();
}

function event_payload(int $index, int $step): array
{
    $offset = ($index * 37) + ($step * 113);
    $events = [
        'performance' => [
            'ttfb' => 82 + ($offset % 210),
            'fcp' => 510 + ($offset % 640),
            'lcp' => 980 + ($offset % 1450),
            'inp' => 58 + ($offset % 185),
            'cls' => round(0.008 + (($offset % 115) / 1000), 4),
        ],
    ];

    if (($index + $step) % 11 === 0) {
        unset($events['performance']);
    }
    if (($index + $step) % 10 !== 0) {
        $events['scroll_25'] = 1800 + ($offset % 2400);
    }
    if (($index + ($step * 2)) % 5 !== 0) {
        $events['scroll_50'] = 4200 + ($offset % 5200);
    }
    if (($index + $step) % 3 === 0) {
        $events['scroll_90'] = 9600 + ($offset % 11200);
    }
    if (($index + $step) % 7 !== 0) {
        $events['stay_10s'] = 10000 + ($offset % 650);
    }
    if (($index + $step) % 3 === 0) {
        $events['stay_30s'] = 30000 + ($offset % 900);
    }
    if (($index + ($step * 3)) % 8 === 0) {
        $events['stay_60s'] = 60000 + ($offset % 1100);
    }

    if ($step === 0 && $index % 5 !== 0) {
        $events['cta_click'] = 3200 + ($offset % 6800);
    }
    if ($step === 1 && $index % 4 === 0) {
        $events['demo_signup'] = 7600 + ($offset % 14500);
    }

    return $events;
}

function build_campaign(array $defaults, string $domain): array
{
    $defaults['domains'] = [$domain];
    $defaults['apikey'] = bin2hex(random_bytes(16));
    $defaults['saveuserflow'] = false;
    $defaults['uniqueness'] = [
        'enabled' => true,
        'method' => 'cookie_ip_ua',
        'ttl_hours' => 24,
        'get_parameter' => '',
    ];
    $defaults['white'] = [
        'action' => 'error',
        'folders' => [],
        'redirect' => ['type' => 302, 'urls' => []],
        'curls' => [],
        'errorcodes' => ['404'],
        'domainfilter' => ['use' => false, 'domains' => []],
        'loadmode' => [],
        'filters' => [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'country',
                'field' => 'country',
                'type' => 'string',
                'input' => 'text',
                'operator' => 'in',
                'value' => 'AQ',
            ]],
            'valid' => true,
        ],
    ];
    $defaults['black'] = [
        'jsconnect' => 'replace',
        'jsbotdetection' => [
            'enabled' => false,
            'events' => [],
            'timeout' => 5000,
            'timezone' => ['min' => -12, 'max' => 14],
        ],
        'flows' => [[
            'name' => EVENTS_DEMO_FLOW,
            'filters' => [],
            'distribution' => 'equal',
            'optimize_for' => 'Purchase',
            'optimize_mode' => 'funnels',
            'steps' => [
                [
                    'action' => 'folder',
                    'folders' => [[
                        'name' => EVENTS_DEMO_INTRO,
                        'loadtype' => 'base',
                        'weight' => 100,
                        'mvt' => ['enabled' => false, 'tests' => []],
                    ]],
                    'redirect' => ['urls' => [], 'type' => 302],
                ],
                [
                    'action' => 'folder',
                    'folders' => [[
                        'name' => EVENTS_DEMO_OFFER,
                        'loadtype' => 'base',
                        'weight' => 100,
                        'mvt' => ['enabled' => false, 'tests' => []],
                    ]],
                    'redirect' => ['urls' => [], 'type' => 302],
                ],
            ],
        ]],
    ];
    $defaults['events'] = [
        'scroll' => ['use' => true, 'thresholds' => [25, 50, 90]],
        'time' => ['use' => true, 'thresholds' => [10, 30, 60]],
        'performance' => ['use' => true],
        'custom' => ['cta_click', 'demo_signup'],
    ];
    $defaults['statistics']['timezone'] = 'Europe/Samara';
    $defaults['statistics']['tables'] = [[
        'name' => 'Events: Flow → Step → Landing',
        'columns' => array_map(
            static fn(string $field): array => ['field' => $field, 'width' => -1],
            [
                'flow',
                'step',
                'landing',
                'clicks',
                'uniques',
                'conversion',
                'event.scroll_50.count',
                'event.scroll_90.count',
                'event.stay_10s.count',
                'event.stay_30s.count',
                'event.cta_click.count',
                'event.demo_signup.count',
                'performance.ttfb.p75',
                'performance.fcp.p75',
                'performance.lcp.p75',
                'performance.inp.p75',
                'performance.cls.p75',
            ]
        ),
        'groupby' => ['flow', 'step', 'landing'],
        'filters' => [],
        'orderby' => [],
    ]];
    return $defaults;
}

function seed_replacement(
    string $path,
    string $schemaPath,
    array $common,
    array $campaign
): array {
    $schema = @file_get_contents($schemaPath);
    if ($schema === false || trim($schema) === '') {
        throw new RuntimeException("Cannot read current schema: {$schemaPath}");
    }

    $database = new SQLite3($path, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
    $database->busyTimeout(10000);
    try {
        if (!$database->exec($schema)) {
            throw new RuntimeException('Failed to create replacement schema: ' . $database->lastErrorMsg());
        }
        if (!$database->exec('PRAGMA foreign_keys = ON')) {
            throw new RuntimeException('Failed to enable replacement foreign keys.');
        }
        if (!$database->exec('BEGIN IMMEDIATE')) {
            throw new RuntimeException('Failed to start seed transaction: ' . $database->lastErrorMsg());
        }

        try {
            $commonStatement = prepare_statement($database, 'INSERT INTO common (settings) VALUES (:settings)');
            $commonStatement->bindValue(
                ':settings',
                json_encode($common, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                SQLITE3_TEXT
            );
            execute_statement($commonStatement, $database);

            $campaignStatement = prepare_statement(
                $database,
                'INSERT INTO campaigns (name, settings) VALUES (:name, :settings)'
            );
            $campaignStatement->bindValue(':name', EVENTS_DEMO_CAMPAIGN, SQLITE3_TEXT);
            $campaignStatement->bindValue(
                ':settings',
                json_encode($campaign, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                SQLITE3_TEXT
            );
            execute_statement($campaignStatement, $database);
            $campaignId = (int)$database->lastInsertRowID();

            $clickStatement = prepare_statement(
                $database,
                'INSERT INTO clicks '
                . '(campaign_id,time,ip,country,lang,os,osver,device,brand,model,isp,client,clientver,ua,'
                . 'userid,unique_hash,unique_flags,clickid,flow,path,step,params,leaddata,status,cost,payout) '
                . 'VALUES '
                . '(:campaign,:time,:ip,:country,:lang,:os,:osver,:device,:brand,:model,:isp,:client,'
                . ':clientver,:ua,:userid,:unique_hash,:unique_flags,:clickid,:flow,:path,:step,:params,'
                . ':leaddata,:status,:cost,:payout)'
            );
            $stepStatement = prepare_statement(
                $database,
                'INSERT INTO click_steps (clickid,step,variant,time,events) '
                . 'VALUES (:clickid,:step,:variant,:time,:events)'
            );
            $conversionStatement = prepare_statement(
                $database,
                'INSERT INTO conversions '
                . '(clickid,campaign_id,flow,step,time,status,raw_status,source,tid,tid_parameter,payout,currency,'
                . 'is_initial,changes_status,status_occurrence) '
                . 'VALUES (:clickid,:campaign,:flow,:step,:time,:status,:raw_status,:source,:tid,:tid_parameter,:payout,'
                . ':currency,:initial,:changes_status,:occurrence)'
            );

            $countries = ['DE', 'US', 'GB', 'PL', 'FR', 'NL'];
            $now = time();
            for ($index = 0; $index < EVENTS_DEMO_CLICK_COUNT; $index++) {
                $clickid = sprintf('events-demo-%03d-%s', $index + 1, substr(hash('sha256', (string)$index), 0, 12));
                $reachedOffer = $index % 5 !== 0;
                $converted = $reachedOffer && $index % 6 === 0;
                $purchased = $converted && $index % 12 === 0;
                $status = $purchased ? 'Purchase' : ($converted ? 'Lead' : null);
                $payout = $purchased ? 38.50 : 0.0;
                $timestamp = $now - (($index % 14) * 86400) - (($index * 317) % 72000);
                $device = $index % 4 === 0 ? 'desktop' : 'smartphone';
                $os = $device === 'desktop' ? 'Windows' : ($index % 2 === 0 ? 'Android' : 'iOS');
                $brand = $device === 'desktop' ? '' : ($os === 'iOS' ? 'Apple' : 'Samsung');

                $clickStatement->reset();
                $clickStatement->bindValue(':campaign', $campaignId, SQLITE3_INTEGER);
                $clickStatement->bindValue(':time', $timestamp, SQLITE3_INTEGER);
                $clickStatement->bindValue(':ip', '198.51.100.' . (($index % 250) + 1), SQLITE3_TEXT);
                $clickStatement->bindValue(':country', $countries[$index % count($countries)], SQLITE3_TEXT);
                $clickStatement->bindValue(':lang', $index % 3 === 0 ? 'de' : 'en', SQLITE3_TEXT);
                $clickStatement->bindValue(':os', $os, SQLITE3_TEXT);
                $clickStatement->bindValue(':osver', $device === 'desktop' ? 11.0 : 17.0, SQLITE3_FLOAT);
                $clickStatement->bindValue(':device', $device, SQLITE3_TEXT);
                $clickStatement->bindValue(':brand', $brand, SQLITE3_TEXT);
                $clickStatement->bindValue(':model', $device === 'desktop' ? '' : 'Demo device', SQLITE3_TEXT);
                $clickStatement->bindValue(':isp', 'Demo ISP ' . (($index % 4) + 1), SQLITE3_TEXT);
                $clickStatement->bindValue(':client', $index % 5 === 0 ? 'Firefox' : 'Chrome', SQLITE3_TEXT);
                $clickStatement->bindValue(':clientver', 126.0 + ($index % 8), SQLITE3_FLOAT);
                $clickStatement->bindValue(':ua', 'YellowTDS Events demo browser', SQLITE3_TEXT);
                $clickStatement->bindValue(':userid', 'events-demo-user-' . ($index + 1), SQLITE3_TEXT);
                $clickStatement->bindValue(
                    ':unique_hash',
                    hash('sha256', 'events-demo-user-' . ($index + 1), true),
                    SQLITE3_BLOB
                );
                $clickStatement->bindValue(':unique_flags', 3, SQLITE3_INTEGER);
                $clickStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
                $clickStatement->bindValue(':flow', EVENTS_DEMO_FLOW, SQLITE3_TEXT);
                $clickStatement->bindValue(
                    ':path',
                    json_encode([EVENTS_DEMO_INTRO, EVENTS_DEMO_OFFER], JSON_THROW_ON_ERROR),
                    SQLITE3_TEXT
                );
                $clickStatement->bindValue(':step', $reachedOffer ? 1 : 0, SQLITE3_INTEGER);
                $clickStatement->bindValue(
                    ':params',
                    json_encode([
                        'utm_source' => $index % 2 === 0 ? 'demo_search' : 'demo_social',
                        'utm_campaign' => 'events_demo',
                        'creative' => 'card_' . (($index % 4) + 1),
                    ], JSON_THROW_ON_ERROR),
                    SQLITE3_TEXT
                );
                $clickStatement->bindValue(':leaddata', null, SQLITE3_NULL);
                $clickStatement->bindValue(
                    ':status',
                    $status,
                    $status === null ? SQLITE3_NULL : SQLITE3_TEXT
                );
                $clickStatement->bindValue(':cost', round(0.18 + (($index % 11) * 0.035), 3), SQLITE3_FLOAT);
                $clickStatement->bindValue(':payout', $payout, SQLITE3_FLOAT);
                execute_statement($clickStatement, $database);

                $stepCount = $reachedOffer ? 2 : 1;
                for ($step = 0; $step < $stepCount; $step++) {
                    $stepStatement->reset();
                    $stepStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
                    $stepStatement->bindValue(':step', $step, SQLITE3_INTEGER);
                    $stepStatement->bindValue(
                        ':variant',
                        $step === 0 ? EVENTS_DEMO_INTRO : EVENTS_DEMO_OFFER,
                        SQLITE3_TEXT
                    );
                    $stepStatement->bindValue(':time', $timestamp + ($step * (8 + ($index % 25))), SQLITE3_INTEGER);
                    $stepStatement->bindValue(
                        ':events',
                        json_encode(event_payload($index, $step), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                        SQLITE3_TEXT
                    );
                    execute_statement($stepStatement, $database);
                }

                if ($status !== null) {
                    $conversionStatement->reset();
                    $conversionStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
                    $conversionStatement->bindValue(':campaign', $campaignId, SQLITE3_INTEGER);
                    $conversionStatement->bindValue(':flow', EVENTS_DEMO_FLOW, SQLITE3_TEXT);
                    $conversionStatement->bindValue(':step', 1, SQLITE3_INTEGER);
                    $conversionStatement->bindValue(':time', $timestamp + 180, SQLITE3_INTEGER);
                    $conversionStatement->bindValue(':status', $status, SQLITE3_TEXT);
                    $conversionStatement->bindValue(':raw_status', strtolower($status), SQLITE3_TEXT);
                    $conversionStatement->bindValue(':source', 'site_script', SQLITE3_TEXT);
                    $conversionStatement->bindValue(':tid', 'demo-' . ($index + 1), SQLITE3_TEXT);
                    $conversionStatement->bindValue(':tid_parameter', 'tid', SQLITE3_TEXT);
                    $conversionStatement->bindValue(':payout', $payout, SQLITE3_FLOAT);
                    $conversionStatement->bindValue(':currency', 'USD', SQLITE3_TEXT);
                    $conversionStatement->bindValue(':initial', 1, SQLITE3_INTEGER);
                    $conversionStatement->bindValue(':changes_status', 1, SQLITE3_INTEGER);
                    $conversionStatement->bindValue(':occurrence', 1, SQLITE3_INTEGER);
                    execute_statement($conversionStatement, $database);
                }
            }

            if (!$database->exec('COMMIT')) {
                throw new RuntimeException('Failed to commit seed transaction: ' . $database->lastErrorMsg());
            }
        } catch (Throwable $error) {
            $database->exec('ROLLBACK');
            throw $error;
        }

        $quickCheck = sqlite_quick_check($database, 'Replacement database');
        $counts = sqlite_counts($database);
        $expectedSteps = EVENTS_DEMO_CLICK_COUNT + (EVENTS_DEMO_CLICK_COUNT - intdiv(EVENTS_DEMO_CLICK_COUNT + 4, 5));
        if (
            $counts['campaigns'] !== 1
            || $counts['clicks'] !== EVENTS_DEMO_CLICK_COUNT
            || $counts['click_steps'] !== $expectedSteps
            || $counts['common'] !== 1
        ) {
            throw new RuntimeException('Replacement database has unexpected seed counts.');
        }

        $database->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        $journalMode = strtolower((string)$database->querySingle('PRAGMA journal_mode = DELETE'));
        if ($journalMode !== 'delete') {
            throw new RuntimeException("Could not make replacement database self-contained; journal mode is {$journalMode}.");
        }
        return ['quick_check' => $quickCheck, 'counts' => $counts];
    } finally {
        $database->close();
    }
}

function backup_database(string $sourcePath, string $destinationPath): void
{
    $source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
    $destination = new SQLite3($destinationPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
    $source->busyTimeout(10000);
    $destination->busyTimeout(10000);
    try {
        if (!$source->backup($destination)) {
            throw new RuntimeException('SQLite online backup failed: ' . $source->lastErrorMsg());
        }
        sqlite_quick_check($destination, 'Database backup');
    } finally {
        $destination->close();
        $source->close();
    }
    clearstatcache(true, $destinationPath);
    if (!is_file($destinationPath) || filesize($destinationPath) <= 0) {
        throw new RuntimeException('Database backup is missing or empty.');
    }
}

function quiesce_sqlite_database(string $databasePath, string $label): void
{
    $database = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
    $database->busyTimeout(10000);
    try {
        $checkpoint = $database->querySingle('PRAGMA wal_checkpoint(TRUNCATE)', true);
        if (!is_array($checkpoint) || (int)($checkpoint['busy'] ?? 1) !== 0) {
            throw new RuntimeException("{$label} WAL checkpoint is busy.");
        }
        $mode = strtolower((string)$database->querySingle('PRAGMA journal_mode = DELETE'));
        if ($mode !== 'delete') {
            throw new RuntimeException("Cannot quiesce {$label}; journal mode is {$mode}.");
        }
        if (!$database->exec('BEGIN EXCLUSIVE')) {
            throw new RuntimeException("Cannot acquire an exclusive transaction for {$label}.");
        }
        if (!$database->exec('COMMIT')) {
            throw new RuntimeException("Cannot release the exclusive transaction for {$label}.");
        }
    } finally {
        $database->close();
    }

    foreach (['-wal', '-shm'] as $suffix) {
        $sidecar = $databasePath . $suffix;
        clearstatcache(true, $sidecar);
        if (is_file($sidecar)) {
            throw new RuntimeException("SQLite sidecar remains for {$label}: {$sidecar}");
        }
    }
}

function remove_created_demo_landings(array $landingDirectories): void
{
    foreach (array_reverse($landingDirectories) as $landingDirectory) {
        if (!file_exists($landingDirectory) && !is_link($landingDirectory)) {
            continue;
        }
        if (!is_dir($landingDirectory) || is_link($landingDirectory)) {
            throw new RuntimeException(
                "Created demo landing is no longer a regular directory: {$landingDirectory}"
            );
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($landingDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $removed = $item->isDir() && !$item->isLink()
                ? @rmdir($path)
                : @unlink($path);
            if (!$removed) {
                throw new RuntimeException("Cannot remove created demo landing path: {$path}");
            }
        }
        if (!@rmdir($landingDirectory)) {
            throw new RuntimeException("Cannot remove created demo landing: {$landingDirectory}");
        }
    }
}

function existing_database_paths(array $paths): array
{
    return array_values(array_filter(
        array_unique($paths),
        static fn(string $path): bool => is_file($path) || is_link($path)
    ));
}

function remove_sqlite_artifacts(string $databasePath): void
{
    foreach (['-wal', '-shm', '-journal', ''] as $suffix) {
        $path = $databasePath . $suffix;
        if ((is_file($path) || is_link($path)) && !@unlink($path)) {
            throw new RuntimeException("Cannot remove SQLite artifact: {$path}");
        }
    }
}

function release_events_demo_database_connections(): void
{
    if (array_key_exists('db', $GLOBALS)) {
        unset($GLOBALS['db']);
    }
    gc_collect_cycles();
}

function rebuild_events_demo_runtime_cache(string $appRoot): void
{
    global $db;
    require_once $appRoot . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'db.php';
    release_events_demo_database_connections();

    $cacheDatabase = new Db();
    try {
        if (!$cacheDatabase->rebuild_runtime_cache()) {
            throw new RuntimeException('Runtime campaign cache rebuild failed.');
        }
    } finally {
        unset($cacheDatabase);
        gc_collect_cycles();
    }
}

function publish_events_demo_database(
    string $databasePath,
    string $replacementPath,
    string $oldStagingPath,
    ?callable $renameFile = null
): void {
    $renameFile ??= static fn(string $source, string $destination): bool =>
        @rename($source, $destination);

    if (!$renameFile($databasePath, $oldStagingPath)) {
        throw new RuntimeException('Failed to stage the old database for atomic replacement.');
    }
    if ($renameFile($replacementPath, $databasePath)) {
        return;
    }
    if ($renameFile($oldStagingPath, $databasePath)) {
        throw new RuntimeException(
            'Failed to publish replacement database; old database was restored.'
        );
    }

    throw new EventsDemoPartialSwapException(
        'Failed to publish replacement database and failed to restore the live path. '
        . "Old database: {$oldStagingPath}. Replacement database: {$replacementPath}."
    );
}

/**
 * Restore the old database after a failure that occurred after the atomic
 * replacement. On success the failed replacement is removed. On failure every
 * database file is kept for manual recovery and its exact path is reported.
 */
function rollback_events_demo_swap(
    string $databasePath,
    string $oldStagingPath,
    string $failedReplacementPath,
    array $createdLandingDirectories,
    callable $rebuildRuntimeCache
): void {
    $rollbackErrors = [];
    $databaseRestored = false;
    release_events_demo_database_connections();

    try {
        if (!is_file($databasePath) || !is_file($oldStagingPath)) {
            throw new RuntimeException('Both the published and old staged databases are required.');
        }
        quiesce_sqlite_database($databasePath, 'failed replacement database');
        if (!@rename($databasePath, $failedReplacementPath)) {
            throw new RuntimeException(
                "Cannot preserve failed replacement at {$failedReplacementPath}"
            );
        }
        if (!@rename($oldStagingPath, $databasePath)) {
            throw new RuntimeException("Cannot restore old database to {$databasePath}");
        }
        $databaseRestored = true;
    } catch (Throwable $error) {
        $rollbackErrors[] = $error->getMessage();
    }

    $restoredReady = false;
    if ($databaseRestored) {
        try {
            $restored = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
            $restored->busyTimeout(10000);
            try {
                sqlite_quick_check($restored, 'Restored database');
                $restored->exec('PRAGMA journal_mode = WAL');
                $restored->exec('PRAGMA synchronous = NORMAL');
            } finally {
                $restored->close();
            }
            $restoredReady = true;
        } catch (Throwable $error) {
            $rollbackErrors[] = $error->getMessage();
        }

        if ($restoredReady) {
            try {
                $cacheResult = $rebuildRuntimeCache();
                if ($cacheResult === false) {
                    throw new RuntimeException('Previous runtime cache rebuild returned false.');
                }
            } catch (Throwable $error) {
                $rollbackErrors[] = $error->getMessage();
            }
        }
    }

    try {
        remove_created_demo_landings($createdLandingDirectories);
    } catch (Throwable $error) {
        $rollbackErrors[] = $error->getMessage();
    }

    if ($rollbackErrors !== []) {
        $preserved = existing_database_paths([
            $databasePath,
            $oldStagingPath,
            $failedReplacementPath,
        ]);
        throw new RuntimeException(
            "Rollback failed: " . implode(' ', $rollbackErrors)
            . ' Preserved database paths: '
            . ($preserved === [] ? '(none found)' : implode(', ', $preserved))
        );
    }

    try {
        remove_sqlite_artifacts($failedReplacementPath);
    } catch (Throwable $error) {
        $preserved = existing_database_paths([
            $databasePath,
            $oldStagingPath,
            $failedReplacementPath,
        ]);
        throw new RuntimeException(
            $error->getMessage() . '. Preserved database paths: ' . implode(', ', $preserved),
            0,
            $error
        );
    }
}

function intro_html(): string
{
    return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>YellowTDS Events Demo</title>
  <style>
    body{margin:0;font:17px/1.6 system-ui,sans-serif;color:#202124;background:#fff9d9}
    main{max-width:760px;margin:auto;padding:10vh 24px 20vh}
    .card{background:#fff;border:1px solid #eadf9c;border-radius:18px;padding:36px;box-shadow:0 16px 50px #62540018}
    .tag{color:#745f00;font-weight:700}.cta{display:inline-block;margin-top:28px;padding:14px 22px;border-radius:10px;background:#ffd400;color:#161616;text-decoration:none;font-weight:800}
    .spacer{height:65vh}.hint{color:#6b6b6b}
  </style>
</head>
<body>
<main>
  <section class="card">
    <div class="tag">Step 1 · Intro landing</div>
    <h1>Events demo funnel</h1>
    <p>This local landing records scroll depth, visible time, Web Vitals and a custom CTA event.</p>
    <p class="hint">Scroll down, stay on the page for a moment, then continue to the offer step.</p>
  </section>
  <div class="spacer"></div>
  <section class="card">
    <h2>Ready for step 2?</h2>
    <a class="cta" href="{next}" onclick="if(window.ytdsEvent){window.ytdsEvent('cta_click').catch(function(){})}">Open demo offer</a>
  </section>
</main>
</body>
</html>
HTML;
}

function offer_html(): string
{
    return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>YellowTDS Events Demo Offer</title>
  <style>
    body{margin:0;font:17px/1.6 system-ui,sans-serif;color:#f7f7f7;background:#181a1f}
    main{max-width:760px;margin:auto;padding:12vh 24px 65vh}.card{background:#24272e;border:1px solid #393d47;border-radius:18px;padding:36px}
    .tag{color:#ffd400;font-weight:700}button{padding:14px 22px;border:0;border-radius:10px;background:#ffd400;color:#161616;font-weight:800;cursor:pointer}
    #thanks{display:none;color:#a9e7b3;font-weight:700}
  </style>
</head>
<body>
<main>
  <section class="card">
    <div class="tag">Step 2 · Offer landing</div>
    <h1>Demo offer reached</h1>
    <p>This second landing gives Flow → Step → Landing statistics a distinct reached-step cohort.</p>
    <button type="button" onclick="if(window.ytdsEvent){window.ytdsEvent('demo_signup').catch(function(){})}this.hidden=true;document.getElementById('thanks').style.display='block'">Send demo signup event</button>
    <p id="thanks">Demo event sent. You can inspect it in Statistics.</p>
  </section>
</main>
</body>
</html>
HTML;
}

if (defined('YELLOWTDS_REBUILD_EVENTS_DEMO_LIBRARY_ONLY')) {
    return;
}

if (PHP_SAPI !== 'cli') {
    fail('This script may only run from the command line.');
}
if (!class_exists('SQLite3')) {
    fail('The PHP SQLite3 extension is required.');
}

$options = getopt('', ['confirm:', 'backup-dir:', 'domain:', 'help']);
if (isset($options['help'])) {
    echo "Usage:\n";
    echo "  php tests/load/rebuild_events_demo.php --confirm=REBUILD:<configured-db-filename>\n";
    echo "      [--domain=ywbtest.site] [--backup-dir=/safe/local/path]\n\n";
    echo "The command backs up, then fully replaces the configured SQLite DB and creates\n";
    echo "two dedicated HTML landing folders. settings.local.php is copied to the backup\n";
    echo "and verified byte-for-byte unchanged after the rebuild.\n";
    exit(0);
}

$appRoot = realpath(__DIR__ . '/../../code');
if ($appRoot === false || !is_dir($appRoot)) {
    fail('Cannot resolve the YellowTDS application root.');
}
$settingsPath = $appRoot . DIRECTORY_SEPARATOR . 'settings.local.php';
if (!is_file($settingsPath) || !is_readable($settingsPath)) {
    fail('settings.local.php must exist and be readable; refusing to fall back to defaults.');
}
$settingsBytes = file_get_contents($settingsPath);
if ($settingsBytes === false) {
    fail('Cannot snapshot settings.local.php.');
}
$settingsHash = hash('sha256', $settingsBytes);

require_once $appRoot . DIRECTORY_SEPARATOR . 'settings.php';
global $cloSettings;
$rebuildRuntimeCache = static function () use ($appRoot): void {
    rebuild_events_demo_runtime_cache($appRoot);
};

try {
    $dbFilename = safe_basename((string)($cloSettings['dbConnection'] ?? ''), 'dbConnection');
    $confirmation = $options['confirm'] ?? null;
    if (!is_string($confirmation) || !hash_equals('REBUILD:' . $dbFilename, $confirmation)) {
        throw new RuntimeException(
            "Explicit confirmation required: --confirm=REBUILD:{$dbFilename}"
        );
    }

    $domain = trim((string)($options['domain'] ?? 'ywbtest.site'));
    if (
        $domain === ''
        || str_contains($domain, '://')
        || str_contains($domain, '/')
        || preg_match('/^(?:localhost|[A-Za-z0-9](?:[A-Za-z0-9.-]{0,251}[A-Za-z0-9]))(?::\d{1,5})?$/', $domain) !== 1
    ) {
        throw new RuntimeException('Invalid --domain; provide one host with an optional port.');
    }

    $dbDirectory = realpath($appRoot . DIRECTORY_SEPARATOR . 'db');
    if ($dbDirectory === false) {
        throw new RuntimeException('Cannot resolve the application db directory.');
    }
    $databasePath = $dbDirectory . DIRECTORY_SEPARATOR . $dbFilename;
    if (!is_file($databasePath) || is_link($databasePath)) {
        throw new RuntimeException('Configured database must be an existing regular non-symlink file.');
    }
    $databaseRealPath = realpath($databasePath);
    if ($databaseRealPath === false || dirname($databaseRealPath) !== $dbDirectory) {
        throw new RuntimeException('Configured database resolves outside the application db directory.');
    }

    $configuredBackupDir = safe_basename(
        (string)($cloSettings['backupDir'] ?? 'backups'),
        'backupDir'
    );
    $backupOption = $options['backup-dir'] ?? $configuredBackupDir;
    if (!is_string($backupOption) || trim($backupOption) === '') {
        throw new RuntimeException('--backup-dir must contain a path.');
    }
    $backupDirectory = absolute_path(trim($backupOption), $appRoot);
    ensure_directory($backupDirectory);
    $backupDirectoryReal = realpath($backupDirectory);
    if ($backupDirectoryReal === false || $backupDirectoryReal === $dbDirectory) {
        throw new RuntimeException('Backup directory must resolve outside the live db directory.');
    }

    $schemaPath = $dbDirectory . DIRECTORY_SEPARATOR . 'db.sql';
    $commonPath = $dbDirectory . DIRECTORY_SEPARATOR . 'common.json';
    $defaultsPath = $dbDirectory . DIRECTORY_SEPARATOR . 'default.json';
    foreach ([$schemaPath, $commonPath, $defaultsPath] as $sourcePath) {
        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            throw new RuntimeException("Required current source is not readable: {$sourcePath}");
        }
    }
    $common = json_file($commonPath);
    $campaign = build_campaign(json_file($defaultsPath), $domain);

    $cachingName = safe_basename((string)($cloSettings['cachingDir'] ?? 'caching'), 'cachingDir');
    $landingsDirectory = $appRoot . DIRECTORY_SEPARATOR . $cachingName . DIRECTORY_SEPARATOR . 'landings';
    ensure_directory($landingsDirectory);
    $landingPaths = [
        $landingsDirectory . DIRECTORY_SEPARATOR . EVENTS_DEMO_INTRO => intro_html(),
        $landingsDirectory . DIRECTORY_SEPARATOR . EVENTS_DEMO_OFFER => offer_html(),
    ];
    foreach (array_keys($landingPaths) as $landingPath) {
        if (file_exists($landingPath) || is_link($landingPath)) {
            throw new RuntimeException(
                "Dedicated demo landing already exists; refusing to overwrite it: {$landingPath}"
            );
        }
    }

    $suffix = gmdate('Ymd-His') . '-' . getmypid() . '-' . bin2hex(random_bytes(3));
    $replacementPath = $dbDirectory . DIRECTORY_SEPARATOR . ".events-demo-replacement-{$suffix}.sqlite";
    $oldStagingPath = $dbDirectory . DIRECTORY_SEPARATOR . ".events-demo-old-{$suffix}.sqlite";
    $failedReplacementPath = $dbDirectory . DIRECTORY_SEPARATOR . ".events-demo-failed-{$suffix}.sqlite";
    $backupPath = $backupDirectoryReal . DIRECTORY_SEPARATOR . "{$dbFilename}.pre-events-demo-{$suffix}.sqlite";
    $settingsBackupPath = $backupDirectoryReal . DIRECTORY_SEPARATOR . "settings.local.pre-events-demo-{$suffix}.php";
    foreach ([
        $replacementPath,
        $oldStagingPath,
        $failedReplacementPath,
        $backupPath,
        $settingsBackupPath,
    ] as $newPath) {
        if (file_exists($newPath) || is_link($newPath)) {
            throw new RuntimeException("Refusing to overwrite generated path: {$newPath}");
        }
    }

    $lockPath = $dbDirectory . DIRECTORY_SEPARATOR . '.rebuild-events-demo.lock';
    $lock = fopen($lockPath, 'c+');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException('Another rebuild process holds the rebuild lock.');
    }

    $createdLandingDirectories = [];
    $databaseSwapped = false;
    try {
        $replacementValidation = seed_replacement(
            $replacementPath,
            $schemaPath,
            $common,
            $campaign
        );

        backup_database($databasePath, $backupPath);
        if (!@copy($settingsPath, $settingsBackupPath)) {
            throw new RuntimeException('Failed to copy settings.local.php to the backup directory.');
        }
        if (hash_file('sha256', $settingsBackupPath) !== $settingsHash) {
            throw new RuntimeException('settings.local.php backup hash mismatch.');
        }

        foreach ($landingPaths as $landingPath => $html) {
            if (!@mkdir($landingPath, 0755)) {
                throw new RuntimeException("Failed to create demo landing directory: {$landingPath}");
            }
            $createdLandingDirectories[] = $landingPath;
            $temporaryHtml = $landingPath . DIRECTORY_SEPARATOR . '.index.html.tmp-' . getmypid();
            if (file_put_contents($temporaryHtml, $html, LOCK_EX) === false) {
                throw new RuntimeException("Failed to write demo landing: {$landingPath}");
            }
            if (!@rename($temporaryHtml, $landingPath . DIRECTORY_SEPARATOR . 'index.html')) {
                @unlink($temporaryHtml);
                throw new RuntimeException("Failed to publish demo landing: {$landingPath}");
            }
        }

        quiesce_sqlite_database($databasePath, 'live database');

        publish_events_demo_database(
            $databasePath,
            $replacementPath,
            $oldStagingPath
        );
        $databaseSwapped = true;

        $final = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
        $final->busyTimeout(10000);
        try {
            $final->exec('PRAGMA foreign_keys = ON');
            $finalQuickCheck = sqlite_quick_check($final, 'Published database');
            $finalCounts = sqlite_counts($final);
            $final->exec('PRAGMA journal_mode = WAL');
            $final->exec('PRAGMA synchronous = NORMAL');
        } finally {
            $final->close();
        }

        clearstatcache(true, $settingsPath);
        if (hash_file('sha256', $settingsPath) !== $settingsHash) {
            throw new RuntimeException('settings.local.php changed during rebuild.');
        }

        $rebuildRuntimeCache();

        if (is_file($oldStagingPath) && !@unlink($oldStagingPath)) {
            fwrite(STDERR, "WARNING: old staging file remains: {$oldStagingPath}\n");
        }

        echo "Events demo database rebuild complete.\n";
        echo "  Database: {$databasePath}\n";
        echo "  Backup: {$backupPath}\n";
        echo "  Settings backup: {$settingsBackupPath}\n";
        echo "  settings.local.php SHA-256: {$settingsHash} (unchanged)\n";
        echo "  Campaign: " . EVENTS_DEMO_CAMPAIGN . "\n";
        echo "  Domain: {$domain}\n";
        echo "  Landings: " . EVENTS_DEMO_INTRO . ', ' . EVENTS_DEMO_OFFER . "\n";
        echo "  quick_check: {$finalQuickCheck}\n";
        foreach ($finalCounts as $table => $count) {
            echo "  {$table}: {$count}\n";
        }
        echo "  Runtime cache: rebuilt\n";
    } catch (Throwable $error) {
        if ($databaseSwapped) {
            try {
                rollback_events_demo_swap(
                    $databasePath,
                    $oldStagingPath,
                    $failedReplacementPath,
                    $createdLandingDirectories,
                    $rebuildRuntimeCache
                );
            } catch (Throwable $rollbackError) {
                throw new RuntimeException(
                    'Post-swap validation failed: ' . $error->getMessage()
                    . ' ' . $rollbackError->getMessage(),
                    0,
                    $error
                );
            }
            throw new RuntimeException(
                'Post-swap validation failed; old database and runtime cache were restored: '
                . $error->getMessage(),
                0,
                $error
            );
        }

        $cleanupErrors = [];
        try {
            remove_created_demo_landings($createdLandingDirectories);
        } catch (Throwable $cleanupError) {
            $cleanupErrors[] = $cleanupError->getMessage();
        }

        if ($error instanceof EventsDemoPartialSwapException) {
            $preserved = existing_database_paths([
                $databasePath,
                $oldStagingPath,
                $replacementPath,
            ]);
            throw new RuntimeException(
                $error->getMessage()
                . ' Preserved database paths: '
                . ($preserved === [] ? '(none found)' : implode(', ', $preserved))
                . ($cleanupErrors === [] ? '' : ' Cleanup errors: ' . implode(' ', $cleanupErrors)),
                0,
                $error
            );
        }

        try {
            remove_sqlite_artifacts($replacementPath);
        } catch (Throwable $cleanupError) {
            $cleanupErrors[] = $cleanupError->getMessage();
        }
        if ($cleanupErrors !== []) {
            throw new RuntimeException(
                $error->getMessage() . ' Cleanup errors: ' . implode(' ', $cleanupErrors),
                0,
                $error
            );
        }
        throw $error;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
} catch (Throwable $error) {
    fail($error->getMessage(), 1);
}
