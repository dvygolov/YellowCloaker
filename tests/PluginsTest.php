<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../currency.php';
require_once __DIR__ . '/../proxyvpn.php';

class PluginsTest extends TestCase
{
    public function testCurrencyMergeKeepsFirstSourceValues(): void
    {
        $rates = CurrencyRateManager::mergeRates(
            [
                'first' => ['EUR' => 1.1, 'RUB' => 0.01],
                'second' => ['EUR' => 1.2, 'THB' => 0.03],
            ],
            [
                'first' => [],
                'second' => [],
            ]
        );

        $this->assertSame(1.1, $rates['EUR']);
        $this->assertSame(0.01, $rates['RUB']);
        $this->assertSame(0.03, $rates['THB']);
    }

    public function testCurrencyPreferredSourceOverridesDuplicateCurrency(): void
    {
        $rates = CurrencyRateManager::mergeRates(
            [
                'frankfurter' => ['RUB' => 0.011],
                'turkish' => ['RUB' => 0.012],
            ],
            [
                'frankfurter' => [],
                'turkish' => ['RUB'],
            ]
        );

        $this->assertSame(0.012, $rates['RUB']);
    }

    public function testProxyVpnDecisionModes(): void
    {
        $this->assertTrue(ProxyVpnDetector::decide('any', 1, 2));
        $this->assertFalse(ProxyVpnDetector::decide('any', 0, 2));
        $this->assertFalse(ProxyVpnDetector::decide('most', 1, 2));
        $this->assertTrue(ProxyVpnDetector::decide('most', 2, 3));
        $this->assertTrue(ProxyVpnDetector::decide('most', 0, 0));
    }

    public function testXffOnlyDetectsPositiveConflict(): void
    {
        $this->assertTrue(ProxyVpnDetector::xffConflictDetected('1.1.1.1', ['HTTP_X_FORWARDED_FOR' => '2.2.2.2']));
        $this->assertFalse(ProxyVpnDetector::xffConflictDetected('1.1.1.1', ['HTTP_X_FORWARDED_FOR' => '1.1.1.1']));
        $this->assertFalse(ProxyVpnDetector::xffConflictDetected('1.1.1.1', []));
    }
}
