<?php

use PHPUnit\Framework\TestCase;

if (!defined('YELLOWTDS_REBUILD_EVENTS_DEMO_LIBRARY_ONLY')) {
    define('YELLOWTDS_REBUILD_EVENTS_DEMO_LIBRARY_ONLY', true);
}
require_once __DIR__ . '/../load/rebuild_events_demo.php';

final class RebuildEventsDemoRollbackTest extends TestCase
{
    private string $root;
    private string $databaseRoot;
    private array $originalCloSettings;
    private bool $hadOriginalDb;

    protected function setUp(): void
    {
        $this->originalCloSettings = $GLOBALS['cloSettings'];
        $this->hadOriginalDb = array_key_exists('db', $GLOBALS);
        $this->root = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'yellowtds_events_rollback_'
            . bin2hex(random_bytes(5));
        self::assertTrue(mkdir($this->root, 0755, true));
        $this->databaseRoot = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'code'
            . DIRECTORY_SEPARATOR
            . 'db'
            . DIRECTORY_SEPARATOR
            . '.events-rollback-test-'
            . bin2hex(random_bytes(5));
        self::assertTrue(mkdir($this->databaseRoot, 0755, true));
    }

    protected function tearDown(): void
    {
        release_events_demo_database_connections();
        $GLOBALS['cloSettings'] = $this->originalCloSettings;
        if ($this->hadOriginalDb) {
            $GLOBALS['db'] = new Db();
        }
        $this->remove($this->root);
        $this->remove($this->databaseRoot);
    }

    public function testArtificialPostSwapFailureRestoresOldDatabaseAndCache(): void
    {
        $livePath = $this->root . DIRECTORY_SEPARATOR . 'clicks.db';
        $replacementPath = $this->root . DIRECTORY_SEPARATOR . 'replacement.db';
        $oldStagingPath = $this->root . DIRECTORY_SEPARATOR . 'old.db';
        $failedReplacementPath = $this->root . DIRECTORY_SEPARATOR . 'failed.db';
        $landingPath = $this->root . DIRECTORY_SEPARATOR . 'events-demo-landing';
        $cachePath = $this->root . DIRECTORY_SEPARATOR . 'runtime-cache.txt';

        $this->createMarkerDatabase($livePath, 'old');
        $this->createMarkerDatabase($replacementPath, 'new');
        self::assertTrue(mkdir($landingPath));
        file_put_contents($landingPath . DIRECTORY_SEPARATOR . 'index.html', 'demo');
        file_put_contents(
            $landingPath . DIRECTORY_SEPARATOR . '.index.html.tmp-test',
            'partial'
        );

        self::assertTrue(rename($livePath, $oldStagingPath));
        self::assertTrue(rename($replacementPath, $livePath));

        try {
            throw new RuntimeException('Artificial post-swap failure.');
        } catch (RuntimeException) {
            rollback_events_demo_swap(
                $livePath,
                $oldStagingPath,
                $failedReplacementPath,
                [$landingPath],
                function () use ($livePath, $cachePath): void {
                    file_put_contents($cachePath, $this->readMarker($livePath));
                }
            );
        }

        self::assertSame('old', $this->readMarker($livePath));
        self::assertSame('old', file_get_contents($cachePath));
        self::assertFileDoesNotExist($oldStagingPath);
        self::assertFileDoesNotExist($failedReplacementPath);
        self::assertDirectoryDoesNotExist($landingPath);

        $restored = new SQLite3($livePath, SQLITE3_OPEN_READONLY);
        try {
            self::assertSame('ok', sqlite_quick_check($restored, 'Test restored database'));
        } finally {
            $restored->close();
        }
    }

