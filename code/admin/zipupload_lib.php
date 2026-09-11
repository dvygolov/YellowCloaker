<?php

function zip_upload_normalize_entry_name(string $name): ?string
{
    $name = str_replace('\\', '/', $name);
    if ($name === '' || str_contains($name, "\0")) {
        return null;
    }
    if (str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name) === 1) {
        return null;
    }

    $isDir = str_ends_with($name, '/');
    $name = trim($name, '/');
    if ($name === '') {
        return null;
    }

    $parts = explode('/', $name);
    foreach ($parts as $part) {
        if ($part === '' || $part === '.' || $part === '..') {
            return null;
        }
    }

    return $name . ($isDir ? '/' : '');
}

function zip_upload_should_skip_entry(string $name): bool
{
    return str_starts_with($name, '__MACOSX/') || str_starts_with($name, '.');
}

/**
 * @return array<int, array{index: int, name: string, isDir: bool}>
 */
function zip_upload_collect_entries(ZipArchive $zip): array
{
    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $rawName = (string)$zip->getNameIndex($i);
        $normalized = zip_upload_normalize_entry_name($rawName);
        if ($normalized === null || zip_upload_should_skip_entry($normalized)) {
            continue;
        }

        $entries[] = [
            'index' => $i,
            'name' => $normalized,
            'isDir' => str_ends_with($normalized, '/'),
        ];
    }
    return $entries;
}

/**
 * @param array<int, array{index: int, name: string, isDir: bool}> $entries
 * @return array<string, mixed>
 */
function zip_upload_analyze_entries(array $entries): array
{
    $rootFiles = [];
    $rootDirs = [];
    foreach ($entries as $entry) {
        $name = rtrim($entry['name'], '/');
        $parts = explode('/', $name);
        if (count($parts) === 1 && !$entry['isDir']) {
            $rootFiles[] = $name;
        } elseif (count($parts) >= 2) {
            $rootDirs[$parts[0]] = true;
        }
    }

    foreach (['index.php', 'index.html', 'index.htm'] as $indexName) {
        if (in_array($indexName, $rootFiles, true)) {
            return ['mode' => 'direct', 'entries' => $entries];
        }
    }

    $rootDirs = array_keys($rootDirs);
    if (count($rootDirs) !== 1 || count($rootFiles) !== 0) {
        return ['error' => 'ZIP invalid: no index.php, index.html, or index.htm found at root level'];
    }

    $singleDirName = $rootDirs[0];
    $prefix = $singleDirName . '/';
    $innerRootFiles = [];
    foreach ($entries as $entry) {
        if (!str_starts_with($entry['name'], $prefix)) {
            continue;
        }
        $relativePath = substr($entry['name'], strlen($prefix));
        $relativePath = rtrim((string)$relativePath, '/');
        if ($relativePath === '' || str_contains($relativePath, '/')) {
            continue;
        }
        if (!$entry['isDir']) {
            $innerRootFiles[] = $relativePath;
        }
    }

    foreach (['index.php', 'index.html', 'index.htm'] as $indexName) {
        if (in_array($indexName, $innerRootFiles, true)) {
            return ['mode' => 'single_dir', 'prefix' => $prefix, 'entries' => $entries];
        }
    }

    return ['error' => 'ZIP invalid: the folder "' . $singleDirName . '" does not contain index.php, index.html, or index.htm'];
}

/**
 * @return array<string, mixed>
 */
function zip_upload_analyze_archive(ZipArchive $zip): array
{
    return zip_upload_analyze_entries(zip_upload_collect_entries($zip));
}

/**
 * @param array<string, mixed> $plan
 */
function zip_upload_extract_plan(ZipArchive $zip, array $plan, string $targetDir): void
{
    $prefix = (string)($plan['prefix'] ?? '');
    $entries = is_array($plan['entries'] ?? null) ? $plan['entries'] : [];

    foreach ($entries as $entry) {
        if (!is_array($entry) || !isset($entry['index'], $entry['name'], $entry['isDir'])) {
            continue;
        }
        $relativePath = (string)$entry['name'];
        if ($prefix !== '') {
            if (!str_starts_with($relativePath, $prefix)) {
                continue;
            }
            $relativePath = substr($relativePath, strlen($prefix));
            if ($relativePath === '' || $relativePath === false) {
                continue;
            }
        }

        $destPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (($entry['isDir'] ?? false) === true) {
            if (!is_dir($destPath) && !@mkdir($destPath, 0755, true)) {
                throw new RuntimeException('Failed to create directory "' . $relativePath . '"');
            }
            continue;
        }

        $dir = dirname($destPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new RuntimeException('Failed to create directory for "' . $relativePath . '"');
        }

        $content = $zip->getFromIndex((int)$entry['index']);
        if ($content === false || @file_put_contents($destPath, $content) === false) {
            throw new RuntimeException('Failed to write file "' . $relativePath . '"');
        }
    }
}

function zip_upload_remove_directory(string $dir): void
{
    if (!file_exists($dir)) {
        return;
    }
    if (!is_dir($dir) || is_link($dir)) {
        @unlink($dir);
        return;
    }
    foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
        zip_upload_remove_directory($dir . DIRECTORY_SEPARATOR . $item);
    }
    @rmdir($dir);
}
