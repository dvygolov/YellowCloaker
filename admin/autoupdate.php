<?php
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../backupmanager.php';
require_once __DIR__ . '/../paths.php';
require_once __DIR__ . '/../requestfunc.php';
require_once __DIR__ . '/password.php';

class AutoUpdater {
    private const GITHUB_REPO = 'dvygolov/YellowTDS';
    private const GITHUB_BRANCH = 'multipleconfigs';
    private const GITHUB_API_URL = 'https://api.github.com/repos/dvygolov/YellowTDS/contents/admin/version.txt?ref=multipleconfigs';
    private const VERSION_FILE = __DIR__ . '/version.txt';
    private const UPDATE_DIR = __DIR__ . '/../temp_update';

    private string $currentVersion;
    private string $latestVersion = '';
    private string $downloadUrl = '';
    /** @var array<int, string> */
    private array $protectedRootDirectories = ['backups', 'temp_update', 'logs', 'ycclogs', 'tmp'];

    public function __construct() {
        $this->currentVersion = trim((string)file_get_contents(self::VERSION_FILE));
    }

    public function checkForUpdates(): bool {
        try {
            $httpResponse = HttpClient::send(new HttpRequest(
                id: 'autoupdate-check',
                url: self::GITHUB_API_URL,
                headers: ['Accept: application/vnd.github.v3+json'],
                timeout: 20,
                connectTimeout: 5,
                followRedirects: true,
                verifyPeer: true,
                verifyHost: 2,
                userAgent: 'YellowTDS Updater',
            ));
            if (!$httpResponse->isOk()) {
                throw new Exception("Failed to fetch version information: HTTP {$httpResponse->httpCode()} {$httpResponse->error}");
            }
            $response = (string)$httpResponse->content;

            $fileInfo = json_decode($response, true);
            if (!$fileInfo || !isset($fileInfo['content'])) {
                throw new Exception("Invalid version file information");
            }

            $this->latestVersion = trim(base64_decode($fileInfo['content']));

            $latestTimestamp = $this->convertVersionToTimestamp($this->latestVersion);
            $currentTimestamp = $this->convertVersionToTimestamp($this->currentVersion);

            return $latestTimestamp > $currentTimestamp;
        } catch (Exception $e) {
            ytds_log('error', 'admin', $e->getMessage(), ['action' => 'update-check']);
            return false;
        }
    }

    private function convertVersionToTimestamp(string $version): int {
        $parts = explode('.', $version);
        if (count($parts) !== 3) {
            throw new Exception("Invalid version format");
        }
        return mktime(0, 0, 0, (int)$parts[1], (int)$parts[0], 2000 + intval($parts[2]));
    }

    public function update(): array {
        $result = ['success' => false, 'message' => ''];
        $targetRoot = dirname(__DIR__);
        $backupManager = null;
        $backup = null;

        try {
            if ($this->latestVersion === '') {
                $this->checkForUpdates();
            }
            $settings = (new SettingsManager($targetRoot))->load();
            $backupManager = new BackupManager($targetRoot, $settings);
            $backup = $backupManager->create('pre_update', [
                'fromVersion' => $this->currentVersion,
                'toVersion' => $this->latestVersion !== '' ? $this->latestVersion : 'unknown',
            ]);
            if (!file_exists(self::UPDATE_DIR)) {
                mkdir(self::UPDATE_DIR, 0755, true);
            }

            $zipFile = self::UPDATE_DIR . '/update.zip';

            $this->downloadUrl = "https://api.github.com/repos/" . self::GITHUB_REPO . "/zipball/" . self::GITHUB_BRANCH;
            if (!$this->downloadFile($this->downloadUrl, $zipFile)) {
                throw new Exception("Failed to download update");
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) {
                throw new Exception("Failed to open update archive");
            }

            $zip->extractTo(self::UPDATE_DIR);
            $zip->close();

            $extractedDirs = glob(self::UPDATE_DIR . '/*', GLOB_ONLYDIR);
            $extractedDir = $extractedDirs[0] ?? '';
            if ($extractedDir === '') {
                throw new Exception("Failed to locate extracted files");
            }

            $this->applyExtractedUpdate($extractedDir, $targetRoot);
            $this->recursiveDelete(self::UPDATE_DIR);

            $result['success'] = true;
            $result['message'] = "Successfully updated to version " . $this->latestVersion . ". Backup created: " . $backup['id'];
        } catch (Throwable $e) {
            $result['message'] = "Update failed: " . $e->getMessage();
            if ($backupManager instanceof BackupManager && is_array($backup)) {
                try {
                    $backupManager->restore((string)$backup['id'], false);
                    $result['message'] .= ' The previous system state was restored.';
                } catch (Throwable $restoreError) {
                    $result['message'] .= ' Automatic rollback failed: ' . $restoreError->getMessage();
                }
            }
            $this->recursiveDelete(self::UPDATE_DIR);
        }

        return $result;
    }

