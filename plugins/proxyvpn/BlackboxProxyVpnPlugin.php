<?php

require_once __DIR__ . '/../base.php';

class BlackboxProxyVpnPlugin extends BaseProxyVpnPlugin
{
    public function getName(): string
    {
        return 'blackbox';
    }

    public function buildDetectionRequest(string $ip, array $server): PluginHttpRequest
    {
        return new PluginHttpRequest($this->getName(), 'https://blackbox.ipinfo.app/lookup/' . rawurlencode($ip), 5, 5);
    }

    public function parseDetectionResponse(PluginHttpResponse $response): ?bool
    {
        if (!$response->isOk()) {
            throw new Exception("HTTP {$response->httpCode}; curl {$response->errno} {$response->error}");
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
