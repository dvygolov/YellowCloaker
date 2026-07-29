<?php

require_once __DIR__ . '/../base.php';

class BlackboxProxyVpnPlugin extends BaseProxyVpnPlugin
{
    public function getName(): string
    {
        return 'blackbox';
    }

    public function buildDetectionRequest(string $ip, array $server): HttpRequest
    {
        return new HttpRequest($this->getName(), 'https://blackbox.ipinfo.app/lookup/' . rawurlencode($ip), timeout: 5, connectTimeout: 5);
    }

    public function parseDetectionResponse(HttpResponse $response): ?bool
    {
        if (!$response->isOk()) {
            throw new Exception("HTTP {$response->httpCode()}; curl {$response->errno} {$response->error}");
        }

        $content = trim((string)$response->content);
        if ($content === 'Y') {
            return true;
        }
        if ($content === 'N') {
            return false;
        }

        throw new Exception('Unexpected response: ' . $content);
    }
}
