<?php

require_once __DIR__ . '/settings.php';

final class BackupManager
{
    public const MAX_BACKUPS = 5;
    private const SCHEMA_VERSION = 1;
    private const FILE_PREFIX = 'yellowtds_';
    private string $root;

    /** @var array<string, mixed> */
    private array $settings;

    /** @param array<string, mixed>|null $settings */
    public function __construct(string $root = __DIR__, ?array $settings = null)
    {
        $resolved = realpath($root);
        if ($resolved === false || !is_dir($resolved)) {
            throw new RuntimeException('Invalid YellowTDS root directory');
        }
        $this->root = rtrim($resolved, '/\\');
        $this->settings = $settings ?? (new SettingsManager($this->root))->load();
        $backupDir = (string)($this->settings['backupDir'] ?? 'backups');
        $this->assertSafeDirectoryName($backupDir);
        $reserved = ['admin', 'api', 'bases', 'caching', 'db', 'docs', 'js', 'logs', 'plugins', 'scripts', 'tests', 'tmp', 'ycclogs', 'temp_update'];
        $dynamicReserved = [(string)($this->settings['adminPath'] ?? 'admin'), (string)($this->settings['cachingDir'] ?? 'caching')];
        if (in_array(strtolower($backupDir), array_map('strtolower', array_merge($reserved, $dynamicReserved)), true)) {
            throw new RuntimeException('Backup directory overlaps a system directory');
        }
    }

    public function directory(): string
    {
        return $this->rootPath((string)($this->settings['backupDir'] ?? 'backups'));
    }

    /**
     * @param array<string, scalar|null> $metadata
     * @return array<string, mixed>
     */
    public function create(string $type = 'manual', array $metadata = []): array
    {
        return $this->withLock(fn(): array => $this->createUnlocked($type, $metadata));
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->withLock(fn(): array => $this->listUnlocked());
    }

    public function delete(string $id): void
    {
        $this->withLock(function () use ($id): void {
            $path = $this->backupPath($id);
            if (!is_file($path)) {
                throw new RuntimeException('Backup not found');
            }
            if (!@unlink($path)) {
                throw new RuntimeException('Failed to delete backup');
            }
        });
    }

    /** @return array{backup: array<string, mixed>, safetyBackup: ?array<string, mixed>, redirect: ?string} */
    public function restore(string $id, bool $createSafetyBackup = true): array
    {
        return $this->withLock(function () use ($id, $createSafetyBackup): array {
            @set_time_limit(0);
            $archivePath = $this->backupPath($id);
            if (!is_file($archivePath)) {
                throw new RuntimeException('Backup not found');
            }

            $manifest = $this->readManifest($archivePath);
            $archiveCopy = tempnam(sys_get_temp_dir(), 'yellowtds_restore_');
            if ($archiveCopy === false || !@copy($archivePath, $archiveCopy)) {
                throw new RuntimeException('Failed to prepare backup for restore');
            }

            $safetyBackup = null;
            try {
                if ($createSafetyBackup) {
                    $safetyBackup = $this->createUnlocked('pre_restore', ['sourceBackup' => $id], [$id]);
                }
                try {
                    $this->applyArchive($archiveCopy, $manifest);
                } catch (Throwable $restoreError) {
                    if (is_array($safetyBackup)) {
                        try {
                            $safetyPath = $this->backupPath((string)$safetyBackup['id']);
                            $this->applyArchive($safetyPath, $this->readManifest($safetyPath));
                        } catch (Throwable $rollbackError) {
                            throw new RuntimeException(
                                'Restore failed and the safety rollback also failed: ' . $rollbackError->getMessage(),
                                0,
                                $restoreError,
                            );
                        }
                        throw new RuntimeException('Restore failed. The current system state was recovered from the safety backup.', 0, $restoreError);
                    }
                    throw $restoreError;
                }
            } finally {
                @unlink($archiveCopy);
            }

            $restoredAdminPath = (string)($manifest['adminPath'] ?? 'admin');
            $redirect = $restoredAdminPath !== (string)($this->settings['adminPath'] ?? 'admin')
                ? '../' . rawurlencode($restoredAdminPath) . '/'
                : null;

            $this->settings['adminPath'] = $restoredAdminPath;
            $this->settings['backupDir'] = (string)($manifest['backupDir'] ?? 'backups');
            $restoredArchivePath = $this->backupPathAfterRestore($id, $manifest);

            return [
                'backup' => $this->publicBackupInfo($manifest, $id, is_file($restoredArchivePath) ? (filesize($restoredArchivePath) ?: 0) : 0),
                'safetyBackup' => $safetyBackup,
                'redirect' => $redirect,
            ];
        });
    }