    public function testRollbackFailurePreservesBothDatabaseFilesAndReportsPaths(): void
    {
        $livePath = $this->root . DIRECTORY_SEPARATOR . 'clicks-failing.db';
        $replacementPath = $this->root . DIRECTORY_SEPARATOR . 'replacement-failing.db';
        $oldStagingPath = $this->root . DIRECTORY_SEPARATOR . 'old-failing.db';
        $failedReplacementPath = $this->root . DIRECTORY_SEPARATOR . 'failed-failing.db';

        $this->createMarkerDatabase($livePath, 'old');
        $this->createMarkerDatabase($replacementPath, 'new');
        self::assertTrue(rename($livePath, $oldStagingPath));
        self::assertTrue(rename($replacementPath, $livePath));

        try {
            rollback_events_demo_swap(
                $livePath,
                $oldStagingPath,
                $failedReplacementPath,
                [],
                static function (): void {
                    throw new RuntimeException('Artificial cache failure.');
                }
            );
            self::fail('Rollback cache failure was not reported.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString('Artificial cache failure.', $error->getMessage());
            self::assertStringContainsString($livePath, $error->getMessage());
            self::assertStringContainsString($failedReplacementPath, $error->getMessage());
        }

        self::assertSame('old', $this->readMarker($livePath));
        self::assertSame('new', $this->readMarker($failedReplacementPath));
    }

    public function testRollbackClosesStaleDbAndRebuildsCacheFromRestoredDatabase(): void
    {
        $livePath = $this->databaseRoot . DIRECTORY_SEPARATOR . 'clicks.db';
        $oldStagingPath = $this->databaseRoot . DIRECTORY_SEPARATOR . 'old.db';
        $failedReplacementPath = $this->databaseRoot . DIRECTORY_SEPARATOR . 'failed.db';
        $cachePath = $this->root . DIRECTORY_SEPARATOR . 'cache';
        self::assertTrue(mkdir($cachePath, 0755, true));

        $this->createCampaignDatabase($livePath, 'new.example');
        $this->createCampaignDatabase($oldStagingPath, 'old.example');
        $GLOBALS['cloSettings']['dbConnection'] = basename($this->databaseRoot) . '/clicks.db';
        $GLOBALS['cloSettings']['cachingDir'] = $cachePath;

        $GLOBALS['db'] = new Db();
        self::assertTrue($GLOBALS['db']->rebuild_runtime_cache());
        $newDomains = include $cachePath . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'domains.php';
        self::assertArrayHasKey('new.example', $newDomains['exact']);

        rollback_events_demo_swap(
            $livePath,
            $oldStagingPath,
            $failedReplacementPath,
            [],
            static function (): void {
                rebuild_events_demo_runtime_cache(
                    (string)realpath(dirname(__DIR__, 2) . '/code')
                );
            }
        );

        $restoredDomains = include $cachePath . DIRECTORY_SEPARATOR . 'runtime'
            . DIRECTORY_SEPARATOR . 'domains.php';
        self::assertArrayHasKey('old.example', $restoredDomains['exact']);
        self::assertArrayNotHasKey('new.example', $restoredDomains['exact']);
        self::assertSame('old.example', $this->readCampaignDomain($livePath));
    }

    public function testFailedPublishAndFailedRestorePreserveBothExactPaths(): void
    {
        $livePath = $this->root . DIRECTORY_SEPARATOR . 'clicks-partial.db';
        $replacementPath = $this->root . DIRECTORY_SEPARATOR . 'replacement-partial.db';
        $oldStagingPath = $this->root . DIRECTORY_SEPARATOR . 'old-partial.db';
        $this->createMarkerDatabase($livePath, 'old');
        $this->createMarkerDatabase($replacementPath, 'new');
        $renameAttempt = 0;

        try {
            publish_events_demo_database(
                $livePath,
                $replacementPath,
                $oldStagingPath,
                static function (string $source, string $destination) use (&$renameAttempt): bool {
                    $renameAttempt++;
                    return $renameAttempt === 1 ? rename($source, $destination) : false;
                }
            );
            self::fail('Partial-swap failure was not reported.');
        } catch (EventsDemoPartialSwapException $error) {
            self::assertStringContainsString($oldStagingPath, $error->getMessage());
            self::assertStringContainsString($replacementPath, $error->getMessage());
        }

        self::assertFileDoesNotExist($livePath);
        self::assertSame('old', $this->readMarker($oldStagingPath));
        self::assertSame('new', $this->readMarker($replacementPath));
    }

    public function testEarlyRollbackFailureStillRemovesCreatedLandings(): void
    {
        $livePath = $this->root . DIRECTORY_SEPARATOR . 'clicks-early-failure.db';
        $missingOldPath = $this->root . DIRECTORY_SEPARATOR . 'missing-old.db';
        $failedReplacementPath = $this->root . DIRECTORY_SEPARATOR . 'failed-early.db';
        $landingPath = $this->root . DIRECTORY_SEPARATOR . 'landing-early-failure';
        $this->createMarkerDatabase($livePath, 'new');
        self::assertTrue(mkdir($landingPath));
        file_put_contents($landingPath . DIRECTORY_SEPARATOR . 'index.html', 'demo');
        $cacheCalled = false;

        try {
            rollback_events_demo_swap(
                $livePath,
                $missingOldPath,
                $failedReplacementPath,
                [$landingPath],
                static function () use (&$cacheCalled): void {
                    $cacheCalled = true;
                }
            );
            self::fail('Early rollback failure was not reported.');
        } catch (RuntimeException $error) {
            self::assertStringContainsString(
                'Both the published and old staged databases are required.',
                $error->getMessage()
            );
        }

        self::assertDirectoryDoesNotExist($landingPath);
        self::assertSame('new', $this->readMarker($livePath));
        self::assertFalse($cacheCalled);
    }

    private function createMarkerDatabase(string $path, string $marker): void
    {
        $database = new SQLite3($path, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        try {
            self::assertTrue($database->exec('CREATE TABLE marker (value TEXT NOT NULL)'));
            $statement = $database->prepare('INSERT INTO marker (value) VALUES (:value)');
            $statement->bindValue(':value', $marker, SQLITE3_TEXT);
            self::assertNotFalse($statement->execute());
        } finally {
            $database->close();
        }
    }

    private function readMarker(string $path): string
    {
        $database = new SQLite3($path, SQLITE3_OPEN_READONLY);
        try {
            return (string)$database->querySingle('SELECT value FROM marker');
        } finally {
            $database->close();
        }
    }

    private function createCampaignDatabase(string $path, string $domain): void
    {
        $database = new SQLite3($path, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        try {
            $schema = file_get_contents(__DIR__ . '/../../code/db/db.sql');
            self::assertIsString($schema);
            self::assertTrue($database->exec($schema));
            self::assertTrue($database->exec("INSERT INTO common (settings) VALUES ('{}')"));
            $settings = json_encode([
                'domains' => [$domain],
                'white' => [
                    'domainfilter' => [
                        'use' => false,
                        'domains' => [],
                    ],
                ],
            ], JSON_THROW_ON_ERROR);
            $statement = $database->prepare(
                'INSERT INTO campaigns (name, settings) VALUES (:name, :settings)'
            );
            $statement->bindValue(':name', $domain, SQLITE3_TEXT);
            $statement->bindValue(':settings', $settings, SQLITE3_TEXT);
            self::assertNotFalse($statement->execute());
        } finally {
            $database->close();
        }
    }

    private function readCampaignDomain(string $path): string
    {
        $database = new SQLite3($path, SQLITE3_OPEN_READONLY);
        try {
            $settings = json_decode(
                (string)$database->querySingle('SELECT settings FROM campaigns LIMIT 1'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            return (string)$settings['domains'][0];
        } finally {
            $database->close();
        }
    }

    private function remove(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
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
