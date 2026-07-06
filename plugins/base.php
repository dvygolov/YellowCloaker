<?php

require_once __DIR__ . '/http.php';

abstract class BaseCurrencyPlugin
{
    abstract public function getName(): string;

    abstract public function buildRatesRequest(): PluginHttpRequest;

    /**
     * Returns rates normalized as USD per 1 unit of currency.
     *
     * @return array<string, float>
     */
    abstract public function parseRatesResponse(PluginHttpResponse $response): array;
}

abstract class BaseProxyVpnPlugin
{
    abstract public function getName(): string;

    abstract public function buildDetectionRequest(string $ip, array $server): PluginHttpRequest;

    abstract public function parseDetectionResponse(PluginHttpResponse $response): ?bool;
}
