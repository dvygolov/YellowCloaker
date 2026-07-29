<?php

/**
 * Isolated benchmark for click-step event writes and event statistics.
 *
 * The benchmark creates a one-off SQLite database under tests/load/, points the
 * production Db class at that file, and removes the DB (including WAL/SHM)
 * before exit. It never opens or changes the configured runtime database.
 *
 * Default scale:
 *   10,000 clicks x 3 reached steps = 30,000 click_steps/event samples
 *   2,000 ordinary writes + 2,000 performance packets via production Db
 *   3 Flow -> Step -> Landing statistics queries, including nearest-rank P75
 *   10,000 high-cardinality leaf groups with an independent 2,000 ms budget
 *
 * Usage:
 *   php tests/load/benchmark_events.php
 *   php tests/load/benchmark_events.php --clicks=500 --steps=2 --writes=200 --stats-runs=1
 */

declare(strict_types=1);

function benchmark_usage(): void
{
    echo "Usage: php tests/load/benchmark_events.php [options]\n";
    echo "  --clicks=N              Click rows to seed (default 10000)\n";
    echo "  --steps=N               Reached steps per click (default 3)\n";
    echo "  --writes=N              Targets written through Db methods (default 2000)\n";
    echo "  --stats-runs=N          Repeated statistics queries (default 3)\n";
    echo "  --high-cardinality-groups=N Leaf groups to exercise (default min(clicks, 10000))\n";
    echo "  --write-p95-budget-ms=N Portable P95 budget per write (default 25)\n";
    echo "  --stats-budget-ms=N     Max 30k-step statistics budget (default 2000)\n";
    echo "  --high-cardinality-budget-ms=N Max high-cardinality stats budget (default 2000)\n";
    echo "  --keep-db               Keep the temporary DB for query-plan inspection\n";
}

function benchmark_int_option(
    array $options,
    string $name,
    int $default,
    int $minimum,
    int $maximum
): int {
    $raw = $options[$name] ?? $default;
    if (is_array($raw) || filter_var($raw, FILTER_VALIDATE_INT) === false) {
        throw new InvalidArgumentException("Invalid --{$name}; expected an integer.");
    }
    $value = (int)$raw;
    if ($value < $minimum || $value > $maximum) {
        throw new InvalidArgumentException("--{$name} must be between {$minimum} and {$maximum}.");
    }
    return $value;
}

function benchmark_float_option(
    array $options,
    string $name,
    float $default,
    float $minimum,
    float $maximum
): float {
    $raw = $options[$name] ?? $default;
    if (is_array($raw) || !is_numeric($raw)) {
        throw new InvalidArgumentException("Invalid --{$name}; expected a number.");
    }
    $value = (float)$raw;
    if (!is_finite($value) || $value < $minimum || $value > $maximum) {
        throw new InvalidArgumentException("--{$name} must be between {$minimum} and {$maximum}.");
    }
    return $value;
}

/** @param list<float> $values */
function benchmark_percentile(array $values, float $percentile): float
{
    if ($values === []) {
        return 0.0;
    }
    sort($values, SORT_NUMERIC);
    $rank = max(1, (int)ceil(count($values) * $percentile));
    return (float)$values[$rank - 1];
}

/** @param list<float> $values */
function benchmark_average(array $values): float
{
    return $values === [] ? 0.0 : array_sum($values) / count($values);
}

function benchmark_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('Correctness check failed: ' . $message);
    }
}

function benchmark_assert_close(float $expected, mixed $actual, float $delta, string $label): void
{
    benchmark_assert(
        is_int($actual) || is_float($actual),
        "{$label} is not numeric"
    );
    benchmark_assert(
        abs($expected - (float)$actual) <= $delta,
        "{$label}: expected {$expected}, received " . var_export($actual, true)
    );
}

/** @return array{cta:int,performance:array{ttfb:int,fcp:int,lcp:int,inp:int,cls:float}} */
function benchmark_event_values(int $ordinal): array
{
    return [
        'cta' => 1 + ($ordinal % 1000),
        'performance' => [
            'ttfb' => 80 + ($ordinal % 600),
            'fcp' => 400 + ($ordinal % 1600),
            'lcp' => 800 + ($ordinal % 3200),
            'inp' => 40 + ($ordinal % 900),
            'cls' => round(($ordinal % 250) / 1000, 4),
        ],
    ];
}

