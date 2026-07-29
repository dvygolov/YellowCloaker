<?php

/**
 * Create an explicitly scoped Events API load-test fixture in the runtime DB.
 *
 * This script intentionally does not run as part of the normal load-test setup.
 * It writes only a uniquely named campaign plus its clicks/click_steps and
 * produces tests/load/.events-fixture.json for the k6 Events scenario.
 *
 * Usage:
 *   php tests/load/setup_events.php --clicks=5000 --steps=2
 *   php tests/load/teardown_events.php
 */

declare(strict_types=1);

const EVENTS_FIXTURE_CAMPAIGN = '__YellowTDS Events Load Fixture__';
const EVENTS_FIXTURE_MANIFEST = __DIR__ . '/.events-fixture.json';

function events_fixture_option_int(array $options, string $name, int $default, int $minimum, int $maximum): int
{
    $raw = $options[$name] ?? $default;
    if (is_array($raw) || filter_var($raw, FILTER_VALIDATE_INT) === false) {
        fwrite(STDERR, "Invalid --{$name}; expected an integer.\n");
        exit(2);
    }
    $value = (int)$raw;
    if ($value < $minimum || $value > $maximum) {
        fwrite(STDERR, "--{$name} must be between {$minimum} and {$maximum}.\n");
        exit(2);
    }
    return $value;
}

function events_fixture_database_path(array $settings): string
{
    $connection = (string)($settings['dbConnection'] ?? '');
    if (
        $connection === ''
        || basename($connection) !== $connection
        || preg_match('/^[A-Za-z0-9._-]+$/', $connection) !== 1
    ) {
        throw new RuntimeException('The configured SQLite filename is not safe for the fixture.');
    }
    return __DIR__ . '/../../code/db/' . $connection;
}

