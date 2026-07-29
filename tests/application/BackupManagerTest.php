<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/backupmanager.php';
require_once __DIR__ . '/../../code/admin/backups.php';

class BackupManagerTest extends TestCase
{
    private string $root;
    private array $settings;

    protected function setUp(): void
    {
        if (!class_exists('ZipArchive') || !class_exists('SQLite3')) {
            $this->markTestSkipped('ZIP and SQLite3 extensions are required');
        }

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yellowtds_backup_' . bin2hex(random_bytes(5));
        mkdir($this->root . '/admin', 0755, true);
        mkdir($this->root . '/db', 0755, true);
        mkdir($this->root . '/caching/landings', 0755, true);
        foreach (['autoupdate.php', 'index.php', 'login.php'] as $file) {
            file_put_contents($this->root . '/admin/' . $file, '<?php // ' . $file);
        }
        file_put_contents($this->root . '/admin/version.txt', '15.07.26');
        file_put_contents($this->root . '/settings.php', '<?php // settings');
        file_put_contents($this->root . '/settings.local.php', '<?php return ["marker" => "before"];');
        file_put_contents($this->root . '/app.txt', 'before');
        file_put_contents($this->root . '/caching/landings/page.html', 'landing-before');

        $database = new SQLite3($this->root . '/db/clicks.db');
        $database->exec('CREATE TABLE markers (value TEXT)');
        $database->exec("INSERT INTO markers VALUES ('before')");
        $database->close();

        $this->settings = SettingsManager::defaults();
        $this->settings['adminPath'] = 'admin';
        $this->settings['backupDir'] = 'restore-points';
    }

    protected function tearDown(): void
    {
        if (isset($this->root)) {
            $this->remove($this->root);
        }
    }

    public function testBackupRestoresFilesSettingsDatabaseAndCreatesSafetyBackup(): void
    {
        $manager = new BackupManager($this->root, $this->settings);
        $backup = $manager->create('pre_update', ['fromVersion' => '15.07.26']);

        file_put_contents($this->root . '/app.txt', 'after');
        file_put_contents($this->root . '/settings.local.php', '<?php return ["marker" => "after"];');
        file_put_contents($this->root . '/caching/landings/page.html', 'landing-after');
        file_put_contents($this->root . '/new-from-update.txt', 'remove me');
        $database = new SQLite3($this->root . '/db/clicks.db');
        $database->exec("UPDATE markers SET value = 'after'");
        $database->close();

        $result = $manager->restore((string)$backup['id']);

        $this->assertSame('before', file_get_contents($this->root . '/app.txt'));
        $this->assertStringContainsString('before', (string)file_get_contents($this->root . '/settings.local.php'));
        $this->assertSame('landing-before', file_get_contents($this->root . '/caching/landings/page.html'));
        $this->assertFileDoesNotExist($this->root . '/new-from-update.txt');
        $database = new SQLite3($this->root . '/db/clicks.db', SQLITE3_OPEN_READONLY);
        $this->assertSame('before', $database->querySingle('SELECT value FROM markers'));
        $database->close();
        $this->assertSame('pre_restore', $result['safetyBackup']['type']);
        $this->assertSame(BackupManager::MODE_FULL, $result['safetyBackup']['mode']);
        $this->assertTrue($result['backup']['includesDatabase']);
        $this->assertCount(2, $manager->list());
    }

    public function testQuickBackupRestoresFilesButPreservesCurrentDatabase(): void
    {
        $manager = new BackupManager($this->root, $this->settings);
        $backup = $manager->create('manual', [], BackupManager::MODE_QUICK);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($this->root . '/restore-points/' . $backup['id']) === true);
        $this->assertFalse($zip->locateName('snapshot/db/clicks.db'));
        $this->assertNotFalse($zip->locateName('snapshot/caching/landings/page.html'));
        $zip->close();

        file_put_contents($this->root . '/app.txt', 'after');
        file_put_contents($this->root . '/caching/landings/page.html', 'landing-after');
        $database = new SQLite3($this->root . '/db/clicks.db');
        $database->exec("UPDATE markers SET value = 'after'");
        $database->close();

        $result = $manager->restore((string)$backup['id']);

