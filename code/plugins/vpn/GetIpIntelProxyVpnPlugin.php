<?php

require_once __DIR__ . '/../base.php';

class GetIpIntelProxyVpnPlugin extends BaseProxyVpnPlugin
{
    private const BAN_ON_PROBABILITY = 0.99;

    public function getName(): string
    {
        return 'ipintel';
    }

    public function buildDetectionRequest(string $ip, array $server): HttpRequest
    {
        $host = (string)($server['HTTP_HOST'] ?? 'localhost');
        $contactEmail = 'support@' . preg_replace('/[^A-Za-z0-9.-]/', '', $host);
        $url = 'http://check.getipintel.net/check.php?ip=' . rawurlencode($ip) . '&contact=' . rawurlencode($contactEmail) . '&flags=m';
        return new HttpRequest($this->getName(), $url, timeout: 5, connectTimeout: 5);
    }

    public function parseDetectionResponse(HttpResponse $response): ?bool
    {
        if (!$response->isOk()) {
            throw new Exception("HTTP {$response->httpCode()}; curl {$response->errno} {$response->error}");
        }

        $content = trim((string)$response->content);
        if ($content === '' || !is_numeric($content)) {
            throw new Exception('Unexpected response: ' . $content);
        }

        $score = (float)$content;
        if ($score < 0) {
            throw new Exception('Negative response: ' . $content);
        }

        return $score >= self::BAN_ON_PROBABILITY;
    }
}
