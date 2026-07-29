<?php

/**
 * Delete exactly the Events API load-test campaign recorded in the manifest.
 *
 * Usage:
 *   php tests/load/teardown_events.php
 */

declare(strict_types=1);

const EVENTS_FIXTURE_CAMPAIGN = '__YellowTDS Events Load Fixture__';
const EVENTS_FIXTURE_MANIFEST = __DIR__ . '/.events-fixture.json';

if (!is_file(EVENTS_FIXTURE_MANIFEST)) {
    echo "No Events fixture manifest found; nothing was deleted.\n";
    exit(0);
}

try {
    $manifest = json_decode(
        (string)file_get_contents(EVENTS_FIXTURE_MANIFEST),
        true,
        16,
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $error) {
    fwrite(STDERR, "Invalid Events fixture manifest; refusing to delete anything.\n");
    exit(2);
}

$campaignId = filter_var($manifest['campaign_id'] ?? null, FILTER_VALIDATE_INT);
$campaignName = $manifest['campaign_name'] ?? null;
$manifestDatabase = $manifest['database_path'] ?? null;
$fixtureVersion = filter_var($manifest['version'] ?? null, FILTER_VALIDATE_INT);
$fixturePrefix = $manifest['prefix'] ?? null;
$stepsPerClick = filter_var($manifest['steps_per_click'] ?? null, FILTER_VALIDATE_INT);
$retryTargetStart = filter_var($manifest['retry_target_start'] ?? null, FILTER_VALIDATE_INT);
$retryTargetCount = filter_var($manifest['retry_target_count'] ?? null, FILTER_VALIDATE_INT);
if (
    !is_int($campaignId)
    || $campaignId < 1
    || $campaignName !== EVENTS_FIXTURE_CAMPAIGN
    || !is_string($manifestDatabase)
    || $manifestDatabase === ''
    || $fixtureVersion !== 2
    || !is_string($fixturePrefix)
    || $fixturePrefix === ''
    || !is_int($stepsPerClick)
    || $stepsPerClick < 1
    || !is_int($retryTargetStart)
    || $retryTargetStart < 1
    || !is_int($retryTargetCount)
    || $retryTargetCount < 1
) {
    fwrite(STDERR, "Manifest identity is incomplete; refusing to delete anything.\n");
    exit(2);
}

require_once __DIR__ . '/../../code/settings.php';
global $cloSettings;

$connection = (string)($cloSettings['dbConnection'] ?? '');
if (
    $connection === ''
    || basename($connection) !== $connection
    || preg_match('/^[A-Za-z0-9._-]+$/', $connection) !== 1
) {
    fwrite(STDERR, "Configured SQLite filename is unsafe; refusing to delete anything.\n");
    exit(2);
}
$databasePath = __DIR__ . '/../../code/db/' . $connection;
$resolvedDatabase = realpath($databasePath);
$resolvedManifestDatabase = realpath($manifestDatabase);
if (
    $resolvedDatabase === false
    || $resolvedManifestDatabase === false
    || strcasecmp($resolvedDatabase, $resolvedManifestDatabase) !== 0
) {
    fwrite(STDERR, "Runtime database differs from the fixture manifest; refusing to delete anything.\n");
    exit(2);
}

$sqlite = new SQLite3($resolvedDatabase, SQLITE3_OPEN_READWRITE);
$sqlite->busyTimeout(5000);
$sqlite->exec('PRAGMA foreign_keys = ON');
$lookup = $sqlite->prepare('SELECT name FROM campaigns WHERE id = :id');
$lookup->bindValue(':id', $campaignId, SQLITE3_INTEGER);
$row = $lookup->execute()->fetchArray(SQLITE3_ASSOC);
if (!is_array($row)) {
    $sqlite->close();
    @unlink(EVENTS_FIXTURE_MANIFEST);
    echo "Fixture campaign no longer exists; removed the stale manifest.\n";
    exit(0);
}
if (($row['name'] ?? '') !== EVENTS_FIXTURE_CAMPAIGN) {
    $sqlite->close();
    fwrite(STDERR, "Campaign ID {$campaignId} has another name; refusing to delete it.\n");
    exit(2);
}

$retryCheck = $sqlite->prepare(
    "SELECT json_extract(events, '$.cta_click') AS cta, "
    . "json_extract(events, '$.performance.ttfb') AS ttfb, "
    . "json_extract(events, '$.performance.lcp') AS lcp, "
    . "json_type(events, '$.unknown_event') AS unknown_type "
    . 'FROM click_steps WHERE clickid = :clickid AND step = :step'
);
$integrityFailures = 0;
if ($retryCheck === false) {
    $integrityFailures = $retryTargetCount;
} else {
    for ($offset = 0; $offset < $retryTargetCount; $offset++) {
        $targetIndex = $retryTargetStart + $offset;
        $clickIndex = intdiv($targetIndex, $stepsPerClick);
        $step = $targetIndex % $stepsPerClick;
        $retryCheck->reset();
        $retryCheck->bindValue(':clickid', $fixturePrefix . $clickIndex, SQLITE3_TEXT);
        $retryCheck->bindValue(':step', $step, SQLITE3_INTEGER);
        $retryResult = $retryCheck->execute();
        $retryRow = $retryResult === false ? false : $retryResult->fetchArray(SQLITE3_ASSOC);
        if (
            !is_array($retryRow)
            || (int)($retryRow['cta'] ?? -1) !== 4242
            || (int)($retryRow['ttfb'] ?? -1) !== 120
            || (int)($retryRow['lcp'] ?? -1) !== 1400
            || ($retryRow['unknown_type'] ?? null) !== null
        ) {
            $integrityFailures++;
        }
    }
}

$delete = $sqlite->prepare('DELETE FROM campaigns WHERE id = :id AND name = :name');
$delete->bindValue(':id', $campaignId, SQLITE3_INTEGER);
$delete->bindValue(':name', EVENTS_FIXTURE_CAMPAIGN, SQLITE3_TEXT);
if ($delete->execute() === false || $sqlite->changes() !== 1) {
    $sqlite->close();
    fwrite(STDERR, "Failed to delete the scoped Events fixture campaign.\n");
    exit(1);
}
$sqlite->close();

if (!unlink(EVENTS_FIXTURE_MANIFEST)) {
    fwrite(STDERR, "Campaign deleted, but the fixture manifest could not be removed.\n");
    exit(1);
}

echo "Deleted Events fixture campaign {$campaignId} and its cascaded clicks/click_steps.\n";
if ($integrityFailures > 0) {
    fwrite(
        STDERR,
        "First-write integrity failed on {$integrityFailures}/{$retryTargetCount} reserved retry targets.\n"
    );
    exit(1);
}
echo "Verified first-write-wins and unknown-event rejection on {$retryTargetCount} retry targets.\n";