        $this->assertSame('before', file_get_contents($this->root . '/app.txt'));
        $this->assertSame('landing-before', file_get_contents($this->root . '/caching/landings/page.html'));
        $database = new SQLite3($this->root . '/db/clicks.db', SQLITE3_OPEN_READONLY);
        $this->assertSame('after', $database->querySingle('SELECT value FROM markers'));
        $database->close();
        $this->assertFalse($result['backup']['includesDatabase']);
        $this->assertSame(BackupManager::MODE_QUICK, $result['safetyBackup']['mode']);
        $this->assertFalse($result['safetyBackup']['includesDatabase']);
    }

    public function testQuickRestorePreservesCurrentDatabaseUnderTheRestoredDatabaseName(): void
    {
        $original = new BackupManager($this->root, $this->settings);
        $backup = $original->create('manual', [], BackupManager::MODE_QUICK);
        rename($this->root . '/db/clicks.db', $this->root . '/db/current.db');
        $currentSettings = $this->settings;
        $currentSettings['dbConnection'] = 'current.db';
        $database = new SQLite3($this->root . '/db/current.db');
        $database->exec("UPDATE markers SET value = 'current-content'");
        $database->close();

        $manager = new BackupManager($this->root, $currentSettings);
        $manager->restore((string)$backup['id']);

        $this->assertFileExists($this->root . '/db/clicks.db');
        $this->assertFileDoesNotExist($this->root . '/db/current.db');
        $database = new SQLite3($this->root . '/db/clicks.db', SQLITE3_OPEN_READONLY);
        $this->assertSame('current-content', $database->querySingle('SELECT value FROM markers'));
        $database->close();
    }

    public function testRetentionKeepsOnlyNewestFiveAndDeleteRemovesSelectedBackup(): void
    {
        $manager = new BackupManager($this->root, $this->settings);
        foreach ([BackupManager::MODE_FULL, BackupManager::MODE_QUICK] as $mode) {
            for ($index = 0; $index < 7; $index++) {
                $manager->create('manual', ['sequence' => $index], $mode);
            }
        }

        $backups = $manager->list();
        $this->assertCount(BackupManager::MAX_BACKUPS_PER_MODE * 2, $backups);
        $this->assertCount(
            BackupManager::MAX_BACKUPS_PER_MODE,
            array_filter($backups, static fn(array $backup): bool => $backup['mode'] === BackupManager::MODE_FULL),
        );
        $this->assertCount(
            BackupManager::MAX_BACKUPS_PER_MODE,
            array_filter($backups, static fn(array $backup): bool => $backup['mode'] === BackupManager::MODE_QUICK),
        );
        $manager->delete((string)$backups[0]['id']);
        $this->assertCount((BackupManager::MAX_BACKUPS_PER_MODE * 2) - 1, $manager->list());
    }

    public function testCreateEndpointActionCreatesManualBackupWithoutAnId(): void
    {
        $manager = new BackupManager($this->root, $this->settings);

        $result = backups_execute_action($manager, 'create', '', BackupManager::MODE_QUICK, 'manual-operation-0001');

        $this->assertTrue($result['success']);
        $this->assertSame('Backup created successfully.', $result['message']);
        $this->assertSame('manual', $result['backup']['type']);
        $this->assertSame(BackupManager::MODE_QUICK, $result['backup']['mode']);
        $this->assertFalse($result['backup']['includesDatabase']);
        $this->assertTrue($result['backup']['valid']);
        $this->assertCount(1, $manager->list());
        $this->assertSame('completed', $manager->operationStatus('manual-operation-0001')['status']);
    }

    public function testRunningOperationWithoutALockIsReportedAsInterrupted(): void
    {
        mkdir($this->root . '/tmp', 0755, true);
        $operationId = 'interrupted-operation-0001';
        file_put_contents($this->root . '/tmp/backup-operation.json', json_encode([
            'id' => $operationId,
            'action' => 'create',
            'mode' => BackupManager::MODE_FULL,
            'status' => 'running',
            'stage' => 'finalizing',
            'message' => 'Finalizing the backup archive.',
            'startedAt' => time() - 600,
            'updatedAt' => time() - 600,
        ], JSON_THROW_ON_ERROR));

        $manager = new BackupManager($this->root, $this->settings);
        $operation = $manager->operationStatus($operationId);

        $this->assertSame('failed', $operation['status']);
        $this->assertStringContainsString('interrupted', $operation['error']);
    }

    public function testSchemaOneBackupIsUnsupported(): void
    {
        $manager = new BackupManager($this->root, $this->settings);
        $backup = $manager->create('manual', [], BackupManager::MODE_FULL);
        $archivePath = $this->root . '/restore-points/' . $backup['id'];
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($archivePath) === true);
        $manifest = json_decode((string)$zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['schema'] = 1;
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $zip->close();

        $listed = $manager->list();

        $this->assertCount(1, $listed);
        $this->assertFalse($listed[0]['valid']);
        $this->assertSame('unsupported', $listed[0]['mode']);
    }

    public function testExistingReadOnlyLockFileDoesNotBreakBackupListing(): void
    {
        mkdir($this->root . '/tmp', 0755, true);
        $lockPath = $this->root . '/tmp/backups.lock';
        file_put_contents($lockPath, '');
        chmod($lockPath, 0444);

        try {
            $manager = new BackupManager($this->root, $this->settings);
            $this->assertSame([], $manager->list());
        } finally {
            chmod($lockPath, 0666);
        }
    }

    public function testRestoreReturnsBackupStorageToTheSnapshottedName(): void
    {
        $original = new BackupManager($this->root, $this->settings);
        $backup = $original->create('pre_update');
        rename($this->root . '/restore-points', $this->root . '/archive-vault');
        $renamedSettings = $this->settings;
        $renamedSettings['backupDir'] = 'archive-vault';

        $renamed = new BackupManager($this->root, $renamedSettings);
        $renamed->restore((string)$backup['id'], false);

        $this->assertDirectoryExists($this->root . '/restore-points');
        $this->assertDirectoryDoesNotExist($this->root . '/archive-vault');
        $this->assertFileExists($this->root . '/restore-points/' . $backup['id']);
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
