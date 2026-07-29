<?php

use PHPUnit\Framework\TestCase;

$GLOBALS['cloSettings'] = [
    'debug' => false,
];

require_once __DIR__ . '/../../code/bases/ipcountry.php';

class GeoBasesTest extends TestCase
{
    public function testCountryCodeUsesSapicsFlatCountryCode(): void
    {
        $this->assertSame('US', get_country_code_from_record(['country_code' => 'US']));
    }

    public function testAsnOrganizationUsesSapicsOriginAsnField(): void
    {
        $this->assertSame(
            'Cloudflare, Inc.',
            get_asn_organization_from_record(['autonomous_system_organization' => 'Cloudflare, Inc.'])
        );
    }

    public function testMissingDatabaseMessagePointsToUpdater(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../../code/bases/ipcountry.php');

        $this->assertStringContainsString('Run bases/update.php', $source);
        $this->assertStringNotContainsString('maxMindKey', $source);
    }
}