    public function applyExtractedUpdate(string $extractedDir, ?string $targetRoot = null): void {
        $targetRoot = $targetRoot ?? dirname(__DIR__);
        $adminPath = $this->getActiveAdminPath($targetRoot);
        $this->ensureLocalSettings($targetRoot, $adminPath);

        $settings = (new SettingsManager($targetRoot))->load();
        $backupDir = (string)($settings['backupDir'] ?? 'backups');
        if (preg_match('/^[A-Za-z0-9._-]{1,64}$/', $backupDir) === 1) {
            $this->protectedRootDirectories = array_values(array_unique(array_merge($this->protectedRootDirectories, [$backupDir])));
        }

        $this->recursiveCopyUpdate($extractedDir, $targetRoot, $adminPath);
        $this->assertAdminUpdateComplete($targetRoot, $adminPath);
    }

    private function ensureLocalSettings(string $targetRoot, string $adminPath): void {
        $manager = new SettingsManager($targetRoot);
        $settings = SettingsManager::defaults();
        if (realpath($targetRoot) === realpath(dirname(__DIR__))) {
            global $cloSettings;
            if (is_array($cloSettings ?? null)) {
                $settings = $cloSettings;
            }
        }
        $settings['adminPath'] = $adminPath;
        $manager->initializeLocal($settings);
    }

