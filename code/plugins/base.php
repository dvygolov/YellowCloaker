<?php

require_once dirname(__DIR__) . '/requestfunc.php';

abstract class BaseCurrencyPlugin
{
    abstract public function getName(): string;

    abstract public function buildRatesRequest(): HttpRequest;

    /**
     * Returns rates normalized as USD per 1 unit of currency.
     *
     * @return array<string, float>
     */
    abstract public function parseRatesResponse(HttpResponse $response): array;
}

abstract class BaseProxyVpnPlugin
{
    abstract public function getName(): string;

    abstract public function buildDetectionRequest(string $ip, array $server): HttpRequest;

    abstract public function parseDetectionResponse(HttpResponse $response): ?bool;
}
