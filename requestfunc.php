<?php
require_once __DIR__ . '/bases/ipcountry.php';
require_once __DIR__ . '/paths.php';

final class HttpRequest
{
    public function __construct(
        public string $id,
        public string $url,
        public string $method = 'GET',
        public string|array|null $body = null,
        public array $headers = [],
        public int $timeout = 10,
        public int $connectTimeout = 5,
        public bool $followRedirects = false,
        public bool $verifyPeer = false,
        public int $verifyHost = 0,
        public ?string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        public ?string $referer = null,
        public bool $captureHeaders = false,
        public bool $failOnHttpError = false,
        public ?int $httpVersion = null,
    ) {
        $this->method = strtoupper($this->method);
    }
}

final class HttpResponse
{
    /**
     * @param array<string, array<int, string>> $headers
     * @param array<string, mixed> $info
     */
    public function __construct(
        public string $id,
        public string|false $content,
        public array $headers,
        public array $info,
        public string $error = '',
        public int $errno = 0,
    ) {
    }

    public function httpCode(): int
    {
        return (int)($this->info['http_code'] ?? 0);
    }

    public function contentType(): ?string
    {
        $contentType = $this->info['content_type'] ?? null;
        return is_string($contentType) && $contentType !== '' ? $contentType : null;
    }

    public function isOk(): bool
    {
        return $this->errno === 0
            && $this->error === ''
            && $this->httpCode() >= 200
            && $this->httpCode() < 300
            && $this->content !== false;
    }
}

final class HttpClient
{
    public static function send(HttpRequest $request): HttpResponse
    {
        [$handle, $context] = self::createHandle($request);
        $content = curl_exec($handle);
        $response = self::createResponse($request, $handle, $context, $content);
        curl_close($handle);
        return $response;
    }

    /** @param HttpRequest[] $requests @return array<string, HttpResponse> */
    public static function sendParallel(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $multi = curl_multi_init();
        $handles = [];
        foreach ($requests as $request) {
            if (!$request instanceof HttpRequest) {
                throw new InvalidArgumentException('sendParallel expects HttpRequest objects');
            }
            [$handle, $context] = self::createHandle($request);
            curl_multi_add_handle($multi, $handle);
            $handles[spl_object_id($handle)] = [$handle, $request, $context];
        }

        do {
            do {
                $status = curl_multi_exec($multi, $running);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
            if ($running && $status === CURLM_OK) {
                $selected = curl_multi_select($multi, 1.0);
                if ($selected === -1) {
                    usleep(1000);
                }
            }
        } while ($running && $status === CURLM_OK);

        $responses = [];
        foreach ($handles as [$handle, $request, $context]) {
            $responses[$request->id] = self::createResponse(
                $request,
                $handle,
                $context,
                curl_multi_getcontent($handle)
            );
            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }
        curl_multi_close($multi);
        return $responses;
    }

    /** @return array{0: CurlHandle, 1: object} */
    private static function createHandle(HttpRequest $request): array
    {
        $handle = curl_init();
        if ($handle === false) {
            throw new RuntimeException('Failed to initialize cURL');
        }
        $context = (object)['headers' => []];
        $options = [
            CURLOPT_URL => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => $request->verifyPeer,
            CURLOPT_SSL_VERIFYHOST => $request->verifyHost,
            CURLOPT_TIMEOUT => $request->timeout,
            CURLOPT_CONNECTTIMEOUT => $request->connectTimeout,
            CURLOPT_FOLLOWLOCATION => $request->followRedirects,
            CURLOPT_FAILONERROR => $request->failOnHttpError,
        ];
        if ($request->headers !== []) {
            $options[CURLOPT_HTTPHEADER] = $request->headers;
        }
        if ($request->userAgent !== null) {
            $options[CURLOPT_USERAGENT] = $request->userAgent;
        }
        if ($request->referer !== null && $request->referer !== '') {
            $options[CURLOPT_REFERER] = $request->referer;
        }
        if ($request->httpVersion !== null) {
            $options[CURLOPT_HTTP_VERSION] = $request->httpVersion;
        }
        if ($request->method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $request->method;
        }
        if ($request->body !== null) {
            $options[CURLOPT_POSTFIELDS] = $request->body;
        }
        if ($request->captureHeaders) {
            $options[CURLOPT_HEADERFUNCTION] = static function ($handle, string $line) use ($context): int {
                $length = strlen($line);
                $trimmed = trim($line);
                if ($trimmed === '') {
                    return $length;
                }
                if (str_starts_with(strtoupper($trimmed), 'HTTP/')) {
                    $context->headers = [':status' => [$trimmed]];
                    return $length;
                }
                $separator = strpos($line, ':');
                if ($separator !== false) {
                    $name = strtolower(trim(substr($line, 0, $separator)));
                    $value = trim(substr($line, $separator + 1));
                    $context->headers[$name] ??= [];
                    $context->headers[$name][] = $value;
                }
                return $length;
            };
        }
        if (!curl_setopt_array($handle, $options)) {
            curl_close($handle);
            throw new RuntimeException('Failed to configure cURL request');
        }
        return [$handle, $context];
    }

    private static function createResponse(
        HttpRequest $request,
        CurlHandle $handle,
        object $context,
        string|false $content
    ): HttpResponse {
        return new HttpResponse(
            $request->id,
            $content,
            is_array($context->headers) ? $context->headers : [],
            curl_getinfo($handle),
            curl_error($handle),
            curl_errno($handle),
        );
    }
}

function send_access_control_headers(): void
{
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parsedUrl = parse_url($_SERVER['HTTP_REFERER']);
        if (is_array($parsedUrl) && isset($parsedUrl['scheme'], $parsedUrl['host'])) {
            $origin = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (!empty($parsedUrl['port'])) {
                $origin .= ':' . $parsedUrl['port'];
            }
            header('Access-Control-Allow-Origin: ' . $origin);
        }
    }
    header('Access-Control-Allow-Credentials: true');
}

