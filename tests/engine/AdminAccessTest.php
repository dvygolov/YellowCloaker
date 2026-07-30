<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/admin/accesscontrol.php';

class AdminAccessTest extends TestCase
{
    public function testAllowsWhenNoRestrictionsAreSet(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '203.0.113.15',
        ];
        $settings = [
            'adminDomain' => '',
            'adminIp' => '',
        ];

        $this->assertNull(get_admin_access_error($server, $settings));
    }

    public function testBlocksWrongAdminDomain(): void
    {
        $server = [
            'SERVER_NAME' => 'wrong.example.com',
            'REMOTE_ADDR' => '203.0.113.15',
        ];
        $settings = [
            'adminDomain' => 'admin.example.com',
            'adminIp' => '',
        ];

        $this->assertSame(
            'Admin Domain admin.example.com is set, but your domain is wrong.example.com. You are not allowed to access this page!',
            get_admin_access_error($server, $settings)
        );
    }

    public function testBlocksWrongAdminIp(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '203.0.113.15',
        ];
        $settings = [
            'adminDomain' => '',
            'adminIp' => '198.51.100.10',
        ];

        $this->assertSame(
            'Admin IPs 198.51.100.10 are set, but your IP is 203.0.113.15. You are not allowed to access this page!',
            get_admin_access_error($server, $settings)
        );
    }

    public function testAllowsAnyConfiguredAdminIp(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '203.0.113.15',
        ];
        $settings = [
            'adminDomain' => '',
            'adminIp' => '198.51.100.10, 203.0.113.15, 2001:db8::10',
        ];

        $this->assertNull(get_admin_access_error($server, $settings));
        $this->assertSame(
            ['198.51.100.10', '203.0.113.15', '2001:db8::10'],
            get_allowed_admin_ips($settings)
        );
    }

    public function testUsesCfConnectingIpOnlyForRealCloudflareProxy(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '104.16.1.1',
            'HTTP_CF_CONNECTING_IP' => '8.8.8.8',
        ];
        $settings = [
            'adminDomain' => '',
            'adminIp' => '8.8.8.8',
        ];
        $cloudflareChecker = static fn(string $ip): bool => $ip === '104.16.1.1';

        $this->assertNull(get_admin_access_error($server, $settings, $cloudflareChecker));
        $this->assertSame('8.8.8.8', get_admin_request_ip($server, $cloudflareChecker));
    }

    public function testIgnoresSpoofedCfConnectingIpOutsideCloudflare(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '203.0.113.15',
            'HTTP_CF_CONNECTING_IP' => '8.8.8.8',
        ];
        $settings = [
            'adminDomain' => '',
            'adminIp' => '8.8.8.8',
        ];
        $cloudflareChecker = static fn(string $ip): bool => false;

        $this->assertSame('203.0.113.15', get_admin_request_ip($server, $cloudflareChecker));
        $this->assertSame(
            'Admin IPs 8.8.8.8 are set, but your IP is 203.0.113.15. You are not allowed to access this page!',
            get_admin_access_error($server, $settings, $cloudflareChecker)
        );
    }

    public function testSupportsIpv6CloudflareRanges(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '2606:4700::1111',
            'HTTP_CF_CONNECTING_IP' => '2001:4860:4860::8888',
        ];
        $cloudflareChecker = static fn(string $ip): bool => $ip === '2606:4700::1111';

        $this->assertSame('2001:4860:4860::8888', get_admin_request_ip($server, $cloudflareChecker));
    }

    public function testAdminRequestIpKeepsLoopbackWithoutDebugFallback(): void
    {
        $server = [
            'SERVER_NAME' => 'admin.example.com',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_CF_CONNECTING_IP' => '8.8.8.8',
        ];
        $cloudflareChecker = static fn(string $ip): bool => false;

        $this->assertSame('127.0.0.1', get_admin_request_ip($server, $cloudflareChecker));
    }
}
