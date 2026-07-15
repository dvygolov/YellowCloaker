<?php

final class SystemStatus
{
    public const CACHE_TTL = 60;

    public function __construct(
        private readonly string $root,
        private readonly array $settings,
        private readonly int $cacheTtl = self::CACHE_TTL
    ) {
    }

    /** @return array<string, mixed> */
    public function get(bool $refresh = false): array
    {
        if (!$refresh) {
            $cached = $this->readCache();
            if ($cached !== null) {
                return $cached;
            }
        }

        $status = $this->collect();
        $this->writeCache($status);
        return $status;
    }

    /** @return array<string, mixed> */
    public function collect(): array
    {
        $freeBytes = @disk_free_space($this->root);
        $totalBytes = @disk_total_space($this->root);
        $freeBytes = is_float($freeBytes) || is_int($freeBytes) ? (int)$freeBytes : null;
        $totalBytes = is_float($totalBytes) || is_int($totalBytes) ? (int)$totalBytes : null;
        $freePercent = $freeBytes !== null && $totalBytes !== null && $totalBytes > 0
            ? round(($freeBytes / $totalBytes) * 100, 1)
            : null;

        $databasePath = $this->rootPath('db/' . (string)($this->settings['dbConnection'] ?? 'clicks.db'));
        $cachePath = $this->rootPath((string)($this->settings['cachingDir'] ?? 'caching'));
        $logsPath = $this->rootPath('logs');

        return [
            'disk' => [
                'freeBytes' => $freeBytes,
                'totalBytes' => $totalBytes,
                'freePercent' => $freePercent,
                'level' => $this->diskLevel($freePercent),
            ],
            'database' => $this->databaseStatus($databasePath),
            'cache' => $this->directoryStatus($cachePath),
            'logs' => $this->directoryStatus($logsPath),
            'generatedAt' => time(),
        ];
    }

    /** @return array{bytes: int|null, available: bool} */
    private function databaseStatus(string $path): array
    {
        if (!is_file($path)) {
            return ['bytes' => null, 'available' => false];
        }

        $bytes = 0;
        foreach ([$path, $path . '-wal', $path . '-shm'] as $databaseFile) {
            if (!is_file($databaseFile)) {
                continue;
            }
            $size = @filesize($databaseFile);
            if ($size !== false) {
                $bytes += $size;
            }
        }

        return ['bytes' => $bytes, 'available' => true];
    }

    /** @return array{bytes: int|null, available: bool} */
    private function directoryStatus(string $path): array
    {
        if (!is_dir($path)) {
            return ['bytes' => null, 'available' => false];
        }

        $bytes = 0;
        try {
            $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator(
                $directory,
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($files as $file) {
                if ($file->isLink() || !$file->isFile()) {
                    continue;
                }
                try {
                    $bytes += $file->getSize();
                } catch (Throwable) {
                    // A disappearing or unreadable file should not hide the rest of the status.
                }
            }
        } catch (Throwable) {
            return ['bytes' => null, 'available' => false];
        }

        return ['bytes' => $bytes, 'available' => true];
    }

    private function diskLevel(?float $freePercent): string
    {
        if ($freePercent === null) {
            return 'unavailable';
        }
        if ($freePercent < 5) {
            return 'critical';
        }
        if ($freePercent < 15) {
            return 'warning';
        }
        return 'normal';
    }

    /** @return array<string, mixed>|null */
    private function readCache(): ?array
    {
        $path = $this->cachePath();
        $modifiedAt = @filemtime($path);
        if ($modifiedAt === false || time() - $modifiedAt >= $this->cacheTtl) {
            return null;
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }
        try {
            if (!flock($handle, LOCK_SH)) {
                return null;
            }
            $json = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        $cached = json_decode((string)$json, true);
        return is_array($cached) && isset($cached['disk'], $cached['database'], $cached['cache'], $cached['logs'])
            ? $cached
            : null;
    }

    /** @param array<string, mixed> $status */
    private function writeCache(array $status): void
    {
        $path = $this->cachePath();
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return;
        }

        $handle = @fopen($path, 'c+b');
        if ($handle === false) {
            return;
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                return;
            }
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string)json_encode($status, JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function cachePath(): string
    {
        return $this->rootPath('tmp/system-status.json');
    }

    private function rootPath(string $relativePath): string
    {
        return rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));
    }
}
