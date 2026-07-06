<?php

require_once __DIR__ . '/../base.php';

class FrankfurterCurrencyPlugin extends BaseCurrencyPlugin
{
    public function getName(): string
    {
        return 'frankfurter';
    }

    public function buildRatesRequest(): PluginHttpRequest
    {
        return new PluginHttpRequest($this->getName(), 'https://api.frankfurter.dev/v1/latest?base=USD', 10, 5);
    }

    public function parseRatesResponse(PluginHttpResponse $response): array
    {
        if (!$response->isOk()) {
            throw new Exception("HTTP {$response->httpCode}; curl {$response->errno} {$response->error}");
        }

        $data = json_decode((string)$response->content, true);
        if (!is_array($data) || !isset($data['rates']) || !is_array($data['rates'])) {
            throw new Exception('Invalid JSON payload');
        }

        $rates = ['USD' => 1.0];
        foreach ($data['rates'] as $currency => $perUsd) {
            $currency = strtoupper((string)$currency);
            $perUsd = (float)$perUsd;
            if ($currency === '' || $perUsd <= 0) {
                continue;
            }
            $rates[$currency] = 1.0 / $perUsd;
        }

        return $rates;
    }
}
