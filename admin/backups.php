<?php

// JSON endpoints must never leak PHP warnings into the response body.
ini_set('display_errors', '0');

require_once __DIR__ . '/../backupmanager.php';
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/accesscontrol.php';

function backups_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function backups_handle_request(): void
{
    if (!check_password(false)) {
        backups_send(['error' => 'Forbidden'], 403);
        return;
    }

    try {
        $root = dirname(__DIR__);
        $settings = (new SettingsManager($root))->load();
        $manager = new BackupManager($root, $settings);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            backups_send([
                'backups' => $manager->list(),
                'directory' => (string)($settings['backupDir'] ?? 'backups'),
                'limit' => BackupManager::MAX_BACKUPS,
            ]);
            return;
        }
        if ($method !== 'POST') {
            backups_send(['error' => 'Method Not Allowed'], 405);
            return;
        }

        $body = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($body)) {
            backups_send(['error' => 'Invalid JSON payload'], 422);
            return;
        }
        $action = (string)($body['action'] ?? '');
        $id = (string)($body['id'] ?? '');
        if ($id === '') {
            backups_send(['error' => 'Backup id is required'], 422);
            return;
        }

        if ($action === 'delete') {
            $manager->delete($id);
            backups_send(['success' => true, 'message' => 'Backup deleted']);
            return;
        }
        if ($action === 'restore') {
            $restored = $manager->restore($id, true);
            backups_send([
                'success' => true,
                'message' => 'System restored. All settings and files now match the selected backup.',
                'redirect' => $restored['redirect'],
                'safetyBackup' => $restored['safetyBackup'],
            ]);
            return;
        }

        backups_send(['error' => 'Invalid action'], 422);
    } catch (Throwable $e) {
        error_log('[backups] ' . $e->getMessage());
        backups_send(['error' => $e->getMessage()], 500);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    backups_handle_request();
}
