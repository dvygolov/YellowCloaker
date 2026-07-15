<?php

use PHPUnit\Framework\TestCase;

$GLOBALS['cloSettings'] = [
    'debug' => false,
];

require_once __DIR__ . '/../admin/accesscontrol.php';

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
}