function get_abs_from_rel(string $url): string
{
    $fullpath = get_cloaker_path() . $url;
    if (!str_ends_with($url, '.php')) {
        $fullpath .= '/';
    }
    return $fullpath;
}

function get_request_headers(bool $ispost = false): array
{
    $ip = getip();
    $headers = [
        'X-YWBCLO-UIP: ' . $ip,
        'X-FORWARDED-FOR: ' . $ip,
        'CF-CONNECTING-IP: ' . $ip,
        'FORWARDED-FOR: ' . $ip,
        'X-COMING-FROM: ' . $ip,
        'COMING-FROM: ' . $ip,
        'FORWARDED-FOR-IP: ' . $ip,
        'CLIENT-IP: ' . $ip,
        'X-REAL-IP: ' . $ip,
        'REMOTE-ADDR: ' . $ip,
    ];
    if ($ispost) {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    return $headers;
}

function get(string $url): array
{
    $response = HttpClient::send(new HttpRequest(
        id: 'legacy-get',
        url: $url,
        headers: get_request_headers(false),
        timeout: 0,
        connectTimeout: 0,
        referer: (string)($_SERVER['REQUEST_URI'] ?? ''),
    ));
    return ['content' => $response->content, 'info' => $response->info, 'error' => $response->error];
}

function post(string $url, array $postfields): array
{
    $response = HttpClient::send(new HttpRequest(
        id: 'legacy-post',
        url: $url,
        method: 'POST',
        body: $postfields,
        headers: get_request_headers(true),
        timeout: 10,
        connectTimeout: 0,
        referer: (string)($_SERVER['REQUEST_URI'] ?? ''),
        httpVersion: CURL_HTTP_VERSION_1_1,
    ));
    return ['content' => $response->content, 'info' => $response->info, 'error' => $response->error];
}
