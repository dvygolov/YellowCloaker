<?php

require_once __DIR__ . '/logging.php';

final class DatabaseMaintenanceBusyException extends RuntimeException
{
}

final class DatabaseMaintenance
{
    public const BATCH_SIZE = 1000;
    public const RECENT_WINDOW_SECONDS = 300;
    public const JOB_TTL_SECONDS = 86400;

    private string $root;
    private string $databasePath;
    private Closure $diskFree;
    private Closure $clock;
    private Closure $logger;

    /** @param array<string, mixed> $settings */
    public function __construct(
        string $root = __DIR__,
        private readonly array $settings = [],
        ?callable $diskFree = null,
        ?callable $clock = null,
        ?callable $logger = null,
    ) {
        $resolved = realpath($root);
        if ($resolved === false || !is_dir($resolved)) {
            throw new RuntimeException('Invalid YellowTDS root directory');
        }
        $this->root = rtrim($resolved, '/\\');
        $databaseName = (string)($settings['dbConnection'] ?? 'clicks.db');
        if ($databaseName === '' || basename($databaseName) !== $databaseName) {
            throw new RuntimeException('Invalid database file name');
        }
        $this->databasePath = $this->rootPath('db/' . $databaseName);
        if (!is_file($this->databasePath)) {
            throw new RuntimeException('SQLite database does not exist');
        }

        $this->diskFree = Closure::fromCallable($diskFree ?? static fn(string $path): int|float|false => disk_free_space($path));
        $this->clock = Closure::fromCallable($clock ?? static fn(): int => time());
        if ($logger !== null) {
            $this->logger = Closure::fromCallable($logger);
        } else {
            $yellowTdsLogger = new YellowTdsLogger($this->root, $settings);
            $this->logger = static fn(string $level, string $source, string $message, array $context = []): bool =>
                $yellowTdsLogger->log($level, $source, $message, $context);
        }
    }

