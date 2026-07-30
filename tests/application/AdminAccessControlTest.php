<?php

use PHPUnit\Framework\TestCase;

$GLOBALS['cloSettings'] = [
    'debug' => false,
];

require_once __DIR__ . '/../../code/admin/accesscontrol.php';

class AdminAccessControlTest extends TestCase
{
    public function testCurrentDomainDoesNotIncludePort(): void
    {
        $server = ['SERVER_NAME' => 'admin.example.com:8443'];

        $this->assertSame('admin.example.com', get_admin_request_domain($server));
    }

    public function testDomainRestrictionUsesDetectedDomainWithoutPort(): void
    {
        $server = ['SERVER_NAME' => 'admin.example.com:8443'];
        $settings = ['adminDomain' => 'admin.example.com', 'adminIp' => ''];

        $this->assertNull(get_admin_access_error($server, $settings));
    }

    public function testProductionDenialDoesNotExposeDomainMismatch(): void
    {
        $error = get_admin_access_error(
            ['SERVER_NAME' => 'wrong.example.com'],
            ['adminDomain' => 'admin.example.com', 'adminIp' => '']
        );

        $this->assertNotNull($error);
        $response = get_admin_access_denial_response($error, false);

        $this->assertSame(404, $response['status']);
        $this->assertSame('Not Found', $response['body']);
        $this->assertStringNotContainsString('admin.example.com', $response['body']);
        $this->assertStringNotContainsString('wrong.example.com', $response['body']);
    }

    public function testProductionDenialDoesNotExposeIpMismatch(): void
    {
        $error = get_admin_access_error(
            ['REMOTE_ADDR' => '203.0.113.15'],
            ['adminDomain' => '', 'adminIp' => '198.51.100.10']
        );

        $this->assertNotNull($error);
        $response = get_admin_access_denial_response($error, false);

        $this->assertSame(404, $response['status']);
        $this->assertSame('Not Found', $response['body']);
        $this->assertStringNotContainsString('198.51.100.10', $response['body']);
        $this->assertStringNotContainsString('203.0.113.15', $response['body']);
    }

    public function testIpRestrictionAllowsAnyAddressInCommaSeparatedList(): void
    {
        $this->assertNull(get_admin_access_error(
            ['REMOTE_ADDR' => '203.0.113.15'],
            ['adminDomain' => '', 'adminIp' => '198.51.100.10, 203.0.113.15']
        ));
    }

    public function testDebugDenialKeepsDiagnosticMessage(): void
    {
        $error = 'Admin Domain mismatch details';

        $this->assertSame(
            ['status' => 200, 'body' => $error],
            get_admin_access_denial_response($error, true)
        );
    }
}