function events_fixture_write_manifest(array $manifest): void
{
    $json = json_encode(
        $manifest,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
    $temporary = EVENTS_FIXTURE_MANIFEST . '.tmp-' . getmypid();
    if (file_put_contents($temporary, $json, LOCK_EX) === false) {
        throw new RuntimeException('Failed to write the Events fixture manifest.');
    }
    if (!rename($temporary, EVENTS_FIXTURE_MANIFEST)) {
        @unlink($temporary);
        throw new RuntimeException('Failed to publish the Events fixture manifest.');
    }
}

$options = getopt('', ['clicks::', 'steps::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php tests/load/setup_events.php [--clicks=5000] [--steps=2]\n";
    echo "Creates a dedicated runtime-DB fixture. Run teardown_events.php afterwards.\n";
    exit(0);
}

$clickCount = events_fixture_option_int($options, 'clicks', 5000, 2, 100000);
$stepsPerClick = events_fixture_option_int($options, 'steps', 2, 1, 10);
$targetCount = $clickCount * $stepsPerClick;
$retryTargetCount = min(1000, max(1, intdiv($targetCount, 20)), $targetCount - 1);
$uniqueTargetCount = $targetCount - $retryTargetCount;

if (is_file(EVENTS_FIXTURE_MANIFEST)) {
    fwrite(
        STDERR,
        "Events fixture manifest already exists. Run php tests/load/teardown_events.php first.\n"
    );
    exit(2);
}

require_once __DIR__ . '/../../code/db/db.php';

global $db, $cloSettings;

$databasePath = events_fixture_database_path($cloSettings);
if (!is_file($databasePath)) {
    fwrite(STDERR, "Configured runtime database does not exist: {$databasePath}\n");
    exit(2);
}

$existing = array_filter(
    $db->get_campaigns_list(),
    static fn(array $campaign): bool => ($campaign['name'] ?? '') === EVENTS_FIXTURE_CAMPAIGN
);
if ($existing !== []) {
    fwrite(
        STDERR,
        "The scoped Events load-test campaign already exists. Refusing to replace it automatically.\n"
    );
    exit(2);
}

$campaignId = $db->add_campaign(EVENTS_FIXTURE_CAMPAIGN, false);
if (!is_int($campaignId) && !ctype_digit((string)$campaignId)) {
    fwrite(STDERR, "Failed to create the Events load-test campaign.\n");
    exit(1);
}
$campaignId = (int)$campaignId;

$settings = $db->get_campaign_settings($campaignId);
$settings['events'] = [
    'scroll' => ['use' => false, 'thresholds' => []],
    'time' => ['use' => false, 'thresholds' => []],
    'performance' => ['use' => true],
    'custom' => ['cta_click'],
];
$settings['statistics']['timezone'] = 'UTC';
$settings['statistics']['tables'] = [[
    'name' => 'Events API load',
    'columns' => [
        ['field' => 'flow', 'width' => -1],
        ['field' => 'step', 'width' => -1],
        ['field' => 'landing', 'width' => -1],
        ['field' => 'clicks', 'width' => -1],
        ['field' => 'event.cta_click.count', 'width' => -1],
        ['field' => 'event.cta_click.avg', 'width' => -1],
        ['field' => 'event.cta_click.p75', 'width' => -1],
        ['field' => 'performance.lcp.count', 'width' => -1],
        ['field' => 'performance.lcp.avg', 'width' => -1],
        ['field' => 'performance.lcp.p75', 'width' => -1],
    ],
    'groupby' => ['flow', 'step', 'landing'],
    'filters' => [],
    'orderby' => [],
]];
if (!$db->save_campaign_settings($campaignId, $settings, false, false)) {
    $db->delete_campaign($campaignId, false);
    fwrite(STDERR, "Failed to configure Events on campaign {$campaignId}.\n");
    exit(1);
}

$prefix = 'events-load-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(4)) . '-';
$sqlite = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
$sqlite->busyTimeout(5000);
$sqlite->exec('PRAGMA foreign_keys = ON');
$sqlite->exec('PRAGMA journal_mode = WAL');

try {
    if (!$sqlite->exec('BEGIN IMMEDIATE')) {
        throw new RuntimeException('Failed to start fixture transaction: ' . $sqlite->lastErrorMsg());
    }
    $clickStatement = $sqlite->prepare(
        'INSERT INTO clicks '
        . '(campaign_id, time, ip, country, lang, os, device, client, ua, userid, clickid, flow, path, step, params) '
        . 'VALUES (:campaign, :time, :ip, :country, :lang, :os, :device, :client, :ua, '
        . ':userid, :clickid, :flow, :path, :step, :params)'
    );
    $stepStatement = $sqlite->prepare(
        'INSERT INTO click_steps (clickid, step, variant, time, events) '
        . 'VALUES (:clickid, :step, :variant, :time, :events)'
    );
    if ($clickStatement === false || $stepStatement === false) {
        throw new RuntimeException('Failed to prepare fixture inserts: ' . $sqlite->lastErrorMsg());
    }

    $now = time();
    for ($clickIndex = 0; $clickIndex < $clickCount; $clickIndex++) {
        $clickid = $prefix . $clickIndex;
        $path = [];
        for ($step = 0; $step < $stepsPerClick; $step++) {
            $path[] = "events-landing-{$step}-v" . ($clickIndex % 4);
        }

        $clickStatement->reset();
        $clickStatement->bindValue(':campaign', $campaignId, SQLITE3_INTEGER);
        $clickStatement->bindValue(':time', $now - ($clickIndex % 3600), SQLITE3_INTEGER);
        $clickStatement->bindValue(':ip', '198.51.100.' . (($clickIndex % 250) + 1), SQLITE3_TEXT);
        $clickStatement->bindValue(':country', ($clickIndex % 2) === 0 ? 'US' : 'DE', SQLITE3_TEXT);
        $clickStatement->bindValue(':lang', 'en', SQLITE3_TEXT);
        $clickStatement->bindValue(':os', 'Linux', SQLITE3_TEXT);
        $clickStatement->bindValue(':device', 'desktop', SQLITE3_TEXT);
        $clickStatement->bindValue(':client', 'Chrome', SQLITE3_TEXT);
        $clickStatement->bindValue(':ua', 'YellowTDS Events load fixture', SQLITE3_TEXT);
        $clickStatement->bindValue(':userid', $prefix . 'user-' . $clickIndex, SQLITE3_TEXT);
        $clickStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
        $clickStatement->bindValue(':flow', ($clickIndex % 2) === 0 ? 'Events Flow A' : 'Events Flow B', SQLITE3_TEXT);
        $clickStatement->bindValue(
            ':path',
            json_encode($path, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            SQLITE3_TEXT
        );
        $clickStatement->bindValue(':step', $stepsPerClick - 1, SQLITE3_INTEGER);
        $clickStatement->bindValue(':params', '{}', SQLITE3_TEXT);
        if ($clickStatement->execute() === false) {
            throw new RuntimeException('Failed to insert fixture click: ' . $sqlite->lastErrorMsg());
        }

        foreach ($path as $step => $variant) {
            $targetIndex = ($clickIndex * $stepsPerClick) + $step;
            $events = $targetIndex >= $uniqueTargetCount
                ? json_encode([
                    'cta_click' => 4242,
                    'performance' => [
                        'ttfb' => 120,
                        'fcp' => 700,
                        'lcp' => 1400,
                        'inp' => 90,
                        'cls' => 0.05,
                    ],
                ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                : '{}';
            $stepStatement->reset();
            $stepStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $stepStatement->bindValue(':step', $step, SQLITE3_INTEGER);
            $stepStatement->bindValue(':variant', $variant, SQLITE3_TEXT);
            $stepStatement->bindValue(':time', $now - ($clickIndex % 3600), SQLITE3_INTEGER);
            $stepStatement->bindValue(':events', $events, SQLITE3_TEXT);
            if ($stepStatement->execute() === false) {
                throw new RuntimeException('Failed to insert fixture click step: ' . $sqlite->lastErrorMsg());
            }
        }
    }
    if (!$sqlite->exec('COMMIT')) {
        throw new RuntimeException('Failed to commit fixture: ' . $sqlite->lastErrorMsg());
    }
} catch (Throwable $error) {
    $sqlite->exec('ROLLBACK');
    $sqlite->exec('DELETE FROM campaigns WHERE id = ' . $campaignId);
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
} finally {
    $sqlite->close();
}

try {
    events_fixture_write_manifest([
        'version' => 2,
        'campaign_id' => $campaignId,
        'campaign_name' => EVENTS_FIXTURE_CAMPAIGN,
        'database_path' => realpath($databasePath) ?: $databasePath,
        'prefix' => $prefix,
        'click_count' => $clickCount,
        'steps_per_click' => $stepsPerClick,
        'target_count' => $targetCount,
        'unique_target_count' => $uniqueTargetCount,
        'retry_target_start' => $uniqueTargetCount,
        'retry_target_count' => $retryTargetCount,
        'created_at' => gmdate(DATE_ATOM),
    ]);
} catch (Throwable $error) {
    $cleanup = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
    $cleanup->exec('PRAGMA foreign_keys = ON');
    $cleanup->exec('DELETE FROM campaigns WHERE id = ' . $campaignId);
    $cleanup->close();
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}

echo "Events fixture ready.\n";
echo "  Campaign ID: {$campaignId}\n";
echo "  Clicks: {$clickCount}\n";
echo "  Click steps: {$targetCount}\n";
echo "  Empty unique-write targets: {$uniqueTargetCount}\n";
echo "  Pre-seeded duplicate-retry targets: {$retryTargetCount}\n";
echo "  Manifest: " . EVENTS_FIXTURE_MANIFEST . "\n";
echo "Run: k6 run tests/load/k6/scenarios/events.js\n";
echo "Cleanup: php tests/load/teardown_events.php\n";