    /**
     * @param array<string, scalar|null> $metadata
     * @param array<int, string> $protectedIds
     * @return array<string, mixed>
     */
    private function createUnlocked(string $type, array $metadata, array $protectedIds = []): array
    {
        @set_time_limit(0);
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZIP extension is required for backups');
        }

        $backupDir = $this->directory();
        $this->ensureBackupDirectory($backupDir);

        $id = self::FILE_PREFIX . date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(3)) . '.bak';
        $finalPath = $backupDir . DIRECTORY_SEPARATOR . $id;
        $tempPath = $backupDir . DIRECTORY_SEPARATOR . '.' . $id . '.creating';
        $databaseSnapshot = $this->createDatabaseSnapshot();
        $zip = new ZipArchive();
        $zipOpen = false;

        try {
            if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Failed to create backup archive');
            }
            $zipOpen = true;

            $manifest = [
                'schema' => self::SCHEMA_VERSION,
                'id' => $id,
                'createdAt' => date(DATE_ATOM),
                'timestamp' => time(),
                'type' => preg_replace('/[^A-Za-z0-9_-]/', '', $type) ?: 'manual',
                'version' => $this->currentVersion(),
                'adminPath' => (string)($this->settings['adminPath'] ?? 'admin'),
                'backupDir' => (string)($this->settings['backupDir'] ?? 'backups'),
                'metadata' => $metadata,
                'directories' => [],
                'files' => [],
            ];

            $dbRelative = 'db/' . (string)($this->settings['dbConnection'] ?? 'clicks.db');
            $this->addDirectoryToArchive($zip, $this->root, '', $manifest, $dbRelative, $databaseSnapshot);
            $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($json === false || !$zip->addFromString('manifest.json', $json)) {
                throw new RuntimeException('Failed to write backup manifest');
            }
            if (!$zip->close()) {
                throw new RuntimeException('Failed to finalize backup archive');
            }
            $zipOpen = false;

            if (!@rename($tempPath, $finalPath)) {
                throw new RuntimeException('Failed to publish backup archive');
            }
            @chmod($finalPath, 0640);
            $this->enforceRetention(array_merge($protectedIds, [$id]));

            return $this->publicBackupInfo($manifest, $id, filesize($finalPath) ?: 0);
        } catch (Throwable $e) {
            if ($zipOpen) {
                @$zip->close();
            }
            @unlink($tempPath);
            throw $e;
        } finally {
            if ($databaseSnapshot !== null) {
                @unlink($databaseSnapshot);
            }
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function addDirectoryToArchive(
        ZipArchive $zip,
        string $absoluteDirectory,
        string $relativeDirectory,
        array &$manifest,
        string $dbRelative,
        ?string $databaseSnapshot,
    ): void {
        $items = scandir($absoluteDirectory);
        if ($items === false) {
            throw new RuntimeException('Failed to read ' . $absoluteDirectory);
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $absolute = $absoluteDirectory . DIRECTORY_SEPARATOR . $item;
            $relative = ltrim(str_replace('\\', '/', $relativeDirectory === '' ? $item : $relativeDirectory . '/' . $item), '/');
            if ($this->shouldExclude($relative)) {
                continue;
            }
            if (is_link($absolute)) {
                throw new RuntimeException('Symbolic links are not supported in backups: ' . $relative);
            }

            if (is_dir($absolute)) {
                $manifest['directories'][] = $relative;
                if (!$zip->addEmptyDir('snapshot/' . $relative)) {
                    throw new RuntimeException('Failed to add directory to backup: ' . $relative);
                }
                $this->addDirectoryToArchive($zip, $absolute, $relative, $manifest, $dbRelative, $databaseSnapshot);
                continue;
            }
            if (!is_file($absolute)) {
                continue;
            }
            if ($relative === $dbRelative . '-wal' || $relative === $dbRelative . '-shm') {
                continue;
            }

            $source = ($relative === $dbRelative && $databaseSnapshot !== null) ? $databaseSnapshot : $absolute;
            $archiveName = 'snapshot/' . $relative;
            if (!$zip->addFile($source, $archiveName)) {
                throw new RuntimeException('Failed to add file to backup: ' . $relative);
            }
            $zip->setCompressionName($archiveName, ZipArchive::CM_DEFLATE);
            $manifest['files'][] = [
                'path' => $relative,
                'size' => filesize($source) ?: 0,
                'mtime' => filemtime($absolute) ?: time(),
                'mode' => fileperms($absolute) & 0777,
            ];
        }
    }

    private function createDatabaseSnapshot(): ?string
    {
        $database = $this->rootPath('db/' . (string)($this->settings['dbConnection'] ?? 'clicks.db'));
        if (!class_exists('SQLite3') || !is_file($database)) {
            return null;
        }
        $header = @file_get_contents($database, false, null, 0, 16);
        if ($header !== "SQLite format 3\0") {
            return null;
        }

        $temp = tempnam(sys_get_temp_dir(), 'yellowtds_db_');
        if ($temp === false) {
            throw new RuntimeException('Failed to allocate database snapshot');
        }
        $source = new SQLite3($database, SQLITE3_OPEN_READONLY);
        $destination = new SQLite3($temp, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        try {
            $source->busyTimeout(10000);
            if (!$source->backup($destination)) {
                throw new RuntimeException('Failed to snapshot SQLite database');
            }
        } finally {
            $destination->close();
            $source->close();
        }
        return $temp;
    }

    /** @return array<int, array<string, mixed>> */
    private function listUnlocked(): array
    {
        $directory = $this->directory();
        if (!is_dir($directory)) {
            return [];
        }
        $paths = glob($directory . DIRECTORY_SEPARATOR . self::FILE_PREFIX . '*.bak') ?: [];
        $backups = [];
        foreach ($paths as $path) {
            $id = basename($path);
            try {
                $manifest = $this->readManifest($path);
                $backups[] = $this->publicBackupInfo($manifest, $id, filesize($path) ?: 0);
            } catch (Throwable $e) {
                $backups[] = [
                    'id' => $id,
                    'createdAt' => date(DATE_ATOM, filemtime($path) ?: time()),
                    'timestamp' => filemtime($path) ?: 0,
                    'type' => 'unknown',
                    'version' => 'unknown',
                    'size' => filesize($path) ?: 0,
                    'valid' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }
        usort($backups, static fn(array $a, array $b): int => ((int)$b['timestamp']) <=> ((int)$a['timestamp']));
        return $backups;
    }

    /** @param array<int, string> $protectedIds */
    private function enforceRetention(array $protectedIds = []): void
    {
        $backups = $this->listUnlocked();
        if (count($backups) <= self::MAX_BACKUPS) {
            return;
        }
        $protected = array_fill_keys($protectedIds, true);
        $remaining = count($backups);
        foreach (array_reverse($backups) as $backup) {
            if ($remaining <= self::MAX_BACKUPS) {
                break;
            }
            $id = (string)$backup['id'];
            if (isset($protected[$id])) {
                continue;
            }
            if (@unlink($this->backupPath($id))) {
                $remaining--;
            }
        }
        if ($remaining > self::MAX_BACKUPS) {
            throw new RuntimeException('Failed to enforce backup retention limit');
        }
    }

    /** @return array<string, mixed> */
    private function readManifest(string $archivePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Invalid backup archive');
        }
        try {
            $json = $zip->getFromName('manifest.json');
            $manifest = is_string($json) ? json_decode($json, true) : null;
            if (!is_array($manifest) || (int)($manifest['schema'] ?? 0) !== self::SCHEMA_VERSION) {
                throw new RuntimeException('Invalid backup manifest');
            }
            $this->assertSafeDirectoryName((string)($manifest['backupDir'] ?? ''));
            $this->assertSafeDirectoryName((string)($manifest['adminPath'] ?? ''));
            if (!is_array($manifest['files'] ?? null) || !is_array($manifest['directories'] ?? null)) {
                throw new RuntimeException('Incomplete backup manifest');
            }
            return $manifest;
        } finally {
            $zip->close();
        }
    }

    /** @param array<string, mixed> $manifest */
    private function applyArchive(string $archivePath, array $manifest): void
    {
        $currentBackupDir = (string)($this->settings['backupDir'] ?? 'backups');
        $restoredBackupDir = (string)$manifest['backupDir'];
        $currentBackupPath = $this->rootPath($currentBackupDir);
        $restoredBackupPath = $this->rootPath($restoredBackupDir);
        if ($currentBackupPath !== $restoredBackupPath && file_exists($restoredBackupPath)) {
            throw new RuntimeException('Cannot restore because the target backup directory already exists');
        }

        $stage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yellowtds_restore_' . bin2hex(random_bytes(6));
        if (!@mkdir($stage, 0755, true)) {
            throw new RuntimeException('Failed to create restore staging directory');
        }

        $zip = new ZipArchive();
        $zipOpen = false;
        try {
            if ($zip->open($archivePath) !== true) {
                throw new RuntimeException('Invalid backup archive');
            }
            $zipOpen = true;
            $this->extractSnapshot($zip, $stage);
            $zip->close();
            $zipOpen = false;

            $adminPath = (string)$manifest['adminPath'];
            foreach (['settings.php', $adminPath . '/autoupdate.php', $adminPath . '/index.php', $adminPath . '/login.php'] as $required) {
                if (!is_file($stage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $required))) {
                    throw new RuntimeException('Backup is incomplete: missing ' . $required);
                }
            }

            $directories = [];
            foreach ($manifest['directories'] as $relative) {
                $relative = $this->normalizeRelativePath((string)$relative);
                $directories[$relative] = true;
                $target = $this->rootPath($relative);
                if (!is_dir($target) && !@mkdir($target, 0755, true)) {
                    throw new RuntimeException('Failed to restore directory: ' . $relative);
                }
            }

            $files = [];
            foreach ($manifest['files'] as $file) {
                if (!is_array($file)) {
                    throw new RuntimeException('Invalid backup file entry');
                }
                $relative = $this->normalizeRelativePath((string)($file['path'] ?? ''));
                $source = $stage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (!is_file($source)) {
                    throw new RuntimeException('Backup file is missing: ' . $relative);
                }
                $target = $this->rootPath($relative);
                $parent = dirname($target);
                if (!is_dir($parent) && !@mkdir($parent, 0755, true)) {
                    throw new RuntimeException('Failed to create restore directory: ' . dirname($relative));
                }
                if (!@copy($source, $target)) {
                    throw new RuntimeException('Failed to restore file: ' . $relative);
                }
                @chmod($target, (int)($file['mode'] ?? 0644));
                @touch($target, (int)($file['mtime'] ?? time()));
                $files[$relative] = true;
            }

            $this->removeUnexpectedFiles($this->root, '', $files, $directories, $currentBackupDir);

            if ($currentBackupPath !== $restoredBackupPath) {
                if (!@rename($currentBackupPath, $restoredBackupPath)) {
                    throw new RuntimeException('Failed to restore backup directory name');
                }
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } finally {
            if ($zipOpen) {
                @$zip->close();
            }
            $this->recursiveDelete($stage);
        }
    }

    private function extractSnapshot(ZipArchive $zip, string $stage): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (!is_string($name) || $name === 'manifest.json') {
                continue;
            }
            if (!str_starts_with($name, 'snapshot/')) {
                throw new RuntimeException('Unexpected file in backup archive');
            }
            $relative = rtrim(substr($name, strlen('snapshot/')), '/');
            if ($relative === '') {
                continue;
            }
            $relative = $this->normalizeRelativePath($relative);
            $target = $stage . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (str_ends_with($name, '/')) {
                if (!is_dir($target) && !@mkdir($target, 0755, true)) {
                    throw new RuntimeException('Failed to extract backup directory');
                }
                continue;
            }
            $parent = dirname($target);
            if (!is_dir($parent) && !@mkdir($parent, 0755, true)) {
                throw new RuntimeException('Failed to extract backup directory');
            }
            $source = $zip->getStream($name);
            $destination = fopen($target, 'wb');
            if ($source === false || $destination === false) {
                if (is_resource($source)) fclose($source);
                if (is_resource($destination)) fclose($destination);
                throw new RuntimeException('Failed to extract backup file');
            }
            stream_copy_to_stream($source, $destination);
            fclose($source);
            fclose($destination);
        }
    }

    /**
     * @param array<string, bool> $expectedFiles
     * @param array<string, bool> $expectedDirectories
     */
    private function removeUnexpectedFiles(
        string $absoluteDirectory,
        string $relativeDirectory,
        array $expectedFiles,
        array $expectedDirectories,
        string $backupDir,
    ): void {
        $items = scandir($absoluteDirectory);
        if ($items === false) {
            throw new RuntimeException('Failed to inspect current installation during restore');
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $absolute = $absoluteDirectory . DIRECTORY_SEPARATOR . $item;
            $relative = ltrim(str_replace('\\', '/', $relativeDirectory === '' ? $item : $relativeDirectory . '/' . $item), '/');
            if ($this->shouldPreserveDuringRestore($relative, $backupDir)) {
                continue;
            }
            if (is_dir($absolute) && !is_link($absolute)) {
                $this->removeUnexpectedFiles($absolute, $relative, $expectedFiles, $expectedDirectories, $backupDir);
                if (!isset($expectedDirectories[$relative]) && $this->isDirectoryEmpty($absolute)) {
                    @rmdir($absolute);
                }
            } elseif (!isset($expectedFiles[$relative])) {
                @unlink($absolute);
            }
        }
    }

    private function shouldExclude(string $relative): bool
    {
        $first = explode('/', $relative, 2)[0];
        $excluded = [
            (string)($this->settings['backupDir'] ?? 'backups'),
            'backups',
            'temp_update',
            'logs',
            'ycclogs',
            'tmp',
            '.git',
            '.playwright-cli',
        ];
        return in_array($first, array_unique($excluded), true);
    }

    private function shouldPreserveDuringRestore(string $relative, string $backupDir): bool
    {
        $first = explode('/', $relative, 2)[0];
        return in_array($first, array_unique([$backupDir, 'backups', 'temp_update', 'logs', 'ycclogs', 'tmp', '.git', '.playwright-cli']), true);
    }

    private function currentVersion(): string
    {
        $adminPath = (string)($this->settings['adminPath'] ?? 'admin');
        $version = @file_get_contents($this->rootPath($adminPath . '/version.txt'));
        return trim((string)$version) ?: 'unknown';
    }

    /** @param array<string, mixed> $manifest @return array<string, mixed> */
    private function publicBackupInfo(array $manifest, string $id, int $size): array
    {
        return [
            'id' => $id,
            'createdAt' => (string)($manifest['createdAt'] ?? ''),
            'timestamp' => (int)($manifest['timestamp'] ?? 0),
            'type' => (string)($manifest['type'] ?? 'unknown'),
            'version' => (string)($manifest['version'] ?? 'unknown'),
            'size' => $size,
            'valid' => true,
            'error' => '',
        ];
    }

    /** @param array<string, mixed> $manifest */
    private function backupPathAfterRestore(string $id, array $manifest): string
    {
        return $this->rootPath((string)$manifest['backupDir']) . DIRECTORY_SEPARATOR . $id;
    }

    private function backupPath(string $id): string
    {
        if (preg_match('/^' . self::FILE_PREFIX . '[A-Za-z0-9_-]+\.bak$/', $id) !== 1 || basename($id) !== $id) {
            throw new RuntimeException('Invalid backup id');
        }
        return $this->directory() . DIRECTORY_SEPARATOR . $id;
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, "\0")) {
            throw new RuntimeException('Invalid path in backup');
        }
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..') {
                throw new RuntimeException('Unsafe path in backup');
            }
        }
        return $path;
    }

    private function assertSafeDirectoryName(string $name): void
    {
        if ($name === '' || strlen($name) > 64 || preg_match('/^[A-Za-z0-9._-]+$/', $name) !== 1 || $name === '.' || $name === '..') {
            throw new RuntimeException('Invalid backup directory name');
        }
    }

    private function ensureBackupDirectory(string $directory): void
    {
        if (!is_dir($directory) && !@mkdir($directory, 0755, true)) {
            throw new RuntimeException('Failed to create backup directory');
        }
        $protections = [
            '.htaccess' => "Require all denied\nDeny from all\n",
            'index.php' => "<?php\nhttp_response_code(404);\nexit;\n",
        ];
        foreach ($protections as $file => $content) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (!is_file($path) && @file_put_contents($path, $content, LOCK_EX) === false) {
                throw new RuntimeException('Failed to protect backup directory');
            }
        }
    }

    private function isDirectoryEmpty(string $directory): bool
    {
        $items = scandir($directory);
        return $items !== false && count($items) === 2;
    }

    private function recursiveDelete(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        if (!is_dir($path) || is_link($path)) {
            @unlink($path);
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $item) {
            $this->recursiveDelete($path . DIRECTORY_SEPARATOR . $item);
        }
        @rmdir($path);
    }

    private function rootPath(string $relative): string
    {
        return $this->root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
    }

    private function withLock(callable $callback): mixed
    {
        $tmp = $this->rootPath('tmp');
        if (!is_dir($tmp) && !@mkdir($tmp, 0755, true)) {
            throw new RuntimeException('Failed to create backup lock directory');
        }
        $lockPath = $tmp . DIRECTORY_SEPARATOR . 'backups.lock';
        $lock = @fopen($lockPath, 'c+');
        if ($lock === false && is_file($lockPath)) {
            // Deployments and maintenance checks can run as root while the web
            // process runs as another user. A readable descriptor is sufficient
            // for flock() on Unix and avoids breaking the UI on that owner change.
            $lock = @fopen($lockPath, 'r');
        }
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            throw new RuntimeException('Failed to lock backups');
        }
        @chmod($lockPath, 0666);
        try {
            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