    /** @return array{0: int, 1: int} */
    public static function parseDateRange(string $start, string $end, string $timezone): array
    {
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException('Invalid statistics timezone');
        }
        $zone = new DateTimeZone($timezone);
        $startDate = DateTimeImmutable::createFromFormat('!d.m.y', $start, $zone);
        $endDate = DateTimeImmutable::createFromFormat('!d.m.y', $end, $zone);
        if (
            $startDate === false || $endDate === false
            || $startDate->format('d.m.y') !== $start
            || $endDate->format('d.m.y') !== $end
        ) {
            throw new InvalidArgumentException('Dates must use DD.MM.YY format');
        }
        if ($startDate > $endDate) {
            throw new InvalidArgumentException('Start date must not be after end date');
        }
        return [
            $startDate->setTime(0, 0, 0)->getTimestamp(),
            $endDate->setTime(23, 59, 59)->getTimestamp(),
        ];
    }

    /** @return array<string, mixed> */
    public function summary(int $start, int $end): array
    {
        $this->assertTimestampRange($start, $end);
        $db = $this->openDatabase(true);
        try {
            $statement = $db->prepare(
                'SELECT c.id, c.name,
                    (SELECT COUNT(*) FROM clicks k WHERE k.campaign_id = c.id) AS clicks_total,
                    (SELECT COUNT(*) FROM clicks k WHERE k.campaign_id = c.id AND k.time BETWEEN :start AND :end) AS clicks_range,
                    (SELECT MIN(time) FROM clicks k WHERE k.campaign_id = c.id) AS clicks_oldest,
                    (SELECT MAX(time) FROM clicks k WHERE k.campaign_id = c.id) AS clicks_newest,
                    (SELECT COUNT(*) FROM blocked b WHERE b.campaign_id = c.id) AS blocked_total,
                    (SELECT COUNT(*) FROM blocked b WHERE b.campaign_id = c.id AND b.time BETWEEN :start AND :end) AS blocked_range,
                    (SELECT MIN(time) FROM blocked b WHERE b.campaign_id = c.id) AS blocked_oldest,
                    (SELECT MAX(time) FROM blocked b WHERE b.campaign_id = c.id) AS blocked_newest
                 FROM campaigns c
                 ORDER BY c.name COLLATE NOCASE, c.id'
            );
            if ($statement === false) {
                throw new RuntimeException('Failed to prepare database summary: ' . $db->lastErrorMsg());
            }
            $statement->bindValue(':start', $start, SQLITE3_INTEGER);
            $statement->bindValue(':end', $end, SQLITE3_INTEGER);
            $result = $statement->execute();
            if ($result === false) {
                throw new RuntimeException('Failed to read database summary: ' . $db->lastErrorMsg());
            }

            $campaigns = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $oldest = $this->minimumTimestamp([$row['clicks_oldest'] ?? null, $row['blocked_oldest'] ?? null]);
                $newest = $this->maximumTimestamp([$row['clicks_newest'] ?? null, $row['blocked_newest'] ?? null]);
                $clicksTotal = (int)($row['clicks_total'] ?? 0);
                $clicksRange = (int)($row['clicks_range'] ?? 0);
                $blockedTotal = (int)($row['blocked_total'] ?? 0);
                $blockedRange = (int)($row['blocked_range'] ?? 0);
                $campaigns[] = [
                    'id' => (int)$row['id'],
                    'name' => (string)$row['name'],
                    'clicksTotal' => $clicksTotal,
                    'clicksRange' => $clicksRange,
                    'blockedTotal' => $blockedTotal,
                    'blockedRange' => $blockedRange,
                    'recordsTotal' => $clicksTotal + $blockedTotal,
                    'recordsRange' => $clicksRange + $blockedRange,
                    'oldest' => $oldest,
                    'newest' => $newest,
                ];
            }

            $trafficback = $this->querySingleRow(
                $db,
                'SELECT COUNT(*) AS records_total,
                    SUM(CASE WHEN time BETWEEN :start AND :end THEN 1 ELSE 0 END) AS records_range,
                    MIN(time) AS oldest, MAX(time) AS newest
                 FROM trafficback',
                [':start' => [$start, SQLITE3_INTEGER], ':end' => [$end, SQLITE3_INTEGER]],
            );

            return [
                'database' => $this->databaseMetrics($db),
                'campaigns' => $campaigns,
                'trafficback' => [
                    'recordsTotal' => (int)($trafficback['records_total'] ?? 0),
                    'recordsRange' => (int)($trafficback['records_range'] ?? 0),
                    'oldest' => isset($trafficback['oldest']) ? (int)$trafficback['oldest'] : null,
                    'newest' => isset($trafficback['newest']) ? (int)$trafficback['newest'] : null,
                ],
                'recent' => $this->recentActivity($db, [], true, true),
                'activeJob' => null,
            ];
        } finally {
            $db->close();
        }
    }

    /**
     * @param array<int, int|string> $campaignIds
     * @return array<string, mixed>
     */
    public function preview(array $campaignIds, bool $includeTrafficback, int $start, int $end): array
    {
        $this->assertTimestampRange($start, $end);
        $ids = $this->normalizeCampaignIds($campaignIds);
        if ($ids === [] && !$includeTrafficback) {
            throw new InvalidArgumentException('Select at least one campaign or Trafficback');
        }

        $db = $this->openDatabase(true);
        try {
            $campaigns = $this->campaignNames($db, $ids);
            $bounds = $this->selectionBounds($db, $ids, $includeTrafficback);
            $counts = $this->selectionCounts($db, $ids, $includeTrafficback, $start, $end, $bounds);
            $recent = $this->recentActivity($db, $ids, $includeTrafficback);
            return [
                'campaigns' => $campaigns,
                'includeTrafficback' => $includeTrafficback,
                'start' => $start,
                'end' => $end,
                'counts' => $counts,
                'recent' => $recent,
                'database' => $this->databaseMetrics($db),
            ];
        } finally {
            $db->close();
        }
    }

    /**
     * @param array<int, int|string> $campaignIds
     * @return array<string, mixed>
     */
    public function startCleanup(
        array $campaignIds,
        bool $includeTrafficback,
        int $start,
        int $end,
        string $owner,
    ): array {
        return $this->withMaintenanceLock(function () use ($campaignIds, $includeTrafficback, $start, $end, $owner): array {
            $this->assertNoRunningJob();
            $this->assertTimestampRange($start, $end);
            $ids = $this->normalizeCampaignIds($campaignIds);
            if ($ids === [] && !$includeTrafficback) {
                throw new InvalidArgumentException('Select at least one campaign or Trafficback');
            }

            $db = $this->openDatabase(true);
            try {
                $campaigns = $this->campaignNames($db, $ids);
                $bounds = $this->selectionBounds($db, $ids, $includeTrafficback);
                $counts = $this->selectionCounts($db, $ids, $includeTrafficback, $start, $end, $bounds);
                $recent = $this->recentActivity($db, $ids, $includeTrafficback);
                $database = $this->databaseMetrics($db);
            } finally {
                $db->close();
            }
            if (($counts['total'] ?? 0) < 1) {
                throw new InvalidArgumentException('No records match the selected campaigns and date range');
            }

            $now = ($this->clock)();
            $job = [
                'id' => bin2hex(random_bytes(16)),
                'kind' => 'cleanup',
                'status' => 'deleting',
                'stage' => 'clicks',
                'ownerHash' => $this->ownerHash($owner),
                'campaignIds' => $ids,
                'campaigns' => $campaigns,
                'includeTrafficback' => $includeTrafficback,
                'start' => $start,
                'end' => $end,
                'bounds' => $bounds,
                'initial' => $counts,
                'deleted' => ['clicks' => 0, 'blocked' => 0, 'trafficback' => 0, 'total' => 0],
                'recent' => $recent,
                'databaseBefore' => $database,
                'databaseAfter' => null,
                'physicalReclaimedBytes' => null,
                'message' => 'Cleanup is ready to run.',
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
            $this->writeJob($job);
            $this->log('info', 'Database cleanup started', [
                'jobId' => $job['id'],
                'campaignIds' => $ids,
                'includeTrafficback' => $includeTrafficback,
                'start' => $start,
                'end' => $end,
                'records' => $counts,
            ]);
            return $this->publicJob($job);
        });
    }

    /** @return array<string, mixed>|null */
    public function status(string $owner, ?string $jobId = null): ?array
    {
        $job = $this->readJob();
        if ($job === null) {
            return null;
        }
        if ($jobId !== null && !hash_equals((string)($job['id'] ?? ''), $jobId)) {
            throw new InvalidArgumentException('Maintenance job not found');
        }
        $this->assertJobOwner($job, $owner);
        return $this->publicJob($job);
    }

    /** @return array<string, mixed>|null */
    public function activeJobStatus(string $owner): ?array
    {
        $job = $this->readJob();
        if ($job === null || in_array((string)($job['status'] ?? ''), ['completed', 'cancelled'], true)) {
            return null;
        }
        $this->assertJobOwner($job, $owner);
        return $this->publicJob($job);
    }

    /** @return array<string, mixed> */
    public function runBatch(string $jobId, string $owner): array
    {
        return $this->withMaintenanceLock(function () use ($jobId, $owner): array {
            $job = $this->requireOwnedJob($jobId, $owner);
            if (($job['status'] ?? '') !== 'deleting') {
                return $this->publicJob($job);
            }

            $db = $this->openDatabase(false);
            $deletedStage = null;
            $deletedRows = 0;
            try {
                if (!$db->exec('BEGIN IMMEDIATE')) {
                    $this->throwDatabaseError($db, 'Unable to begin cleanup batch');
                }
                while ($deletedRows === 0 && ($job['status'] ?? '') === 'deleting') {
                    $stage = (string)($job['stage'] ?? 'clicks');
                    if ($stage === 'clicks') {
                        $deletedRows = $this->deleteCampaignBatch($db, 'clicks', $job);
                        $deletedStage = 'clicks';
                        if ($deletedRows === 0) {
                            $job['stage'] = 'blocked';
                            continue;
                        }
                    } elseif ($stage === 'blocked') {
                        $deletedRows = $this->deleteCampaignBatch($db, 'blocked', $job);
                        $deletedStage = 'blocked';
                        if ($deletedRows === 0) {
                            $job['stage'] = !empty($job['includeTrafficback']) ? 'trafficback' : 'complete';
                            continue;
                        }
                    } elseif ($stage === 'trafficback') {
                        $deletedRows = $this->deleteTrafficbackBatch($db, $job);
                        $deletedStage = 'trafficback';
                        if ($deletedRows === 0) {
                            $job['stage'] = 'complete';
                            continue;
                        }
                    } else {
                        $job['status'] = 'ready_to_compact';
                        $job['stage'] = 'compact';
                        $job['message'] = 'Deletion completed. Database compaction is ready.';
                    }
                }
                if (!$db->exec('COMMIT')) {
                    $this->throwDatabaseError($db, 'Unable to commit cleanup batch');
                }
            } catch (Throwable $exception) {
                @$db->exec('ROLLBACK');
                if ($exception instanceof DatabaseMaintenanceBusyException) {
                    throw $exception;
                }
                $this->throwDatabaseError($db, $exception->getMessage(), $exception);
            } finally {
                if ($deletedRows > 0) {
                    @$db->exec('PRAGMA wal_checkpoint(PASSIVE)');
                }
                $db->close();
            }

            if ($deletedRows > 0 && $deletedStage !== null) {
                $job['deleted'][$deletedStage] = (int)($job['deleted'][$deletedStage] ?? 0) + $deletedRows;
                $job['deleted']['total'] = (int)($job['deleted']['total'] ?? 0) + $deletedRows;
                $job['message'] = 'Deleted '
                    . number_format((int)$job['deleted']['total'])
                    . ' of '
                    . number_format((int)($job['initial']['total'] ?? 0))
                    . ' records.';
            }
            $job['updatedAt'] = ($this->clock)();
            $this->writeJob($job);

            if (($job['status'] ?? '') === 'ready_to_compact') {
                $this->log('info', 'Database cleanup deletion completed', [
                    'jobId' => $job['id'],
                    'deleted' => $job['deleted'],
                ]);
            }
            return $this->publicJob($job);
        });
    }

    /** @return array<string, mixed> */
    public function cancel(string $jobId, string $owner): array
    {
        return $this->withMaintenanceLock(function () use ($jobId, $owner): array {
            $job = $this->requireOwnedJob($jobId, $owner);
            if (in_array((string)($job['status'] ?? ''), ['completed', 'cancelled'], true)) {
                return $this->publicJob($job);
            }
            if (($job['status'] ?? '') === 'compacting') {
                throw new DatabaseMaintenanceBusyException('Database compaction is already running');
            }
            $job['status'] = 'cancelled';
            $job['message'] = 'Cleanup stopped. Already deleted records were not restored.';
            $job['updatedAt'] = ($this->clock)();
            $this->writeJob($job);
            $this->log('warning', 'Database cleanup cancelled', [
                'jobId' => $job['id'],
                'deleted' => $job['deleted'] ?? [],
            ]);
            return $this->publicJob($job);
        });
    }

    /** @return array<string, mixed> */
    public function startStandaloneCompaction(string $owner): array
    {
        return $this->withMaintenanceLock(function () use ($owner): array {
            $this->assertNoRunningJob();
            $db = $this->openDatabase(true);
            try {
                $database = $this->databaseMetrics($db);
                $recent = $this->recentActivity($db, [], true, true);
            } finally {
                $db->close();
            }
            $now = ($this->clock)();
            $job = [
                'id' => bin2hex(random_bytes(16)),
                'kind' => 'compact',
                'status' => 'ready_to_compact',
                'stage' => 'compact',
                'ownerHash' => $this->ownerHash($owner),
                'campaignIds' => [],
                'campaigns' => [],
                'includeTrafficback' => false,
                'start' => null,
                'end' => null,
                'initial' => ['clicks' => 0, 'blocked' => 0, 'trafficback' => 0, 'total' => 0],
                'deleted' => ['clicks' => 0, 'blocked' => 0, 'trafficback' => 0, 'total' => 0],
                'recent' => $recent,
                'databaseBefore' => $database,
                'databaseAfter' => null,
                'physicalReclaimedBytes' => null,
                'message' => 'Database compaction is ready.',
                'createdAt' => $now,
                'updatedAt' => $now,
            ];
            $this->writeJob($job);
            return $this->publicJob($job);
        });
    }

    /** @return array<string, mixed> */
    public function compact(string $jobId, string $owner): array
    {
        return $this->withMaintenanceLock(function () use ($jobId, $owner): array {
            $job = $this->requireOwnedJob($jobId, $owner);
            if (($job['status'] ?? '') === 'completed') {
                return $this->publicJob($job);
            }
            if (!in_array((string)($job['status'] ?? ''), ['ready_to_compact', 'compaction_required'], true)) {
                throw new InvalidArgumentException('This maintenance job is not ready for compaction');
            }

            $db = $this->openDatabase(false, 15000);
            try {
                $checkpoint = $db->query('PRAGMA wal_checkpoint(TRUNCATE)');
                $checkpointRow = $checkpoint?->fetchArray(SQLITE3_NUM);
                if (is_array($checkpointRow) && (int)($checkpointRow[0] ?? 0) !== 0) {
                    throw new DatabaseMaintenanceBusyException('SQLite is busy; compaction can be retried');
                }
            } finally {
                $db->close();
            }

            $before = $this->databaseMetrics();
            $mainBytes = (int)($before['mainBytes'] ?? 0);
            $freeBytes = $before['freeDiskBytes'] ?? null;
            $requiredFreeBytes = $mainBytes + max(64 * 1024 * 1024, (int)ceil($mainBytes * 0.2));
            if (!is_int($freeBytes) || $freeBytes < $requiredFreeBytes) {
                $job['status'] = 'compaction_required';
                $job['message'] = $freeBytes === null
                    ? 'Compaction was skipped because free disk space could not be determined.'
                    : 'Compaction needs more free disk space.';
                $job['compaction'] = [
                    'requiredFreeBytes' => $requiredFreeBytes,
                    'availableFreeBytes' => $freeBytes,
                ];
                $job['updatedAt'] = ($this->clock)();
                $this->writeJob($job);
                $this->log('warning', 'Database compaction skipped', [
                    'jobId' => $job['id'],
                    'requiredFreeBytes' => $requiredFreeBytes,
                    'availableFreeBytes' => $freeBytes,
                ]);
                return $this->publicJob($job);
            }

            $job['status'] = 'compacting';
            $job['message'] = 'VACUUM is rebuilding the SQLite database.';
            $job['compaction'] = [
                'requiredFreeBytes' => $requiredFreeBytes,
                'availableFreeBytes' => $freeBytes,
            ];
            $job['updatedAt'] = ($this->clock)();
            $this->writeJob($job);

            @set_time_limit(0);
            @ignore_user_abort(true);
            try {
                $db = $this->openDatabase(false, 15000);
                try {
                    if (!$db->exec('VACUUM')) {
                        $this->throwDatabaseError($db, 'VACUUM failed');
                    }
                    @$db->exec('PRAGMA optimize');
                    @$db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
                } finally {
                    $db->close();
                }
            } catch (Throwable $exception) {
                $job['status'] = 'compaction_required';
                $job['message'] = 'Compaction failed and can be retried: ' . $exception->getMessage();
                $job['updatedAt'] = ($this->clock)();
                $this->writeJob($job);
                $this->log('error', 'Database compaction failed', [
                    'jobId' => $job['id'],
                    'error' => $exception->getMessage(),
                ]);
                if ($exception instanceof DatabaseMaintenanceBusyException) {
                    throw $exception;
                }
                return $this->publicJob($job);
            }

            clearstatcache(true, $this->databasePath);
            $after = $this->databaseMetrics();
            $beforeBytes = (int)($job['databaseBefore']['bytes'] ?? $before['bytes'] ?? 0);
            $afterBytes = (int)($after['bytes'] ?? 0);
            $job['status'] = 'completed';
            $job['message'] = 'Database maintenance completed.';
            $job['databaseAfter'] = $after;
            $job['physicalReclaimedBytes'] = max(0, $beforeBytes - $afterBytes);
            $job['updatedAt'] = ($this->clock)();
            $this->writeJob($job);
            @unlink($this->rootPath('tmp/system-status.json'));
            $this->log('info', 'Database maintenance completed', [
                'jobId' => $job['id'],
                'kind' => $job['kind'] ?? 'cleanup',
                'deleted' => $job['deleted'] ?? [],
                'databaseBytesBefore' => $beforeBytes,
                'databaseBytesAfter' => $afterBytes,
                'physicalReclaimedBytes' => $job['physicalReclaimedBytes'],
            ]);
            return $this->publicJob($job);
        });
    }

    /** @return array<string, mixed> */
    private function databaseMetrics(?SQLite3 $db = null): array
    {
        $close = false;
        if ($db === null) {
            $db = $this->openDatabase(true);
            $close = true;
        }
        try {
            $pageSize = (int)$db->querySingle('PRAGMA page_size');
            $pageCount = (int)$db->querySingle('PRAGMA page_count');
            $freelistCount = (int)$db->querySingle('PRAGMA freelist_count');
        } finally {
            if ($close) {
                $db->close();
            }
        }

        clearstatcache(true, $this->databasePath);
        $mainBytes = $this->fileSize($this->databasePath);
        $walBytes = $this->fileSize($this->databasePath . '-wal');
        $shmBytes = $this->fileSize($this->databasePath . '-shm');
        $diskFree = ($this->diskFree)($this->root);
        return [
            'bytes' => $mainBytes + $walBytes + $shmBytes,
            'mainBytes' => $mainBytes,
            'walBytes' => $walBytes,
            'shmBytes' => $shmBytes,
            'pageSize' => $pageSize,
            'pageCount' => $pageCount,
            'freelistCount' => $freelistCount,
            'reclaimableBytes' => $pageSize * $freelistCount,
            'freeDiskBytes' => is_int($diskFree) || is_float($diskFree) ? (int)$diskFree : null,
        ];
    }

    /** @param array<int, int> $ids @return array<int, array{id: int, name: string}> */
    private function campaignNames(SQLite3 $db, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $params = [];
        $in = $this->inClause($ids, 'campaign', $params);
        $statement = $this->prepare($db, 'SELECT id, name FROM campaigns WHERE id IN (' . $in . ') ORDER BY name COLLATE NOCASE, id', $params);
        $result = $statement->execute();
        if ($result === false) {
            throw new RuntimeException('Failed to validate campaigns: ' . $db->lastErrorMsg());
        }
        $campaigns = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $campaigns[] = ['id' => (int)$row['id'], 'name' => (string)$row['name']];
        }
        if (count($campaigns) !== count($ids)) {
            throw new InvalidArgumentException('One or more selected campaigns no longer exist');
        }
        return $campaigns;
    }

    /**
     * @param array<int, int> $ids
     * @return array{clicks: int, blocked: int, trafficback: int}
     */
    private function selectionBounds(SQLite3 $db, array $ids, bool $includeTrafficback): array
    {
        $clicks = 0;
        $blocked = 0;
        if ($ids !== []) {
            $params = [];
            $in = $this->inClause($ids, 'bound', $params);
            $clicks = (int)$this->querySingleValue($db, 'SELECT COALESCE(MAX(id), 0) FROM clicks WHERE campaign_id IN (' . $in . ')', $params);
            $blocked = (int)$this->querySingleValue($db, 'SELECT COALESCE(MAX(id), 0) FROM blocked WHERE campaign_id IN (' . $in . ')', $params);
        }
        return [
            'clicks' => $clicks,
            'blocked' => $blocked,
            'trafficback' => $includeTrafficback ? (int)$db->querySingle('SELECT COALESCE(MAX(id), 0) FROM trafficback') : 0,
        ];
    }

    /**
     * @param array<int, int> $ids
     * @param array{clicks: int, blocked: int, trafficback: int} $bounds
     * @return array{clicks: int, blocked: int, trafficback: int, total: int}
     */
    private function selectionCounts(
        SQLite3 $db,
        array $ids,
        bool $includeTrafficback,
        int $start,
        int $end,
        array $bounds,
    ): array {
        $clicks = 0;
        $blocked = 0;
        if ($ids !== []) {
            $params = [
                ':start' => [$start, SQLITE3_INTEGER],
                ':end' => [$end, SQLITE3_INTEGER],
            ];
            $in = $this->inClause($ids, 'count', $params);
            $clicksParams = $params + [':max_id' => [$bounds['clicks'], SQLITE3_INTEGER]];
            $blockedParams = $params + [':max_id' => [$bounds['blocked'], SQLITE3_INTEGER]];
            $clicks = (int)$this->querySingleValue(
                $db,
                'SELECT COUNT(*) FROM clicks WHERE campaign_id IN (' . $in . ') AND time BETWEEN :start AND :end AND id <= :max_id',
                $clicksParams,
            );
            $blocked = (int)$this->querySingleValue(
                $db,
                'SELECT COUNT(*) FROM blocked WHERE campaign_id IN (' . $in . ') AND time BETWEEN :start AND :end AND id <= :max_id',
                $blockedParams,
            );
        }
        $trafficback = 0;
        if ($includeTrafficback) {
            $trafficback = (int)$this->querySingleValue(
                $db,
                'SELECT COUNT(*) FROM trafficback WHERE time BETWEEN :start AND :end AND id <= :max_id',
                [
                    ':start' => [$start, SQLITE3_INTEGER],
                    ':end' => [$end, SQLITE3_INTEGER],
                    ':max_id' => [$bounds['trafficback'], SQLITE3_INTEGER],
                ],
            );
        }
        return [
            'clicks' => $clicks,
            'blocked' => $blocked,
            'trafficback' => $trafficback,
            'total' => $clicks + $blocked + $trafficback,
        ];
    }

    /**
     * @param array<int, int> $ids
     * @return array{clicks: int, trafficback: int, total: int, since: int}
     */
    private function recentActivity(SQLite3 $db, array $ids, bool $includeTrafficback, bool $allCampaigns = false): array
    {
        $since = ($this->clock)() - self::RECENT_WINDOW_SECONDS;
        $clicks = 0;
        if ($allCampaigns) {
            $clicks = (int)$this->querySingleValue(
                $db,
                'SELECT COUNT(*) FROM clicks WHERE time >= :since',
                [':since' => [$since, SQLITE3_INTEGER]],
            );
        } elseif ($ids !== []) {
            $params = [':since' => [$since, SQLITE3_INTEGER]];
            $in = $this->inClause($ids, 'recent', $params);
            $clicks = (int)$this->querySingleValue(
                $db,
                'SELECT COUNT(*) FROM clicks WHERE campaign_id IN (' . $in . ') AND time >= :since',
                $params,
            );
        }
        $trafficback = $includeTrafficback
            ? (int)$this->querySingleValue(
                $db,
                'SELECT COUNT(*) FROM trafficback WHERE time >= :since',
                [':since' => [$since, SQLITE3_INTEGER]],
            )
            : 0;
        return ['clicks' => $clicks, 'trafficback' => $trafficback, 'total' => $clicks + $trafficback, 'since' => $since];
    }

    /** @param array<string, mixed> $job */
    private function deleteCampaignBatch(SQLite3 $db, string $table, array $job): int
    {
        if (!in_array($table, ['clicks', 'blocked'], true)) {
            throw new InvalidArgumentException('Invalid cleanup table');
        }
        $ids = array_map('intval', $job['campaignIds'] ?? []);
        if ($ids === []) {
            return 0;
        }
        $params = [
            ':start' => [(int)$job['start'], SQLITE3_INTEGER],
            ':end' => [(int)$job['end'], SQLITE3_INTEGER],
            ':max_id' => [(int)($job['bounds'][$table] ?? 0), SQLITE3_INTEGER],
            ':limit' => [self::BATCH_SIZE, SQLITE3_INTEGER],
        ];
        $in = $this->inClause($ids, 'delete', $params);
        $sql = 'DELETE FROM ' . $table . ' WHERE id IN (
            SELECT id FROM ' . $table . '
            WHERE campaign_id IN (' . $in . ') AND time BETWEEN :start AND :end AND id <= :max_id
            ORDER BY id LIMIT :limit
        )';
        $statement = $this->prepare($db, $sql, $params);
        if ($statement->execute() === false) {
            $this->throwDatabaseError($db, 'Failed to delete ' . $table . ' batch');
        }
        return $db->changes();
    }

    /** @param array<string, mixed> $job */
    private function deleteTrafficbackBatch(SQLite3 $db, array $job): int
    {
        if (empty($job['includeTrafficback'])) {
            return 0;
        }
        $statement = $this->prepare(
            $db,
            'DELETE FROM trafficback WHERE id IN (
                SELECT id FROM trafficback
                WHERE time BETWEEN :start AND :end AND id <= :max_id
                ORDER BY id LIMIT :limit
             )',
            [
                ':start' => [(int)$job['start'], SQLITE3_INTEGER],
                ':end' => [(int)$job['end'], SQLITE3_INTEGER],
                ':max_id' => [(int)($job['bounds']['trafficback'] ?? 0), SQLITE3_INTEGER],
                ':limit' => [self::BATCH_SIZE, SQLITE3_INTEGER],
            ],
        );
        if ($statement->execute() === false) {
            $this->throwDatabaseError($db, 'Failed to delete Trafficback batch');
        }
        return $db->changes();
    }

    private function assertNoRunningJob(): void
    {
        $job = $this->readJob();
        if ($job === null) {
            return;
        }
        $status = (string)($job['status'] ?? '');
        $updatedAt = (int)($job['updatedAt'] ?? 0);
        if ($updatedAt > 0 && ($this->clock)() - $updatedAt > self::JOB_TTL_SECONDS) {
            $job['status'] = 'cancelled';
            $job['message'] = 'The previous maintenance job expired.';
            $job['updatedAt'] = ($this->clock)();
            $this->writeJob($job);
            return;
        }
        if (in_array($status, ['deleting', 'ready_to_compact', 'compaction_required', 'compacting'], true)) {
            throw new DatabaseMaintenanceBusyException('Another database maintenance job is active');
        }
    }

    /** @return array<string, mixed> */
    private function requireOwnedJob(string $jobId, string $owner): array
    {
        $job = $this->readJob();
        if ($job === null || !hash_equals((string)($job['id'] ?? ''), $jobId)) {
            throw new InvalidArgumentException('Maintenance job not found');
        }
        $this->assertJobOwner($job, $owner);
        return $job;
    }

    /** @param array<string, mixed> $job */
    private function assertJobOwner(array $job, string $owner): void
    {
        if (!hash_equals((string)($job['ownerHash'] ?? ''), $this->ownerHash($owner))) {
            throw new RuntimeException('Maintenance job belongs to another admin session');
        }
    }

    /** @return array<string, mixed> */
    private function publicJob(array $job): array
    {
        unset($job['ownerHash'], $job['bounds']);
        $initial = (int)($job['initial']['total'] ?? 0);
        $deleted = (int)($job['deleted']['total'] ?? 0);
        $job['remaining'] = max(0, $initial - $deleted);
        $job['progress'] = $initial > 0 ? min(100, round(($deleted / $initial) * 100, 1)) : 100;
        if (in_array((string)($job['status'] ?? ''), ['ready_to_compact', 'compaction_required', 'compacting', 'completed'], true)) {
            $job['progress'] = 100;
            $job['remaining'] = 0;
        }
        return $job;
    }

    private function ownerHash(string $owner): string
    {
        if ($owner === '') {
            throw new InvalidArgumentException('Admin session is required');
        }
        return hash('sha256', $owner);
    }

    /** @return array<string, mixed>|null */
    private function readJob(): ?array
    {
        $path = $this->jobPath();
        if (!is_file($path)) {
            return null;
        }
        $json = @file_get_contents($path);
        $job = json_decode((string)$json, true);
        return is_array($job) ? $job : null;
    }

    /** @param array<string, mixed> $job */
    private function writeJob(array $job): void
    {
        $this->ensureTmpDirectory();
        $json = json_encode($job, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Failed to encode maintenance job state');
        }
        $path = $this->jobPath();
        $temp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($temp, $json, LOCK_EX) === false || !@rename($temp, $path)) {
            @unlink($temp);
            throw new RuntimeException('Failed to persist maintenance job state');
        }
        @chmod($path, 0640);
    }

    /** @template T @param callable(): T $callback @return T */
    private function withMaintenanceLock(callable $callback): mixed
    {
        $this->ensureTmpDirectory();
        $handle = @fopen($this->lockPath(), 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Failed to open database maintenance lock');
        }
        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                throw new DatabaseMaintenanceBusyException('Database maintenance is busy; retry shortly');
            }
            return $callback();
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureTmpDirectory(): void
    {
        $directory = $this->rootPath('tmp');
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create the temporary directory');
        }
    }

    private function jobPath(): string
    {
        return $this->rootPath('tmp/database-maintenance-job.json');
    }

    private function lockPath(): string
    {
        return $this->rootPath('tmp/database-maintenance.lock');
    }

    private function openDatabase(bool $readOnly, int $busyTimeout = 5000): SQLite3
    {
        $db = new SQLite3($this->databasePath, $readOnly ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE);
        $db->busyTimeout($busyTimeout);
        if (!$readOnly) {
            $db->exec('PRAGMA foreign_keys = ON');
        }
        return $db;
    }

    /**
     * @param array<string, array{0: mixed, 1: int}> $params
     */
    private function prepare(SQLite3 $db, string $sql, array $params = []): SQLite3Stmt
    {
        $statement = $db->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Failed to prepare SQLite statement: ' . $db->lastErrorMsg());
        }
        foreach ($params as $name => [$value, $type]) {
            $statement->bindValue($name, $value, $type);
        }
        return $statement;
    }

    /**
     * @param array<string, array{0: mixed, 1: int}> $params
     */
    private function querySingleValue(SQLite3 $db, string $sql, array $params = []): mixed
    {
        $statement = $this->prepare($db, $sql, $params);
        $result = $statement->execute();
        if ($result === false) {
            throw new RuntimeException('Failed to execute SQLite query: ' . $db->lastErrorMsg());
        }
        $row = $result->fetchArray(SQLITE3_NUM);
        return is_array($row) ? ($row[0] ?? null) : null;
    }

    /**
     * @param array<string, array{0: mixed, 1: int}> $params
     * @return array<string, mixed>
     */
    private function querySingleRow(SQLite3 $db, string $sql, array $params = []): array
    {
        $statement = $this->prepare($db, $sql, $params);
        $result = $statement->execute();
        if ($result === false) {
            throw new RuntimeException('Failed to execute SQLite query: ' . $db->lastErrorMsg());
        }
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return is_array($row) ? $row : [];
    }

    /**
     * @param array<int, int> $values
     * @param array<string, array{0: mixed, 1: int}> $params
     */
    private function inClause(array $values, string $prefix, array &$params): string
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $name = ':' . $prefix . '_' . $index;
            $placeholders[] = $name;
            $params[$name] = [(int)$value, SQLITE3_INTEGER];
        }
        return implode(', ', $placeholders);
    }

    /** @param array<int, int|string> $campaignIds @return array<int, int> */
    private function normalizeCampaignIds(array $campaignIds): array
    {
        $normalized = [];
        foreach ($campaignIds as $campaignId) {
            if (filter_var($campaignId, FILTER_VALIDATE_INT) === false || (int)$campaignId < 1) {
                throw new InvalidArgumentException('Campaign ids must be positive integers');
            }
            $normalized[(int)$campaignId] = true;
        }
        $ids = array_keys($normalized);
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private function assertTimestampRange(int $start, int $end): void
    {
        if ($start < 0 || $end < 0 || $start > $end) {
            throw new InvalidArgumentException('Invalid cleanup timestamp range');
        }
    }

    /** @param array<int, mixed> $values */
    private function minimumTimestamp(array $values): ?int
    {
        $values = array_values(array_filter($values, static fn(mixed $value): bool => $value !== null));
        return $values === [] ? null : min(array_map('intval', $values));
    }

    /** @param array<int, mixed> $values */
    private function maximumTimestamp(array $values): ?int
    {
        $values = array_values(array_filter($values, static fn(mixed $value): bool => $value !== null));
        return $values === [] ? null : max(array_map('intval', $values));
    }

    private function fileSize(string $path): int
    {
        if (!is_file($path)) {
            return 0;
        }
        $size = @filesize($path);
        return $size === false ? 0 : (int)$size;
    }

    private function throwDatabaseError(SQLite3 $db, string $message, ?Throwable $previous = null): never
    {
        $code = $db->lastErrorCode();
        $detail = $db->lastErrorMsg();
        if (in_array($code, [5, 6], true) || str_contains(strtolower($detail), 'locked') || str_contains(strtolower($detail), 'busy')) {
            throw new DatabaseMaintenanceBusyException($message . ': ' . $detail, $code, $previous);
        }
        throw new RuntimeException($message . ': ' . $detail, $code, $previous);
    }

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context = []): void
    {
        try {
            ($this->logger)($level, 'database-maintenance', $message, $context);
        } catch (Throwable) {
            // Maintenance must not fail only because operational logging is unavailable.
        }
    }

    private function rootPath(string $relative): string
    {
        return $this->root . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relative, '/\\'));
    }
}
