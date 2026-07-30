<?php

require_once __DIR__ . '/settings.php';

final class YellowTdsLogger
{
    public const LEVELS = ['trace', 'info', 'warning', 'error'];
    public const POSTBACK_DIRECTIONS = ['incoming', 'outgoing'];
    public const POSTBACK_SUCCESS_OUTCOMES = ['accepted', 'delivered', 'sent_unconfirmed'];
    private const CLEANUP_MARKER = '.retention-cleanup';

    public function __construct(
        private readonly string $root = __DIR__,
        private readonly ?array $settings = null
    ) {
    }

    public function log(string $level, string $source, string $message, array $context = []): bool
    {
        $level = strtolower(trim($level));
        if (!in_array($level, self::LEVELS, true)) {
            throw new InvalidArgumentException('Unsupported log level');
        }
        if ($level === 'trace' && !$this->debugEnabled()) {
            return false;
        }

        $source = strtolower(trim($source));
        if (preg_match('/^[a-z0-9][a-z0-9._-]{0,63}$/', $source) !== 1) {
            throw new InvalidArgumentException('Invalid log source');
        }

        $directory = $this->logsDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }

        $entry = [
            'timestamp' => date('c'),
            'level' => $level,
            'source' => $source,
            'message' => $message,
        ];
        if ($context !== []) {
            $entry['context'] = $context;
        }
        $json = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return false;
        }

        $written = @file_put_contents(
            $directory . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log',
            $json . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
        $this->cleanupIfDue();
        return $written !== false;
    }

    public function logPostback(
        string $direction,
        string $outcome,
        string $message,
        array $context = []
    ): bool {
        $direction = strtolower(trim($direction));
        $outcome = strtolower(trim($outcome));
        if (!in_array($direction, self::POSTBACK_DIRECTIONS, true)) {
            throw new InvalidArgumentException('Unsupported postback direction');
        }
        if (preg_match('/^[a-z][a-z0-9_]{0,31}$/', $outcome) !== 1) {
            throw new InvalidArgumentException('Invalid postback outcome');
        }

        $level = in_array($outcome, self::POSTBACK_SUCCESS_OUTCOMES, true) ? 'info' : 'warning';
        return $this->log(
            $level,
            'postback.' . $direction,
            $message,
            ['direction' => $direction, 'outcome' => $outcome] + $context
        );
    }

    public function cleanupIfDue(?int $now = null): int
    {
        $now ??= time();
        $directory = $this->logsDirectory();
        if (!is_dir($directory)) {
            return 0;
        }
        $marker = $directory . DIRECTORY_SEPARATOR . self::CLEANUP_MARKER;
        $lastCleanup = @filemtime($marker);
        if ($lastCleanup !== false && $now - $lastCleanup < 86400) {
            return 0;
        }
        @touch($marker, $now);

        $retentionDays = max(1, min(3650, (int)($this->effectiveSettings()['logRetentionDays'] ?? 30)));
        $cutoff = strtotime('-' . $retentionDays . ' days', $now);
        $deleted = 0;
        foreach (glob($directory . DIRECTORY_SEPARATOR . '????-??-??.log') ?: [] as $path) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', basename($path, '.log'));
            if ($date !== false && $date->getTimestamp() < $cutoff && @unlink($path)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    private function debugEnabled(): bool
    {
        return ($this->effectiveSettings()['debug'] ?? false) === true;
    }

    private function effectiveSettings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }
        global $cloSettings;
        return is_array($cloSettings ?? null) ? $cloSettings : [];
    }

    private function logsDirectory(): string
    {
        return rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR . 'logs';
    }
}

final class YellowTdsLogReader
{
    public const PAGE_SIZE = 500;
    public const MAX_DAYS = 31;

    public function __construct(private readonly string $root = __DIR__)
    {
    }

    /** @return array{entries: array<int, array<string, mixed>>, nextCursor: ?string, malformed: int, counts: array<string, int>, sources: array<int, string>} */
    public function query(string $start, string $end, array $levels, array $sources = [], string $search = '', ?string $cursor = null, int $limit = self::PAGE_SIZE): array
    {
        [$startDate, $endDate] = $this->validateRange($start, $end);
        $levels = array_values(array_intersect(YellowTdsLogger::LEVELS, array_map('strtolower', $levels)));
        $sources = array_values(array_filter(array_map('strtolower', $sources), static fn(string $source): bool => preg_match('/^[a-z0-9][a-z0-9._-]{0,63}$/', $source) === 1));
        $search = mb_strtolower(trim($search), 'UTF-8');
        $offset = $this->decodeCursor($cursor);
        $limit = max(1, min(self::PAGE_SIZE, $limit));
        $entries = [];
        $malformed = 0;
        $matched = 0;
        $hasMore = false;
        $counts = array_fill_keys(YellowTdsLogger::LEVELS, 0);
        $availableSources = [];

        for ($date = $endDate; $date >= $startDate; $date = $date->modify('-1 day')) {
            $path = $this->pathForDate($date);
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }
            $file = new SplFileObject($path, 'rb');
            $file->seek(PHP_INT_MAX);
            for ($lineNumber = $file->key(); $lineNumber >= 0; $lineNumber--) {
                $file->seek($lineNumber);
                $line = trim((string)$file->current());
                if ($line === '') continue;
                $entry = json_decode($line, true);
                if (!$this->validEntry($entry)) {
                    $malformed++;
                    continue;
                }
                $level = strtolower((string)$entry['level']);
                $source = strtolower((string)$entry['source']);
                $availableSources[$source] = true;
                if ($sources !== [] && !in_array($source, $sources, true)) continue;
                $counts[$level]++;
                if ($levels !== [] && !in_array($level, $levels, true)) continue;
                if ($search !== '' && !str_contains(mb_strtolower($line, 'UTF-8'), $search)) continue;
                if ($matched++ < $offset) continue;
                if (count($entries) >= $limit) {
                    $hasMore = true;
                    continue;
                }
                $entries[] = $entry;
            }
        }