    public function getActiveAdminPath(?string $targetRoot = null): string {
        $targetRoot = $targetRoot ?? dirname(__DIR__);
        $settingsFile = $targetRoot . DIRECTORY_SEPARATOR . 'settings.php';
        $fallback = basename(__DIR__);

        if (realpath($targetRoot) === realpath(dirname(__DIR__))) {
            global $cloSettings;
            $candidate = trim((string)($cloSettings['adminPath'] ?? ''), "/ \t\n\r\0\x0B");
            if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $candidate) === 1) {
                return $candidate;
            }
        }

        $localFile = $targetRoot . DIRECTORY_SEPARATOR . 'settings.local.php';
        if (is_file($localFile)) {
            $local = include $localFile;
            $candidate = is_array($local) ? trim((string)($local['adminPath'] ?? ''), "/ \t\n\r\0\x0B") : '';
            if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $candidate) === 1) {
                return $candidate;
            }
        }

        if (is_file($settingsFile)) {
            $settings = file_get_contents($settingsFile);
            if ($settings !== false && preg_match('/"adminPath"\s*=>\s*[\'"]([^\'"]+)[\'"]\s*,?/', $settings, $m) === 1) {
                $candidate = trim($m[1], "/ \t\n\r\0\x0B");
                if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $candidate) === 1) {
                    return $candidate;
                }
            }
        }

        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $fallback) === 1 ? $fallback : 'admin';
    }

    private function assertAdminUpdateComplete(string $targetRoot, string $adminPath): void {
        $adminDir = $targetRoot . DIRECTORY_SEPARATOR . $adminPath;
        foreach (['autoupdate.php', 'version.txt', 'login.php', 'index.php'] as $file) {
            if (!is_file($adminDir . DIRECTORY_SEPARATOR . $file)) {
                throw new Exception("Updated admin directory is incomplete: missing " . $file);
            }
        }
    }

    private function recursiveCopyUpdate(string $src, string $dstRoot, string $adminPath, string $relative = ''): void {
        $dir = opendir($src);
        if ($dir === false) {
            throw new Exception("Failed to open update directory: " . $src);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $src . DIRECTORY_SEPARATOR . $file;
            $relativePath = $relative === '' ? $file : $relative . '/' . $file;
            $isDir = is_dir($srcPath);
            if ($this->shouldSkipUpdatePath($relativePath, $isDir)) {
                continue;
            }

            $dstPath = $this->mapUpdateDestination($dstRoot, $adminPath, $relativePath);
            if ($isDir) {
                if (!is_dir($dstPath) && !mkdir($dstPath, 0755, true)) {
                    closedir($dir);
                    throw new Exception("Failed to create directory: " . $dstPath);
                }
                $this->recursiveCopyUpdate($srcPath, $dstRoot, $adminPath, $relativePath);
            } else {
                $dstDir = dirname($dstPath);
                if (!is_dir($dstDir) && !mkdir($dstDir, 0755, true)) {
                    closedir($dir);
                    throw new Exception("Failed to create directory: " . $dstDir);
                }
                if (!copy($srcPath, $dstPath)) {
                    closedir($dir);
                    throw new Exception("Failed to copy update file: " . $relativePath);
                }
            }
        }
        closedir($dir);
    }

    private function mapUpdateDestination(string $dstRoot, string $adminPath, string $relativePath): string {
        $parts = explode('/', $relativePath);
        if (($parts[0] ?? '') === 'admin') {
            $parts[0] = $adminPath;
        }
        return $dstRoot . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function shouldSkipUpdatePath(string $relativePath, bool $isDir): bool {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return false;
        }

        if ($relativePath === 'settings.local.php') {
            return true;
        }

        $first = explode('/', $relativePath, 2)[0];
        if (in_array($first, $this->protectedRootDirectories, true)) {
            return true;
        }

        if ($isDir && in_array($relativePath, ['caching/devices', 'caching/currency', 'caching/proxyvpn', 'caching/whites_curl'], true)) {
            return true;
        }

        if (preg_match('#^db/.*\.(?:db|sqlite|sqlite3|db-wal|db-shm)$#i', $relativePath) === 1) {
            return true;
        }

        return false;
    }

    private function downloadFile(string $url, string $path): bool {
        $response = HttpClient::send(new HttpRequest(
            id: 'autoupdate-download',
            url: $url,
            headers: ['Accept: application/vnd.github.v3+json'],
            timeout: 120,
            connectTimeout: 10,
            followRedirects: true,
            verifyPeer: true,
            verifyHost: 2,
            userAgent: 'YellowTDS Updater',
        ));
        return $response->isOk() && file_put_contents($path, (string)$response->content) !== false;
    }

    private function recursiveDelete(string $dir): void {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function getCurrentVersion(): string {
        return $this->currentVersion;
    }

    public function getLatestVersion(): string {
        return $this->latestVersion;
    }
}

function autoupdate_handle_request(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if (!check_password(false)){
            $response = ['success' => false, 'message' => 'Incorrect password'];
        } else {
            $updater = new AutoUpdater();
            $response = ['success' => false, 'message' => ''];

            switch ($_POST['action']) {
                case 'check':
                    $hasUpdate = $updater->checkForUpdates();
                    $response = [
                        'success' => true,
                        'hasUpdate' => $hasUpdate,
                        'version' => $hasUpdate ? $updater->getLatestVersion() : $updater->getCurrentVersion()
                    ];
                    break;

                case 'update':
                    $result = $updater->update();
                    $response = [
                        'success' => $result['success'],
                        'message' => $result['message']
                    ];
                    break;

                default:
                    $response['message'] = 'Invalid action';
            }
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    http_response_code(405);
    echo 'Method Not Allowed';
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    autoupdate_handle_request();
}
