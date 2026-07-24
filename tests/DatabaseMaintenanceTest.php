<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../databasemaintenance.php';
require_once __DIR__ . '/../admin/databasemaintenance.php';

final class DatabaseMaintenanceTest extends TestCase
{
    private string $root;
    private string $databasePath;
    private int $now = 1760000000;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yellowtds_maintenance_' . bin2hex(random_bytes(5));
        mkdir($this->root . '/db', 0755, true);
        mkdir($this->root . '/tmp', 0755, true);
        mkdir($this->root . '/logs', 0755, true);
        $this->databasePath = $this->root . '/db/test.db';

        $db = new SQLite3($this->databasePath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $schema = file_get_contents(__DIR__ . '/../db/db.sql');
        self::assertIsString($schema);
        self::assertTrue($db->exec($schema));
        self::assertTrue($db->exec("INSERT INTO common (settings) VALUES ('{}')"));
        $db->close();
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testParsesInclusiveRangeInConfiguredTimezone(): void
    {
        [$start, $end] = DatabaseMaintenance::parseDateRange('01.10.25', '02.10.25', 'Europe/Samara');
        $zone = new DateTimeZone('Europe/Samara');

        self::assertSame('2025-10-01 00:00:00', (new DateTimeImmutable('@' . $start))->setTimezone($zone)->format('Y-m-d H:i:s'));
        self::assertSame('2025-10-02 23:59:59', (new DateTimeImmutable('@' . $end))->setTimezone($zone)->format('Y-m-d H:i:s'));
    }

    public function testAdminEndpointRequiresExactTokensAndDeleteConfirmation(): void
    {
        self::assertTrue(database_maintenance_token_matches('nonce', 'nonce'));
        self::assertFalse(database_maintenance_token_matches('nonce', 'wrong'));
        self::assertFalse(database_maintenance_token_matches('', ''));
        database_maintenance_assert_confirmation('DELETE');

        $this->expectException(InvalidArgumentException::class);
        database_maintenance_assert_confirmation('delete');
    }

    public function testSummaryReportsCampaignAndTrafficbackCounts(): void
    {
        $this->seedCampaign(1, 'First');
        $this->seedCampaign(2, 'Second');
        $this->seedClick(1, 'one-in', 100);
        $this->seedClick(1, 'one-out', 300);
        $this->seedClick(2, 'two-in', 150);
        $this->seedBlocked(1, 160);
        $this->seedTrafficback(170);
        $this->seedTrafficback(400);

        $summary = $this->service()->summary(90, 200);

        self::assertCount(2, $summary['campaigns']);
        self::assertSame(2, $summary['campaigns'][0]['clicksTotal']);
        self::assertSame(1, $summary['campaigns'][0]['clicksRange']);
        self::assertSame(1, $summary['campaigns'][0]['blockedRange']);
        self::assertSame(2, $summary['campaigns'][0]['recordsRange']);
        self::assertSame(1, $summary['trafficback']['recordsRange']);
        self::assertSame(2, $summary['trafficback']['recordsTotal']);
        self::assertGreaterThan(0, $summary['database']['bytes']);
    }

    public function testCleanupUsesThousandRowBatchesAndCascadesWithoutDeletingNewRows(): void
    {
        $this->seedCampaign(1, 'Selected');
        $this->seedCampaign(2, 'Keep');
        $db = $this->open();
        $db->exec('BEGIN');
        for ($index = 0; $index < 1002; $index++) {
            $clickId = 'selected-' . $index;
            $this->insertClick($db, 1, $clickId, 120);
            $step = $db->prepare('INSERT INTO click_steps (clickid, step, variant, time) VALUES (:clickid, 0, :variant, 120)');
            $step->bindValue(':clickid', $clickId, SQLITE3_TEXT);
            $step->bindValue(':variant', 'variant-' . $index, SQLITE3_TEXT);
            $step->execute();
        }
        $db->exec('COMMIT');
        $db->close();
        $this->seedEvent('selected-0', 120);
        $this->seedClick(1, 'outside-range', 500);
        $this->seedClick(2, 'other-campaign', 120);
        $this->seedBlocked(1, 130);
        $this->seedTrafficback(140);

        $service = $this->service();
        $job = $service->startCleanup([1], false, 100, 200, 'owner-one');
        self::assertSame(1003, $job['initial']['total']);

        // This click arrives after the cleanup target snapshot and must survive.
        $this->seedClick(1, 'arrived-later', 150);

        $firstBatch = $service->runBatch($job['id'], 'owner-one');
        self::assertSame(DatabaseMaintenance::BATCH_SIZE, $firstBatch['deleted']['clicks']);
        self::assertSame('deleting', $firstBatch['status']);
        self::assertSame('Deleted 1,000 of 1,003 records.', $firstBatch['message']);

        $current = $service->runBatch($job['id'], 'owner-one');
        self::assertSame(1002, $current['deleted']['clicks']);
        self::assertSame('Deleted 1,002 of 1,003 records.', $current['message']);
        for ($attempt = 0; $attempt < 10 && $current['status'] === 'deleting'; $attempt++) {
            $current = $service->runBatch($job['id'], 'owner-one');
        }
        self::assertSame('ready_to_compact', $current['status']);
        self::assertSame(1002, $current['deleted']['clicks']);
        self::assertSame(1, $current['deleted']['blocked']);

        $db = $this->open(true);
        self::assertSame(0, (int)$db->querySingle("SELECT COUNT(*) FROM clicks WHERE clickid LIKE 'selected-%'"));
        self::assertSame(0, (int)$db->querySingle('SELECT COUNT(*) FROM click_steps'));
        self::assertSame(1, (int)$db->querySingle("SELECT COUNT(*) FROM clicks WHERE clickid = 'arrived-later'"));
        self::assertSame(1, (int)$db->querySingle("SELECT COUNT(*) FROM clicks WHERE clickid = 'outside-range'"));
        self::assertSame(1, (int)$db->querySingle("SELECT COUNT(*) FROM clicks WHERE clickid = 'other-campaign'"));
        self::assertSame(1, (int)$db->querySingle('SELECT COUNT(*) FROM trafficback'));
        self::assertSame(2, (int)$db->querySingle('SELECT COUNT(*) FROM campaigns'));
        self::assertSame(1, (int)$db->querySingle('SELECT COUNT(*) FROM common'));
        $db->close();
    }

    public function testTrafficbackMustBeSelectedSeparately(): void
    {
        $this->seedCampaign(1, 'Campaign');
        $this->seedClick(1, 'keep-click', 120);
        $this->seedTrafficback(120);
        $this->seedTrafficback(300);

        $service = $this->service();
        $job = $service->startCleanup([], true, 100, 200, 'trafficback-owner');
        $current = $job;
        for ($attempt = 0; $attempt < 5 && $current['status'] === 'deleting'; $attempt++) {
            $current = $service->runBatch($job['id'], 'trafficback-owner');
        }

        self::assertSame('ready_to_compact', $current['status']);
        self::assertSame(1, $current['deleted']['trafficback']);
        $db = $this->open(true);
        self::assertSame(1, (int)$db->querySingle('SELECT COUNT(*) FROM trafficback'));
        self::assertSame(1, (int)$db->querySingle('SELECT COUNT(*) FROM clicks'));
        $db->close();
    }

    public function testJobCanBeReadBySameOwnerAndCancelled(): void
    {
        $this->seedCampaign(1, 'Campaign');
        $this->seedClick(1, 'click', 120);
        $service = $this->service();
        $job = $service->startCleanup([1], false, 100, 200, 'same-session');

        self::assertSame($job['id'], $this->service()->status('same-session')['id']);
        self::assertSame($job['id'], $this->service()->activeJobStatus('same-session')['id']);
        $cancelled = $service->cancel($job['id'], 'same-session');
        self::assertSame('cancelled', $cancelled['status']);
        self::assertNull($service->activeJobStatus('same-session'));
        self::assertNull($service->activeJobStatus('different-session'));
        $this->expectException(RuntimeException::class);
        $service->status('different-session');
    }

    public function testVacuumCompactsDatabaseAndClearsFreelist(): void
    {
        $this->seedCampaign(1, 'Campaign');
        $db = $this->open();
        $db->exec('BEGIN');
        $payload = str_repeat('x', 8000);
        for ($index = 0; $index < 300; $index++) {
            $statement = $db->prepare('INSERT INTO trafficback (time, ip, params) VALUES (100, :ip, :params)');
            $statement->bindValue(':ip', '127.0.0.1', SQLITE3_TEXT);
            $statement->bindValue(':params', $payload . $index, SQLITE3_TEXT);
            $statement->execute();
        }
        $db->exec('COMMIT');
        $db->exec('DELETE FROM trafficback');
        $db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
        $freelistBefore = (int)$db->querySingle('PRAGMA freelist_count');
        $db->close();
        self::assertGreaterThan(0, $freelistBefore);

        $service = $this->service(1024 * 1024 * 1024);
        $job = $service->startStandaloneCompaction('compact-owner');
        $completed = $service->compact($job['id'], 'compact-owner');

        self::assertSame('completed', $completed['status']);
        self::assertSame(0, $completed['databaseAfter']['freelistCount']);
        self::assertLessThanOrEqual($completed['databaseBefore']['bytes'], $completed['databaseAfter']['bytes']);
        self::assertNull($service->activeJobStatus('compact-owner'));
    }

    public function testCompactionIsDeferredWhenFreeSpaceIsInsufficient(): void
    {
        $this->seedCampaign(1, 'Campaign');
        $service = $this->service(1);
        $job = $service->startStandaloneCompaction('low-disk-owner');
        $result = $service->compact($job['id'], 'low-disk-owner');

        self::assertSame('compaction_required', $result['status']);
        self::assertSame(1, $result['compaction']['availableFreeBytes']);
        self::assertGreaterThan(1, $result['compaction']['requiredFreeBytes']);
    }

    private function service(int $freeDisk = 10737418240): DatabaseMaintenance
    {
        return new DatabaseMaintenance(
            $this->root,
            ['dbConnection' => 'test.db', 'debug' => false],
            static fn(string $path): int => $freeDisk,
            fn(): int => $this->now,
            static fn(): bool => true,
        );
    }

    private function open(bool $readOnly = false): SQLite3
    {
        $db = new SQLite3($this->databasePath, $readOnly ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);
        if (!$readOnly) {
            $db->exec('PRAGMA foreign_keys = ON');
        }
        return $db;
    }

    private function seedCampaign(int $id, string $name): void
    {
        $db = $this->open();
        $statement = $db->prepare('INSERT INTO campaigns (id, name, settings) VALUES (:id, :name, :settings)');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $statement->bindValue(':name', $name, SQLITE3_TEXT);
        $statement->bindValue(':settings', '{}', SQLITE3_TEXT);
        $statement->execute();
        $db->close();
    }

    private function seedClick(int $campaignId, string $clickId, int $time): void
    {
        $db = $this->open();
        $this->insertClick($db, $campaignId, $clickId, $time);
        $db->close();
    }

    private function insertClick(SQLite3 $db, int $campaignId, string $clickId, int $time): void
    {
        $statement = $db->prepare(
            'INSERT INTO clicks (campaign_id, time, ip, userid, clickid, params)
             VALUES (:campaign_id, :time, :ip, :userid, :clickid, :params)'
        );
        $statement->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
        $statement->bindValue(':time', $time, SQLITE3_INTEGER);
        $statement->bindValue(':ip', '127.0.0.1', SQLITE3_TEXT);
        $statement->bindValue(':userid', 'user-' . $clickId, SQLITE3_TEXT);
        $statement->bindValue(':clickid', $clickId, SQLITE3_TEXT);
        $statement->bindValue(':params', '{}', SQLITE3_TEXT);
        self::assertNotFalse($statement->execute());
    }

    private function seedBlocked(int $campaignId, int $time): void
    {
        $db = $this->open();
        $statement = $db->prepare('INSERT INTO blocked (campaign_id, time, ip) VALUES (:campaign_id, :time, :ip)');
        $statement->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
        $statement->bindValue(':time', $time, SQLITE3_INTEGER);
        $statement->bindValue(':ip', '127.0.0.1', SQLITE3_TEXT);
        $statement->execute();
        $db->close();
    }

    private function seedTrafficback(int $time): void
    {
        $db = $this->open();
        $statement = $db->prepare('INSERT INTO trafficback (time, ip) VALUES (:time, :ip)');
        $statement->bindValue(':time', $time, SQLITE3_INTEGER);
        $statement->bindValue(':ip', '127.0.0.1', SQLITE3_TEXT);
        $statement->execute();
        $db->close();
    }

    private function seedEvent(string $clickId, int $time): void
    {
        $db = $this->open();
        $statement = $db->prepare(
            "UPDATE click_steps SET events = json_set(events, '$.scroll_50', :elapsed)
             WHERE clickid = :clickid AND time = :time"
        );
        $statement->bindValue(':clickid', $clickId, SQLITE3_TEXT);
        $statement->bindValue(':time', $time, SQLITE3_INTEGER);
        $statement->bindValue(':elapsed', 1, SQLITE3_INTEGER);
        $statement->execute();
        $db->close();
    }

    private function remove(string $path): void
    {
        if (!file_exists($path)) return;
        if (!is_dir($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $this->remove($path . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($path);
    }
}
