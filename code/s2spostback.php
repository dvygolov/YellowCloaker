<?php

require_once __DIR__ . '/logging.php';
require_once __DIR__ . '/macros.php';

interface S2sWritableSocket
{
    public function setWriteTimeout(float $seconds): bool;

    public function write(string $bytes): int|false;

    public function close(): void;
}

interface S2sSocketConnector
{
    /**
     * @param array<string, array<string, mixed>> $contextOptions
     */
    public function connect(
        string $endpoint,
        float $timeoutSeconds,
        array $contextOptions
    ): ?S2sWritableSocket;
}

interface S2sPostbackTransport
{
    public function send(string $method, string $url): bool;

    public function lastError(): string;
}

final class NativeS2sWritableSocket implements S2sWritableSocket
{
    /** @var resource|null */
    private mixed $stream;

    /** @param resource $stream */
    public function __construct(mixed $stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('A writable stream resource is required.');
        }
        $this->stream = $stream;
        @stream_set_write_buffer($this->stream, 0);
    }

    public function setWriteTimeout(float $seconds): bool
    {
        if (!is_resource($this->stream) || !is_finite($seconds) || $seconds <= 0) {
            return false;
        }

        $wholeSeconds = (int)floor($seconds);
        $microseconds = (int)ceil(($seconds - $wholeSeconds) * 1_000_000);
        if ($microseconds >= 1_000_000) {
            $wholeSeconds++;
            $microseconds = 0;
        }

        return @stream_set_timeout($this->stream, $wholeSeconds, $microseconds);
    }

    public function write(string $bytes): int|false
    {
        if (!is_resource($this->stream)) {
            return false;
        }
        return @fwrite($this->stream, $bytes);
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            @fclose($this->stream);
        }
        $this->stream = null;
    }
}

final class NativeS2sSocketConnector implements S2sSocketConnector
{
    public function connect(
        string $endpoint,
        float $timeoutSeconds,
        array $contextOptions
    ): ?S2sWritableSocket {
        $context = stream_context_create($contextOptions);
        $errno = 0;
        $error = '';
        $stream = @stream_socket_client(
            $endpoint,
            $errno,
            $error,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
            $context
        );
        return is_resource($stream) ? new NativeS2sWritableSocket($stream) : null;
    }
}

/**
 * Sends a complete HTTP request and closes the socket without reading a
 * response. This keeps event collection independent from a recipient's
 * response time while still making DNS, TCP/TLS and writes bounded.
 */
final class WriteOnlyS2sPostbackTransport implements S2sPostbackTransport
{
    public const MAX_URL_BYTES = 8192;
    public const DEFAULT_CONNECT_TIMEOUT_SECONDS = 1.0;
    public const DEFAULT_WRITE_TIMEOUT_SECONDS = 0.5;

    private S2sSocketConnector $connector;
    private float $connectTimeoutSeconds;
    private float $writeTimeoutSeconds;
    private string $error = '';

    public function __construct(
        ?S2sSocketConnector $connector = null,
        float $connectTimeoutSeconds = self::DEFAULT_CONNECT_TIMEOUT_SECONDS,
        float $writeTimeoutSeconds = self::DEFAULT_WRITE_TIMEOUT_SECONDS
    ) {
        if (
            !is_finite($connectTimeoutSeconds)
            || $connectTimeoutSeconds <= 0
            || !is_finite($writeTimeoutSeconds)
            || $writeTimeoutSeconds <= 0
        ) {
            throw new InvalidArgumentException('S2S socket timeouts must be positive finite values.');
        }
        $this->connector = $connector ?? new NativeS2sSocketConnector();
        $this->connectTimeoutSeconds = $connectTimeoutSeconds;
        $this->writeTimeoutSeconds = $writeTimeoutSeconds;
    }

