<?php

require_once __DIR__ . '/../databasemaintenance.php';

function database_maintenance_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function database_maintenance_csrf_token(): string
{
    get_session();
    if (empty($_SESSION['database_maintenance_csrf']) || !is_string($_SESSION['database_maintenance_csrf'])) {
        $_SESSION['database_maintenance_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['database_maintenance_csrf'];
}

function database_maintenance_assert_csrf(array $body): void
{
    $provided = (string)($body['csrf'] ?? '');
    $expected = database_maintenance_csrf_token();
    if (!database_maintenance_token_matches($expected, $provided)) {
        throw new InvalidArgumentException('Invalid maintenance security token');
    }
}

function database_maintenance_token_matches(string $expected, string $provided): bool
{
    return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}

function database_maintenance_assert_confirmation(string $confirmation): void
{
    if ($confirmation !== 'DELETE') {
        throw new InvalidArgumentException('Type DELETE to confirm irreversible cleanup');
    }
}

/** @return array<string, mixed> */
function database_maintenance_body(): array
{
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body)) {
        throw new InvalidArgumentException('Invalid JSON payload');
    }
    return $body;
}

/** @return array{0: int, 1: int} */
function database_maintenance_range(array $input, string $timezone): array
{
    $today = (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('d.m.y');
    return DatabaseMaintenance::parseDateRange(
        (string)($input['startdate'] ?? $today),
        (string)($input['enddate'] ?? $today),
        $timezone,
    );
}

/** @param array<int, mixed> $campaignIds */
function database_maintenance_selection_digest(array $campaignIds, bool $includeTrafficback, int $start, int $end): string
{
    $ids = [];
    foreach ($campaignIds as $campaignId) {
        if (filter_var($campaignId, FILTER_VALIDATE_INT) === false || (int)$campaignId < 1) {
            throw new InvalidArgumentException('Campaign ids must be positive integers');
        }
        $ids[(int)$campaignId] = true;
    }
    $ids = array_keys($ids);
    sort($ids, SORT_NUMERIC);
    return hash('sha256', json_encode([$ids, $includeTrafficback, $start, $end], JSON_UNESCAPED_SLASHES));
}

function database_maintenance_owner(): string
{
    get_session();
    $owner = session_id();
    if ($owner === '') {
        throw new RuntimeException('Admin session is unavailable');
    }
    return $owner;
}

function database_maintenance_handle_request(): void
{
    ini_set('display_errors', '0');
    require_once __DIR__ . '/securitycheck.php';
    require_once __DIR__ . '/../db/db.php';

    global $cloSettings;
    $settings = is_array($cloSettings ?? null) ? $cloSettings : [];
    $maintenanceDb = isset($db) && $db instanceof Db ? $db : new Db();
    $common = $maintenanceDb->get_common_settings();
    $timezone = (string)($common['statistics']['timezone'] ?? 'UTC');
    if (!in_array($timezone, timezone_identifiers_list(), true)) {
        $timezone = 'UTC';
    }
    $service = new DatabaseMaintenance(dirname(__DIR__), $settings);
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $action = (string)($_GET['action'] ?? ($method === 'GET' ? 'summary' : ''));

    try {
        $owner = database_maintenance_owner();

        if ($method === 'GET') {
            if ($action === 'summary') {
                [$start, $end] = database_maintenance_range($_GET, $timezone);
                $summary = $service->summary($start, $end);
                try {
                    $summary['activeJob'] = $service->activeJobStatus($owner);
                } catch (RuntimeException) {
                    $summary['activeJob'] = ['status' => 'owned_by_another_session'];
                }
                database_maintenance_send([
                    'success' => true,
                    'timezone' => $timezone,
                    'start' => $start,
                    'end' => $end,
                    'summary' => $summary,
                ]);
                return;
            }
            if ($action === 'status') {
                $jobId = isset($_GET['jobId']) ? (string)$_GET['jobId'] : null;
                database_maintenance_send(['success' => true, 'job' => $service->status($owner, $jobId)]);
                return;
            }
            database_maintenance_send(['error' => 'Invalid action'], 404);
            return;
        }

        if ($method !== 'POST') {
            database_maintenance_send(['error' => 'Method Not Allowed'], 405);
            return;
        }

        $body = database_maintenance_body();
        database_maintenance_assert_csrf($body);
        $campaignIds = isset($body['campaignIds']) && is_array($body['campaignIds']) ? $body['campaignIds'] : [];
        $includeTrafficback = ($body['includeTrafficback'] ?? false) === true;

        if ($action === 'preview') {
            [$start, $end] = database_maintenance_range($body, $timezone);
            $preview = $service->preview($campaignIds, $includeTrafficback, $start, $end);
            $previewToken = bin2hex(random_bytes(24));
            $_SESSION['database_maintenance_preview'] = [
                'token' => $previewToken,
                'digest' => database_maintenance_selection_digest($campaignIds, $includeTrafficback, $start, $end),
                'expiresAt' => time() + 600,
            ];
            session_write_close();
            database_maintenance_send(['success' => true, 'preview' => $preview, 'previewToken' => $previewToken]);
            return;
        }

        if ($action === 'start') {
            [$start, $end] = database_maintenance_range($body, $timezone);
            database_maintenance_assert_confirmation((string)($body['confirmation'] ?? ''));
            $preview = $_SESSION['database_maintenance_preview'] ?? null;
            $providedToken = (string)($body['previewToken'] ?? '');
            $digest = database_maintenance_selection_digest($campaignIds, $includeTrafficback, $start, $end);
            if (
                !is_array($preview)
                || $providedToken === ''
                || !database_maintenance_token_matches((string)($preview['token'] ?? ''), $providedToken)
                || !hash_equals((string)($preview['digest'] ?? ''), $digest)
                || (int)($preview['expiresAt'] ?? 0) < time()
            ) {
                throw new InvalidArgumentException('Cleanup preview expired or no longer matches the selection');
            }
            unset($_SESSION['database_maintenance_preview']);
            session_write_close();
            database_maintenance_send([
                'success' => true,
                'job' => $service->startCleanup($campaignIds, $includeTrafficback, $start, $end, $owner),
            ]);
            return;
        }

        session_write_close();
        $jobId = (string)($body['jobId'] ?? '');
        if ($action === 'batch') {
            database_maintenance_send(['success' => true, 'job' => $service->runBatch($jobId, $owner)]);
            return;
        }
        if ($action === 'cancel') {
            database_maintenance_send(['success' => true, 'job' => $service->cancel($jobId, $owner)]);
            return;
        }
        if ($action === 'compact') {
            if ($jobId === '') {
                $job = $service->startStandaloneCompaction($owner);
                $jobId = (string)$job['id'];
            }
            database_maintenance_send(['success' => true, 'job' => $service->compact($jobId, $owner)]);
            return;
        }
        database_maintenance_send(['error' => 'Invalid action'], 404);
    } catch (DatabaseMaintenanceBusyException $exception) {
        database_maintenance_send(['error' => $exception->getMessage(), 'retryable' => true], 409);
    } catch (InvalidArgumentException $exception) {
        database_maintenance_send(['error' => $exception->getMessage()], 422);
    } catch (Throwable $exception) {
        ytds_log('error', 'database-maintenance', $exception->getMessage(), ['action' => $action]);
        database_maintenance_send(['error' => 'Database maintenance failed: ' . $exception->getMessage()], 500);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    database_maintenance_handle_request();
}
