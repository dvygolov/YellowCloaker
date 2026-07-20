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

/** @return array<string, mixed> */
function backups_execute_action(
    BackupManager $manager,
    string $action,
    string $id = '',
    string $mode = '',
    ?string $operationId = null,
): array
{
    if ($action === 'create') {
        return [
            'success' => true,
            'message' => 'Backup created successfully.',
            'backup' => $manager->create('manual', [], $mode, $operationId),
        ];
    }

    if ($id === '') {
        throw new InvalidArgumentException('Backup id is required');
    }

    if ($action === 'delete') {
        $manager->delete($id);
        return ['success' => true, 'message' => 'Backup deleted'];
    }
    if ($action === 'restore') {
        $restored = $manager->restore($id, true, $operationId);
        return [
            'success' => true,
            'message' => 'System restored. All settings and files now match the selected backup.',
            'redirect' => $restored['redirect'],
            'safetyBackup' => $restored['safetyBackup'],
        ];
    }

    throw new InvalidArgumentException('Invalid action');
}

function backups_handle_request(): void
{
    if (!check_password(false)) {
        backups_send(['error' => 'Forbidden'], 403);
        return;
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    try {
        $root = dirname(__DIR__);
        $settings = (new SettingsManager($root))->load();
        $manager = new BackupManager($root, $settings);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            if (($_GET['action'] ?? '') === 'status') {
                try {
                    backups_send(['operation' => $manager->operationStatus((string)($_GET['operationId'] ?? ''))]);
                } catch (InvalidArgumentException $e) {
                    backups_send(['error' => $e->getMessage()], 404);
                }
                return;
            }
            $operation = $manager->latestOperation();
            $operationRunning = is_array($operation) && ($operation['status'] ?? '') === 'running';
            backups_send([
                'backups' => $operationRunning ? null : $manager->list(),
                'directory' => (string)($settings['backupDir'] ?? 'backups'),
                'limits' => [
                    BackupManager::MODE_FULL => BackupManager::MAX_BACKUPS_PER_MODE,
                    BackupManager::MODE_QUICK => BackupManager::MAX_BACKUPS_PER_MODE,
                ],
                'databaseBytes' => $manager->databaseBytes(),
                'operation' => $operation,
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
        $mode = (string)($body['mode'] ?? '');
        $operationId = isset($body['operationId']) ? (string)$body['operationId'] : null;
        try {
            backups_send(backups_execute_action($manager, $action, $id, $mode, $operationId));
        } catch (InvalidArgumentException $e) {
            backups_send(['error' => $e->getMessage()], 422);
        }
    } catch (Throwable $e) {
        ytds_log('error', 'admin', $e->getMessage(), ['action' => 'backups']);
        backups_send(['error' => $e->getMessage()], 500);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    backups_handle_request();
}