        $sourceList = array_keys($availableSources);
        sort($sourceList, SORT_NATURAL | SORT_FLAG_CASE);
        return [
            'entries' => $entries,
            'nextCursor' => $hasMore ? $this->encodeCursor($offset + count($entries)) : null,
            'malformed' => $malformed,
            'counts' => $counts,
            'sources' => $sourceList,
        ];
    }

    /** @return array{DateTimeImmutable, DateTimeImmutable} */
    private function validateRange(string $start, string $end): array
    {
        $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if ($startDate === false || $endDate === false || $startDate->format('Y-m-d') !== $start || $endDate->format('Y-m-d') !== $end) {
            throw new InvalidArgumentException('Dates must use YYYY-MM-DD format');
        }
        $days = (int)$startDate->diff($endDate)->format('%r%a');
        if ($days < 0 || $days >= self::MAX_DAYS) {
            throw new InvalidArgumentException('Date range must be between 1 and ' . self::MAX_DAYS . ' days');
        }
        return [$startDate, $endDate];
    }

    private function pathForDate(DateTimeImmutable $date): string
    {
        return rtrim($this->root, '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $date->format('Y-m-d') . '.log';
    }

    private function validEntry(mixed $entry): bool
    {
        return is_array($entry)
            && isset($entry['timestamp'], $entry['level'], $entry['source'], $entry['message'])
            && in_array(strtolower((string)$entry['level']), YellowTdsLogger::LEVELS, true);
    }

    private function encodeCursor(int $offset): string
    {
        return rtrim(strtr(base64_encode((string)$offset), '+/', '-_'), '=');
    }

    private function decodeCursor(?string $cursor): int
    {
        if ($cursor === null || $cursor === '') return 0;
        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        if ($decoded === false || preg_match('/^\d+$/', $decoded) !== 1) {
            throw new InvalidArgumentException('Invalid cursor');
        }
        return min((int)$decoded, 10000000);
    }
}

function ytds_log(string $level, string $source, string $message, array $context = []): bool
{
    static $logger;
    $logger ??= new YellowTdsLogger();
    return $logger->log($level, $source, $message, $context);
}

function ytds_log_postback(
    string $direction,
    string $outcome,
    string $message,
    array $context = []
): bool {
    static $logger;
    $logger ??= new YellowTdsLogger();
    return $logger->logPostback($direction, $outcome, $message, $context);
}

/** Backward-compatible wrapper for integrations still calling the legacy API. */
function add_log(string $subdir, string $msg, bool $logIp = false): bool
{
    $legacy = strtolower($subdir);
    $level = match ($legacy) {
        'trace' => 'trace',
        'warning' => 'warning',
        'error', 'errors' => 'error',
        default => 'info',
    };
    $source = match ($legacy) {
        'error', 'errors', 'warning', 'trace' => ytds_log_caller_source(),
        'login' => 'auth',
        default => preg_replace('/[^a-z0-9._-]/', '-', $legacy) ?: 'application',
    };
    $context = [];
    if ($logIp) {
        $context['ip'] = ytds_log_request_ip();
    }
    return ytds_log($level, $source, $msg, $context);
}

function add_error_log(string $msg, bool $logIp = false, bool $die = false): void
{
    $context = $logIp ? ['ip' => ytds_log_request_ip()] : [];
    ytds_log('error', ytds_log_caller_source(), $msg, $context);
    if ($die) die($msg);
}

function ytds_log_request_ip(): string
{
    return (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
}

function ytds_log_caller_source(): string
{
    $map = [
        'db' => 'database', 'password' => 'auth', 'securitycheck' => 'auth',
        'update' => 'geobases', 'ipcountry' => 'geobases', 'campeditor' => 'admin',
        'clmnseditor' => 'admin', 'main' => 'routing', 'abtest' => 'routing',
        'deepl' => 'thankyou', 'libretranslate' => 'thankyou',
    ];
    foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6) as $frame) {
        $file = (string)($frame['file'] ?? '');
        if ($file === '' || realpath($file) === realpath(__FILE__)) continue;
        $name = strtolower(pathinfo($file, PATHINFO_FILENAME));
        return $map[$name] ?? (preg_replace('/[^a-z0-9._-]/', '-', $name) ?: 'application');
    }
    return 'application';
}
