<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/logging.php';

final class LoggingTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ytds_logs_' . bin2hex(random_bytes(5));
        mkdir($this->root, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testWritesStructuredUnicodeRecord(): void
    {
        $logger = new YellowTdsLogger($this->root, ['debug' => false, 'logRetentionDays' => 30]);
        $this->assertTrue($logger->log('info', 'postback', "Получен\nлид", ['clickid' => 'abc']));
        $entry = json_decode((string)file_get_contents($this->root . '/logs/' . date('Y-m-d') . '.log'), true);
        $this->assertSame('info', $entry['level']);
        $this->assertSame('postback', $entry['source']);
        $this->assertSame("Получен\nлид", $entry['message']);
        $this->assertSame('abc', $entry['context']['clickid']);
    }

    public function testTraceIsSkippedOutsideDebugMode(): void
    {
        $logger = new YellowTdsLogger($this->root, ['debug' => false]);
        $this->assertFalse($logger->log('trace', 'database', 'hidden'));
        $this->assertDirectoryDoesNotExist($this->root . '/logs');

        $debugLogger = new YellowTdsLogger($this->root, ['debug' => true]);
        $this->assertTrue($debugLogger->log('trace', 'database', 'visible'));
    }

    public function testCleanupOnlyDeletesExpiredNewFormatFiles(): void
    {
        mkdir($this->root . '/logs/error', 0755, true);
        file_put_contents($this->root . '/logs/2020-01-01.log', "{}\n");
        file_put_contents($this->root . '/logs/error/01.01.20.log', 'legacy');
        file_put_contents($this->root . '/logs/not-a-log.txt', 'keep');
        $logger = new YellowTdsLogger($this->root, ['debug' => false, 'logRetentionDays' => 30]);
        $this->assertSame(1, $logger->cleanupIfDue(strtotime('2026-07-16')));
        $this->assertFileDoesNotExist($this->root . '/logs/2020-01-01.log');
        $this->assertFileExists($this->root . '/logs/error/01.01.20.log');
        $this->assertFileExists($this->root . '/logs/not-a-log.txt');
    }

    public function testReaderFiltersPaginatesAndSkipsMalformedRecords(): void
    {
        mkdir($this->root . '/logs', 0755, true);
        $records = [
            ['timestamp' => '2026-07-16T10:00:00+04:00', 'level' => 'info', 'source' => 'postback', 'message' => 'first click'],
            ['timestamp' => '2026-07-16T11:00:00+04:00', 'level' => 'error', 'source' => 'database', 'message' => 'broken click', 'context' => ['clickid' => 'xyz']],
            ['timestamp' => '2026-07-16T12:00:00+04:00', 'level' => 'warning', 'source' => 'postback', 'message' => 'latest click'],
        ];
        $lines = array_map('json_encode', $records);
        file_put_contents($this->root . '/logs/2026-07-16.log', implode("\n", $lines) . "\nnot-json\n");
        $reader = new YellowTdsLogReader($this->root);

        $page = $reader->query('2026-07-16', '2026-07-16', ['info', 'warning', 'error'], ['postback'], 'click', null, 1);
        $this->assertSame('latest click', $page['entries'][0]['message']);
        $this->assertNotNull($page['nextCursor']);
        $this->assertSame(1, $page['malformed']);

        $next = $reader->query('2026-07-16', '2026-07-16', ['info', 'warning', 'error'], ['postback'], 'click', $page['nextCursor'], 1);
        $this->assertSame('first click', $next['entries'][0]['message']);
    }

    public function testReaderRejectsUnsafeRangesAndCursors(): void
    {
        $reader = new YellowTdsLogReader($this->root);
        $this->expectException(InvalidArgumentException::class);
        $reader->query('../etc', '2026-07-16', ['error']);
    }

    private function remove(string $path): void
    {
        if (!file_exists($path)) return;
        if (!is_dir($path)) { @unlink($path); return; }
        foreach (array_diff(scandir($path), ['.', '..']) as $item) {
            $this->remove($path . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($path);
    }
}