/** @return array{clickid:string,step:int,variant:string,ordinal:int} */
function benchmark_target(int $targetIndex, int $stepsPerClick, string $prefix): array
{
    $clickIndex = intdiv($targetIndex, $stepsPerClick);
    $step = $targetIndex % $stepsPerClick;
    return [
        'clickid' => $prefix . $clickIndex,
        'step' => $step,
        'variant' => "benchmark-landing-{$step}-v" . ($clickIndex % 4),
        'ordinal' => $targetIndex,
    ];
}

function benchmark_remove_database(string $path): void
{
    foreach ([$path, $path . '-wal', $path . '-shm'] as $candidate) {
        if (is_file($candidate)) {
            @unlink($candidate);
        }
    }
}

$options = getopt('', [
    'clicks::',
    'steps::',
    'writes::',
    'stats-runs::',
    'high-cardinality-groups::',
    'write-p95-budget-ms::',
    'stats-budget-ms::',
    'high-cardinality-budget-ms::',
    'keep-db',
    'help',
]);
if (isset($options['help'])) {
    benchmark_usage();
    exit(0);
}

try {
    $clickCount = benchmark_int_option($options, 'clicks', 10000, 1, 250000);
    $stepsPerClick = benchmark_int_option($options, 'steps', 3, 1, 10);
    $targetCount = $clickCount * $stepsPerClick;
    $writeTargets = min(
        benchmark_int_option($options, 'writes', 2000, 1, 100000),
        $targetCount
    );
    $statsRuns = benchmark_int_option($options, 'stats-runs', 3, 1, 20);
    $highCardinalityGroups = min(
        benchmark_int_option($options, 'high-cardinality-groups', 10000, 1, 250000),
        $clickCount
    );
    $writeP95Budget = benchmark_float_option(
        $options,
        'write-p95-budget-ms',
        25.0,
        0.1,
        60000.0
    );
    $statsBudget = benchmark_float_option(
        $options,
        'stats-budget-ms',
        2000.0,
        1.0,
        600000.0
    );
    $highCardinalityBudget = benchmark_float_option(
        $options,
        'high-cardinality-budget-ms',
        2000.0,
        1.0,
        600000.0
    );
} catch (InvalidArgumentException $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    benchmark_usage();
    exit(2);
}

$keepDatabase = isset($options['keep-db']);
$databaseName = '.event-benchmark-' . getmypid() . '-' . bin2hex(random_bytes(4)) . '.sqlite';
$databasePath = __DIR__ . DIRECTORY_SEPARATOR . $databaseName;
$relativeDatabasePath = '../../tests/load/' . $databaseName;
$prefix = 'event-benchmark-' . bin2hex(random_bytes(4)) . '-';
$exitCode = 0;
$productionDb = null;

benchmark_remove_database($databasePath);