    public function send(string $method, string $url): bool
    {
        $this->error = '';
        $request = $this->buildRequest($method, $url);
        if ($request === null) {
            return false;
        }

        $socket = null;
        try {
            $socket = $this->connector->connect(
                $request['endpoint'],
                $this->connectTimeoutSeconds,
                $request['context']
            );
            if ($socket === null) {
                $this->error = 'connection failed';
                return false;
            }

            $bytes = $request['bytes'];
            $length = strlen($bytes);
            $offset = 0;
            $deadline = microtime(true) + $this->writeTimeoutSeconds;
            while ($offset < $length) {
                $remainingSeconds = $deadline - microtime(true);
                if ($remainingSeconds <= 0) {
                    $this->error = 'write deadline exceeded';
                    return false;
                }
                if (!$socket->setWriteTimeout($remainingSeconds)) {
                    $this->error = 'failed to set write timeout';
                    return false;
                }

                $written = $socket->write(substr($bytes, $offset));
                if (!is_int($written) || $written <= 0) {
                    $this->error = 'socket write failed';
                    return false;
                }
                $offset += $written;
            }
            return true;
        } catch (Throwable $e) {
            $this->error = 'socket error: ' . $e->getMessage();
            return false;
        } finally {
            $socket?->close();
        }
    }

    public function lastError(): string
    {
        return $this->error;
    }

    /**
     * @return array{
     *     endpoint:string,
     *     context:array<string, array<string, mixed>>,
     *     bytes:string
     * }|null
     */
    private function buildRequest(string $method, string $url): ?array
    {
        $method = strtoupper(trim($method));
        $url = trim($url);
        if (
            !in_array($method, ['GET', 'POST'], true)
            || $url === ''
            || strlen($url) > self::MAX_URL_BYTES
            || preg_match('/[\x00-\x20\x7F]/', $url) === 1
        ) {
            $this->error = 'invalid method or URL';
            return null;
        }

        $parts = parse_url($url);
        if (
            !is_array($parts)
            || isset($parts['user'])
            || isset($parts['pass'])
            || !isset($parts['scheme'], $parts['host'])
        ) {
            $this->error = 'invalid URL authority';
            return null;
        }

        $scheme = strtolower((string)$parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->error = 'unsupported URL scheme';
            return null;
        }

        $host = trim((string)$parts['host'], '[]');
        if (!$this->isValidHost($host)) {
            $this->error = 'invalid URL host';
            return null;
        }

        $port = isset($parts['port'])
            ? (int)$parts['port']
            : ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            $this->error = 'invalid URL port';
            return null;
        }

        $path = (string)($parts['path'] ?? '/');
        $path = $path === '' ? '/' : $path;
        $query = (string)($parts['query'] ?? '');
        if (
            !str_starts_with($path, '/')
            || !$this->isValidRequestTargetPart($path)
            || !$this->isValidRequestTargetPart($query)
        ) {
            $this->error = 'invalid URL path or query';
            return null;
        }

        $isIpv6 = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        $authorityHost = $isIpv6 ? '[' . $host . ']' : $host;
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $hostHeader = $authorityHost . ($port === $defaultPort ? '' : ':' . $port);
        $endpoint = ($scheme === 'https' ? 'tls' : 'tcp') . '://' . $authorityHost . ':' . $port;
        $target = $path;
        $body = '';
        if ($method === 'GET' && $query !== '') {
            $target .= '?' . $query;
        } elseif ($method === 'POST') {
            $body = $query;
        }

        $headers = [
            $method . ' ' . $target . ' HTTP/1.1',
            'Host: ' . $hostHeader,
            'User-Agent: YellowTDS-S2S/1.0',
            'Accept: */*',
            'Connection: close',
        ];
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $headers[] = 'Content-Length: ' . strlen($body);
        }

        $context = [];
        if ($scheme === 'https') {
            $context['ssl'] = [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'peer_name' => $host,
                'SNI_enabled' => true,
                'disable_compression' => true,
            ];
        }

        return [
            'endpoint' => $endpoint,
            'context' => $context,
            'bytes' => implode("\r\n", $headers) . "\r\n\r\n" . $body,
        ];
    }

    private function isValidHost(string $host): bool
    {
        if ($host === '' || strlen($host) > 253) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        $host = rtrim($host, '.');
        if ($host === '') {
            return false;
        }
        foreach (explode('.', $host) as $label) {
            if (
                strlen($label) < 1
                || strlen($label) > 63
                || preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $label) !== 1
            ) {
                return false;
            }
        }
        return true;
    }

    private function isValidRequestTargetPart(string $value): bool
    {
        return preg_match('/[^\x21-\x7E]/', $value) !== 1
            && !str_contains($value, '\\')
            && preg_match('/%(?![0-9A-Fa-f]{2})/', $value) !== 1;
    }
}

