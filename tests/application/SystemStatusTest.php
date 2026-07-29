<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/systemstatus.php';

class SystemStatusTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yellowtds_status_' . bin2hex(random_bytes(5));
        mkdir($this->root . '/db', 0755, true);
        mkdir($this->root . '/storage/landings', 0755, true);
        mkdir($this->root . '/logs/error', 0755, true);
        mkdir($this->root . '/tmp', 0755, true);

        file_put_contents($this->root . '/db/custom.db', str_repeat('d', 20));
        file_put_contents($this->root . '/db/custom.db-wal', str_repeat('w', 7));
        file_put_contents($this->root . '/db/custom.db-shm', str_repeat('s', 3));
        file_put_contents($this->root . '/storage/landing.html', str_repeat('c', 11));
        file_put_contents($this->root . '/storage/landings/page.html', str_repeat('l', 13));
        file_put_contents($this->root . '/logs/error/error.log', str_repeat('e', 5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testCollectsConfiguredDatabaseCacheAndLogSizes(): void
    {
        $status = (new SystemStatus($this->root, [
            'dbConnection' => 'custom.db',
            'cachingDir' => 'storage',
        ]))->collect();

        $this->assertSame(30, $status['database']['bytes']);
        $this->assertTrue($status['database']['available']);
        $this->assertSame(24, $status['cache']['bytes']);
        $this->assertTrue($status['cache']['available']);
        $this->assertSame(5, $status['logs']['bytes']);
        $this->assertTrue($status['logs']['available']);
        $this->assertIsInt($status['disk']['freeBytes']);
        $this->assertIsFloat($status['disk']['freePercent']);
    }

    public function testMissingPathsAreReportedAsUnavailable(): void
    {
        $status = (new SystemStatus($this->root, [
            'dbConnection' => 'missing.db',
            'cachingDir' => 'missing-cache',
        ]))->collect();

        $this->assertSame(['bytes' => null, 'available' => false], $status['database']);
        $this->assertSame(['bytes' => null, 'available' => false], $status['cache']);
    }

    public function testGetReusesCachedStatusUntilItExpires(): void
    {
        $service = new SystemStatus($this->root, [
            'dbConnection' => 'custom.db',
            'cachingDir' => 'storage',
        ], 60);

        $first = $service->get();
        file_put_contents($this->root . '/storage/new.bin', str_repeat('n', 100));
        $cached = $service->get();
        $refreshed = $service->get(true);

        $this->assertSame($first['cache']['bytes'], $cached['cache']['bytes']);
        $this->assertSame($first['cache']['bytes'] + 100, $refreshed['cache']['bytes']);
    }

    private function remove(string $path): void
    {
        if (!file_exists($path)) return;
        if (!is_dir($path) || is_link($path)) { @unlink($path); return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $this->remove($path . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($path);
    }
}