try {
    // Load SettingsManager first, then replace only this process's DB filename.
    // db.php's require_once will not reload the real local settings afterwards.
    require_once __DIR__ . '/../../code/settings.php';
    global $cloSettings;
    $cloSettings['dbConnection'] = $relativeDatabasePath;
    $cloSettings['debug'] = false;
    require_once __DIR__ . '/../../code/db/db.php';
    require_once __DIR__ . '/../../code/campaign.php';
    global $db;
    $productionDb = $db;

    benchmark_assert(is_file($databasePath), 'temporary database was not created');

    $campaignSettings = [
        'events' => [
            'scroll' => ['use' => false, 'thresholds' => []],
            'time' => ['use' => false, 'thresholds' => []],
            'performance' => ['use' => true],
            'custom' => ['cta_click'],
        ],
    ];

    $seedStarted = hrtime(true);
    $sqlite = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
    $sqlite->busyTimeout(5000);
    $sqlite->exec('PRAGMA foreign_keys = ON');
    $sqlite->exec('PRAGMA journal_mode = WAL');
    if (!$sqlite->exec('BEGIN IMMEDIATE')) {
        throw new RuntimeException('Failed to start seed transaction: ' . $sqlite->lastErrorMsg());
    }

    try {
        $campaignStatement = $sqlite->prepare(
            'INSERT INTO campaigns (id, name, settings) VALUES (1, :name, :settings)'
        );
        $campaignStatement->bindValue(':name', 'Events benchmark', SQLITE3_TEXT);
        $campaignStatement->bindValue(
            ':settings',
            json_encode($campaignSettings, JSON_THROW_ON_ERROR),
            SQLITE3_TEXT
        );
        benchmark_assert($campaignStatement->execute() !== false, 'campaign insert failed');

        $clickStatement = $sqlite->prepare(
            'INSERT INTO clicks '
            . '(campaign_id, time, ip, country, lang, os, device, client, ua, userid, clickid, flow, path, step, params, cost) '
            . 'VALUES (1, :time, :ip, :country, :lang, :os, :device, :client, :ua, '
            . ':userid, :clickid, :flow, :path, :step, :params, :cost)'
        );
        $stepStatement = $sqlite->prepare(
            'INSERT INTO click_steps (clickid, step, variant, time, events) '
            . 'VALUES (:clickid, :step, :variant, :time, :events)'
        );
        benchmark_assert(
            $clickStatement !== false && $stepStatement !== false,
            'seed statements could not be prepared'
        );

        $now = time();
        for ($clickIndex = 0; $clickIndex < $clickCount; $clickIndex++) {
            $clickid = $prefix . $clickIndex;
            $path = [];
            for ($step = 0; $step < $stepsPerClick; $step++) {
                $path[] = "benchmark-landing-{$step}-v" . ($clickIndex % 4);
            }

            $clickStatement->reset();
            $clickStatement->bindValue(':time', $now - ($clickIndex % 86400), SQLITE3_INTEGER);
            $clickStatement->bindValue(':ip', '203.0.113.' . (($clickIndex % 250) + 1), SQLITE3_TEXT);
            $clickStatement->bindValue(':country', ($clickIndex % 2) === 0 ? 'US' : 'DE', SQLITE3_TEXT);
            $clickStatement->bindValue(':lang', 'en', SQLITE3_TEXT);
            $clickStatement->bindValue(':os', ($clickIndex % 3) === 0 ? 'Android' : 'Windows', SQLITE3_TEXT);
            $clickStatement->bindValue(':device', ($clickIndex % 3) === 0 ? 'mobile' : 'desktop', SQLITE3_TEXT);
            $clickStatement->bindValue(':client', 'Chrome', SQLITE3_TEXT);
            $clickStatement->bindValue(':ua', 'YellowTDS Events benchmark', SQLITE3_TEXT);
            $clickStatement->bindValue(':userid', $prefix . 'user-' . $clickIndex, SQLITE3_TEXT);
            $clickStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $clickStatement->bindValue(':flow', ($clickIndex % 2) === 0 ? 'Flow A' : 'Flow B', SQLITE3_TEXT);
            $clickStatement->bindValue(
                ':path',
                json_encode($path, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                SQLITE3_TEXT
            );
            $clickStatement->bindValue(':step', $stepsPerClick - 1, SQLITE3_INTEGER);
            $highCardinalityGroup = $clickIndex % $highCardinalityGroups;
            $clickStatement->bindValue(
                ':params',
                json_encode(
                    [
                        'benchmark_parent' => "parent-{$highCardinalityGroup}",
                        'benchmark_leaf' => "leaf-{$highCardinalityGroup}",
                    ],
                    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                ),
                SQLITE3_TEXT
            );
            $clickStatement->bindValue(':cost', ($clickIndex % 50) / 100, SQLITE3_FLOAT);
            benchmark_assert($clickStatement->execute() !== false, "click {$clickIndex} insert failed");

            for ($step = 0; $step < $stepsPerClick; $step++) {
                $targetIndex = ($clickIndex * $stepsPerClick) + $step;
                $values = benchmark_event_values($targetIndex);
                $events = $targetIndex < $writeTargets
                    ? '{}'
                    : json_encode([
                        'cta_click' => $values['cta'],
                        'performance' => $values['performance'],
                    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $stepStatement->reset();
                $stepStatement->bindValue(':clickid', $clickid, SQLITE3_TEXT);
                $stepStatement->bindValue(':step', $step, SQLITE3_INTEGER);
                $stepStatement->bindValue(':variant', $path[$step], SQLITE3_TEXT);
                $stepStatement->bindValue(':time', $now - ($clickIndex % 86400), SQLITE3_INTEGER);
                $stepStatement->bindValue(
                    ':events',
                    $events,
                    SQLITE3_TEXT
                );
                benchmark_assert(
                    $stepStatement->execute() !== false,
                    "click-step {$targetIndex} insert failed"
                );
            }
        }
        benchmark_assert($sqlite->exec('COMMIT'), 'seed transaction commit failed');
    } catch (Throwable $error) {
        $sqlite->exec('ROLLBACK');
        throw $error;
    } finally {
        $sqlite->close();
    }
    $seedMilliseconds = (hrtime(true) - $seedStarted) / 1_000_000;

    $ordinaryDurations = [];
    $performanceDurations = [];
    for ($targetIndex = 0; $targetIndex < $writeTargets; $targetIndex++) {
        $target = benchmark_target($targetIndex, $stepsPerClick, $prefix);
        $values = benchmark_event_values($target['ordinal']);

        $started = hrtime(true);
        $ordinaryResult = $productionDb->save_step_event(
            $target['clickid'],
            $target['step'],
            $target['variant'],
            'cta_click',
            $values['cta']
        );
        $ordinaryDurations[] = (hrtime(true) - $started) / 1_000_000;
        benchmark_assert(
            $ordinaryResult === Db::STEP_EVENT_CREATED,
            "ordinary write {$targetIndex} returned {$ordinaryResult}"
        );

        $started = hrtime(true);
        $performanceResult = $productionDb->save_step_performance(
            $target['clickid'],
            $target['step'],
            $target['variant'],
            $values['performance']
        );
        $performanceDurations[] = (hrtime(true) - $started) / 1_000_000;
        benchmark_assert(
            $performanceResult === Db::STEP_EVENT_CREATED,
            "performance write {$targetIndex} returned {$performanceResult}"
        );
    }

    $firstTarget = benchmark_target(0, $stepsPerClick, $prefix);
    $firstValues = benchmark_event_values(0);
    benchmark_assert(
        $productionDb->save_step_event(
            $firstTarget['clickid'],
            $firstTarget['step'],
            $firstTarget['variant'],
            'cta_click',
            999999
        ) === Db::STEP_EVENT_SAVED,
        'duplicate ordinary retry was not accepted'
    );
    benchmark_assert(
        $productionDb->save_step_performance(
            $firstTarget['clickid'],
            $firstTarget['step'],
            $firstTarget['variant'],
            ['ttfb' => 9999, 'lcp' => 9999]
        ) === Db::STEP_EVENT_SAVED,
        'duplicate performance retry was not accepted'
    );
    benchmark_assert(
        $productionDb->save_step_event(
            $firstTarget['clickid'],
            $firstTarget['step'],
            $firstTarget['variant'],
            'unknown_event',
            1234
        ) === Db::STEP_EVENT_NOT_ALLOWED,
        'unknown event was not rejected'
    );

    $verificationDb = new SQLite3($databasePath, SQLITE3_OPEN_READONLY);
    $ordinaryCount = (int)$verificationDb->querySingle(
        "SELECT COUNT(*) FROM click_steps WHERE json_type(events, '$.cta_click') IN ('integer', 'real')"
    );
    $performanceCount = (int)$verificationDb->querySingle(
        "SELECT COUNT(*) FROM click_steps WHERE json_type(events, '$.performance') = 'object'"
    );
    $identityStatement = $verificationDb->prepare(
        "SELECT json_extract(events, '$.cta_click') AS cta, "
        . "json_extract(events, '$.performance.ttfb') AS ttfb, "
        . "json_type(events, '$.unknown_event') AS unknown_type "
        . 'FROM click_steps WHERE clickid = :clickid AND step = :step'
    );
    $identityStatement->bindValue(':clickid', $firstTarget['clickid'], SQLITE3_TEXT);
    $identityStatement->bindValue(':step', $firstTarget['step'], SQLITE3_INTEGER);
    $identityRow = $identityStatement->execute()->fetchArray(SQLITE3_ASSOC);
    $verificationDb->close();
    benchmark_assert($ordinaryCount === $targetCount, 'not every step has cta_click');
    benchmark_assert($performanceCount === $targetCount, 'not every step has a performance packet');
    benchmark_assert(is_array($identityRow), 'first-write verification row is missing');
    benchmark_assert(
        (int)($identityRow['cta'] ?? -1) === $firstValues['cta'],
        'duplicate retry changed the first ordinary value'
    );
    benchmark_assert(
        (int)($identityRow['ttfb'] ?? -1) === $firstValues['performance']['ttfb'],
        'duplicate retry changed the first performance packet'
    );
    benchmark_assert(
        ($identityRow['unknown_type'] ?? null) === null,
        'unknown event reached click_steps.events'
    );

    $expectedCta = [];
    $expectedLcp = [];
    for ($targetIndex = 0; $targetIndex < $targetCount; $targetIndex++) {
        $values = benchmark_event_values($targetIndex);
        $expectedCta[] = (float)$values['cta'];
        $expectedLcp[] = (float)$values['performance']['lcp'];
    }
    $expectedCtaP75 = benchmark_percentile($expectedCta, 0.75);
    $expectedLcpP75 = benchmark_percentile($expectedLcp, 0.75);
    $expectedCtaAverage = benchmark_average($expectedCta);
    $expectedLcpAverage = benchmark_average($expectedLcp);

    $columns = [
        'clicks',
        'event.cta_click.count',
        'event.cta_click.avg',
        'event.cta_click.p75',
        'event.cta_click.min',
        'event.cta_click.max',
        'performance.lcp.count',
        'performance.lcp.avg',
        'performance.lcp.p75',
        'performance.lcp.min',
        'performance.lcp.max',
    ];
    $statisticsDurations = [];
    $statisticsTree = [];
    for ($run = 0; $run < $statsRuns; $run++) {
        $started = hrtime(true);
        $statisticsTree = $productionDb->get_statistics(
            $columns,
            ['flow', 'step', 'landing'],
            1,
            '0',
            '9999999999',
            'UTC'
        );
        $statisticsDurations[] = (hrtime(true) - $started) / 1_000_000;
        benchmark_assert($statisticsTree !== [], 'statistics tree is empty');
    }

    $totals = $statisticsTree[0]['_stats_totals'] ?? null;
    benchmark_assert(is_array($totals), 'statistics totals are missing');
    benchmark_assert((int)($totals['clicks'] ?? -1) === $clickCount, 'grand total click count differs');
    benchmark_assert(
        (int)($totals['event.cta_click.count'] ?? -1) === $targetCount,
        'grand total ordinary event count differs'
    );
    benchmark_assert(
        (int)($totals['performance.lcp.count'] ?? -1) === $targetCount,
        'grand total performance event count differs'
    );
    benchmark_assert_close(
        $expectedCtaAverage,
        $totals['event.cta_click.avg'] ?? null,
        0.0001,
        'cta_click average'
    );
    benchmark_assert_close(
        $expectedCtaP75,
        $totals['event.cta_click.p75'] ?? null,
        0.0001,
        'cta_click P75'
    );
    benchmark_assert_close(
        $expectedLcpAverage,
        $totals['performance.lcp.avg'] ?? null,
        0.0001,
        'LCP average'
    );
    benchmark_assert_close(
        $expectedLcpP75,
        $totals['performance.lcp.p75'] ?? null,
        0.0001,
        'LCP P75'
    );
    benchmark_assert(count($statisticsTree) === 2, 'expected two top-level flow groups');
    foreach ($statisticsTree as $flowRow) {
        benchmark_assert(
            is_array($flowRow['_children'] ?? null)
            && count($flowRow['_children']) === $stepsPerClick,
            'each flow must contain every reached step'
        );
    }

    $highCardinalityStarted = hrtime(true);
    $highCardinalityTree = $productionDb->get_statistics(
        ['clicks'],
        ['param.benchmark_parent', 'param.benchmark_leaf'],
        1,
        '0',
        '9999999999',
        'UTC'
    );
    $highCardinalityMilliseconds = (hrtime(true) - $highCardinalityStarted) / 1_000_000;
    benchmark_assert(
        count($highCardinalityTree) === $highCardinalityGroups,
        'high-cardinality parent group count differs'
    );
    $highCardinalityLeafCount = 0;
    $highCardinalityTotals = null;
    foreach ($highCardinalityTree as $parentRow) {
        $children = $parentRow['_children'] ?? null;
        benchmark_assert(
            is_array($children) && count($children) === 1,
            'each high-cardinality parent must contain exactly one leaf'
        );
        $highCardinalityLeafCount += count($children);
        $highCardinalityTotals ??= $parentRow['_stats_totals'] ?? null;
    }
    benchmark_assert(
        $highCardinalityLeafCount === $highCardinalityGroups,
        'high-cardinality leaf group count differs'
    );
    benchmark_assert(
        is_array($highCardinalityTotals)
        && (int)($highCardinalityTotals['clicks'] ?? -1) === $clickCount,
        'high-cardinality grand total click count differs'
    );

    $ordinaryP95 = benchmark_percentile($ordinaryDurations, 0.95);
    $performanceP95 = benchmark_percentile($performanceDurations, 0.95);
    $statisticsMaximum = max($statisticsDurations);

    printf("YellowTDS Events benchmark (%s)\n", PHP_VERSION);
    printf("  DB: temporary %s\n", $databasePath);
    printf(
        "  Dataset: %s clicks, %s click_steps, %s production-method targets\n",
        number_format($clickCount),
        number_format($targetCount),
        number_format($writeTargets)
    );
    printf("  Seed: %.2f ms\n", $seedMilliseconds);
    printf(
        "  Ordinary writes: avg %.3f ms, P95 %.3f ms, %.1f writes/s\n",
        benchmark_average($ordinaryDurations),
        $ordinaryP95,
        1000 / max(0.000001, benchmark_average($ordinaryDurations))
    );
    printf(
        "  Performance writes: avg %.3f ms, P95 %.3f ms, %.1f packets/s\n",
        benchmark_average($performanceDurations),
        $performanceP95,
        1000 / max(0.000001, benchmark_average($performanceDurations))
    );
    printf(
        "  Flow -> Step -> Landing stats: %d runs, avg %.2f ms, max %.2f ms\n",
        count($statisticsDurations),
        benchmark_average($statisticsDurations),
        $statisticsMaximum
    );
    printf(
        "  High-cardinality stats: %s leaf groups, %.2f ms\n",
        number_format($highCardinalityLeafCount),
        $highCardinalityMilliseconds
    );
    printf(
        "  Correctness: %s event samples, CTA P75 %.0f, LCP P75 %.0f [PASS]\n",
        number_format($targetCount),
        $expectedCtaP75,
        $expectedLcpP75
    );

    $budgetFailures = [];
    if ($ordinaryP95 > $writeP95Budget) {
        $budgetFailures[] = sprintf(
            'ordinary write P95 %.3f ms exceeded %.3f ms',
            $ordinaryP95,
            $writeP95Budget
        );
    }
    if ($performanceP95 > $writeP95Budget) {
        $budgetFailures[] = sprintf(
            'performance write P95 %.3f ms exceeded %.3f ms',
            $performanceP95,
            $writeP95Budget
        );
    }
    if ($statisticsMaximum > $statsBudget) {
        $budgetFailures[] = sprintf(
            'statistics max %.2f ms exceeded %.2f ms',
            $statisticsMaximum,
            $statsBudget
        );
    }
    if ($highCardinalityMilliseconds > $highCardinalityBudget) {
        $budgetFailures[] = sprintf(
            'high-cardinality statistics %.2f ms exceeded %.2f ms',
            $highCardinalityMilliseconds,
            $highCardinalityBudget
        );
    }
    if ($budgetFailures !== []) {
        foreach ($budgetFailures as $failure) {
            fwrite(STDERR, "  Budget: {$failure} [FAIL]\n");
        }
        $exitCode = 1;
    } else {
        printf(
            "  Budgets: write P95 <= %.0f ms, stats max <= %.0f ms, high-cardinality <= %.0f ms [PASS]\n",
            $writeP95Budget,
            $statsBudget,
            $highCardinalityBudget
        );
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    $exitCode = 1;
} finally {
    // Drop production Db's cached SQLite handles before removing files on Windows.
    $GLOBALS['db'] = null;
    $productionDb = null;
    if (isset($db)) {
        $db = null;
    }
    gc_collect_cycles();

    if ($keepDatabase) {
        echo "  Kept temporary DB: {$databasePath}\n";
    } else {
        benchmark_remove_database($databasePath);
    }
}

exit($exitCode);