/**
 * Event macros may also be used in the path, so their values are percent
 * encoded before the existing click-query macro processor runs.
 */
function yellowtds_expand_event_s2s_url(
    string $template,
    array $click,
    string $eventName,
    int $eventValue,
    int $stepIndex,
    string $variant,
    ?string &$error = null
): ?string {
    $error = null;
    $url = strtr($template, [
        '{event}' => rawurlencode($eventName),
        '{event_value}' => rawurlencode((string)$eventValue),
        '{step_index}' => rawurlencode((string)$stepIndex),
        '{variant}' => rawurlencode($variant),
        '{trigger_type}' => 'event',
        // A conversion status does not exist for an event-triggered postback.
        '{status}' => '',
    ]);

    try {
        $macros = new MacrosProcessor(
            null,
            $click,
            (string)($click['clickid'] ?? ''),
            (string)($click['userid'] ?? '')
        );
        $url = $macros->replace_url_macros($url);
    } catch (Throwable $e) {
        $error = $e->getMessage();
        return null;
    }

    return $url === '' ? null : $url;
}

function yellowtds_s2s_rule_field(mixed $rule, string $field, mixed $default = null): mixed
{
    if (is_array($rule)) {
        return $rule[$field] ?? $default;
    }
    if (is_object($rule) && isset($rule->{$field})) {
        return $rule->{$field};
    }
    return $default;
}

function process_event_s2s_postbacks(
    array $s2sPostbacks,
    string $eventName,
    int $eventValue,
    int $stepIndex,
    string $variant,
    array $click,
    ?S2sPostbackTransport $transport = null
): void {
    if ($click === []) {
        return;
    }
    $transport ??= new WriteOnlyS2sPostbackTransport();

    foreach ($s2sPostbacks as $index => $s2s) {
        $events = yellowtds_s2s_rule_field($s2s, 'events', []);
        $url = yellowtds_s2s_rule_field($s2s, 'url', '');
        $method = strtoupper((string)yellowtds_s2s_rule_field($s2s, 'method', ''));
        if (
            !is_array($events)
            || !in_array($eventName, $events, true)
            || !is_string($url)
            || trim($url) === ''
        ) {
            continue;
        }

        $macroError = null;
        $finalUrl = yellowtds_expand_event_s2s_url(
            $url,
            $click,
            $eventName,
            $eventValue,
            $stepIndex,
            $variant,
            $macroError
        );
        if ($finalUrl === null) {
            ytds_log_postback('outgoing', 'failed', 'Outgoing event S2S postback failed', [
                'rule_number' => $index + 1,
                'trigger_type' => 'event',
                'method' => $method,
                'campaign_id' => isset($click['campaign_id']) ? (int)$click['campaign_id'] : null,
                'clickid' => (string)($click['clickid'] ?? ''),
                'event' => $eventName,
                'event_value' => $eventValue,
                'error' => $macroError === null || $macroError === ''
                    ? 'Macro expansion failed'
                    : 'Macro expansion failed: ' . $macroError,
            ]);
            continue;
        }

        try {
            $sent = $transport->send($method, $finalUrl);
            $transportError = $sent ? '' : $transport->lastError();
        } catch (Throwable $e) {
            $sent = false;
            $transportError = 'transport error: ' . $e->getMessage();
        }
        $context = [
            'rule_number' => $index + 1,
            'trigger_type' => 'event',
            'method' => $method,
            'campaign_id' => isset($click['campaign_id']) ? (int)$click['campaign_id'] : null,
            'clickid' => (string)($click['clickid'] ?? ''),
            'event' => $eventName,
            'event_value' => $eventValue,
            'step_index' => $stepIndex,
            'variant' => $variant,
            'url' => $finalUrl,
            'response_checked' => false,
        ];
        if ($transportError !== '') {
            $context['error'] = $transportError;
        }
        ytds_log_postback(
            'outgoing',
            $sent ? 'sent_unconfirmed' : 'failed',
            $sent
                ? 'Outgoing event S2S postback sent; recipient response was not checked'
                : 'Outgoing event S2S postback failed',
            array_filter($context, static fn(mixed $value): bool => $value !== null && $value !== '')
        );
    }
}
