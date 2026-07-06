<?php

require_once __DIR__ . '/currency/FrankfurterCurrencyPlugin.php';
require_once __DIR__ . '/currency/TurkishCentralBankCurrencyPlugin.php';
require_once __DIR__ . '/proxyvpn/BlackboxProxyVpnPlugin.php';
require_once __DIR__ . '/proxyvpn/GetIpIntelProxyVpnPlugin.php';

class PluginRegistry
{
    /** @return array<string, BaseCurrencyPlugin> */
    public static function currencyPlugins(): array
    {
        return [
            'frankfurter' => new FrankfurterCurrencyPlugin(),
            'turkish' => new TurkishCentralBankCurrencyPlugin(),
        ];
    }

    /** @return array<string, BaseProxyVpnPlugin> */
    public static function proxyVpnPlugins(): array
    {
        return [
            'blackbox' => new BlackboxProxyVpnPlugin(),
            'ipintel' => new GetIpIntelProxyVpnPlugin(),
        ];
    }
}
