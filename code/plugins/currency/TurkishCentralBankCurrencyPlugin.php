<?php

require_once __DIR__ . '/../base.php';

class TurkishCentralBankCurrencyPlugin extends BaseCurrencyPlugin
{
    public function getName(): string
    {
        return 'turkish';
    }

    public function buildRatesRequest(): HttpRequest
    {
        return new HttpRequest($this->getName(), 'https://www.tcmb.gov.tr/kurlar/today.xml', timeout: 10, connectTimeout: 5);
    }

    public function parseRatesResponse(HttpResponse $response): array
    {
        if (!$response->isOk()) {
            throw new Exception("HTTP {$response->httpCode()}; curl {$response->errno} {$response->error}");
        }

        $xml = simplexml_load_string((string)$response->content);
        if ($xml === false) {
            throw new Exception('Invalid XML payload');
        }

        $rates = ['USD' => 1.0];
        foreach ($xml->Currency as $currency) {
            $code = strtoupper((string)$currency['CurrencyCode']);
            $crossRateUsd = (float)$currency->CrossRateUSD;
            if ($code === '' || $crossRateUsd <= 0) {
                continue;
            }
            $rates[$code] = 1.0 / $crossRateUsd;
        }

        return $rates;
    }
}
