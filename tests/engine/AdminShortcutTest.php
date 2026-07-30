<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/admin/accesscontrol.php';

class AdminShortcutTest extends TestCase
{
    private const ADMIN_IP = '8.8.8.8';

    private function server(array $overrides = []): array
    {
        return array_merge([
            'REMOTE_ADDR' => self::ADMIN_IP,
            'SCRIPT_NAME' => '/admin.php',
        ], $overrides);
    }

    private function settings(array $overrides = []): array
    {
        return array_merge([
            'adminIp' => self::ADMIN_IP,
            'adminPath' => 'e3c80abc',
        ], $overrides);
    }

    public function testRedirectsMatchingAdminIpToConfiguredPath(): void
    {
        $this->assertSame(
            '/e3c80abc/',
            get_admin_shortcut_redirect($this->server(), $this->settings())
        );
    }

    public function testRedirectsWhenAdminIpMatchesAnyConfiguredIp(): void
    {
        $this->assertSame(
            '/e3c80abc/',
            get_admin_shortcut_redirect(
                $this->server(),
                $this->settings(['adminIp' => '198.51.100.10, ' . self::ADMIN_IP])
            )
        );
    }

    public function testPreservesApplicationSubdirectoryInRedirect(): void
    {
        $this->assertSame(
            '/tds/e3c80abc/',
            get_admin_shortcut_redirect(
                $this->server(['SCRIPT_NAME' => '/tds/admin.php']),
                $this->settings()
            )
        );
    }

    public function testDoesNotRedirectWhenAdminIpIsDisabled(): void
    {
        $this->assertNull(
            get_admin_shortcut_redirect($this->server(), $this->settings(['adminIp' => '']))
        );
    }

    public function testDoesNotRedirectWhenAdminIpDoesNotMatch(): void
    {
        $this->assertNull(
            get_admin_shortcut_redirect(
                $this->server(['REMOTE_ADDR' => '198.51.100.10']),
                $this->settings()
            )
        );
    }

    public function testDoesNotRedirectWhenAdminPathIsMissing(): void
    {
        $settings = $this->settings();
        unset($settings['adminPath']);

        $this->assertNull(get_admin_shortcut_redirect($this->server(), $settings));
    }

    public function testDoesNotRedirectWhenAdminPathIsEmptyOrInvalid(): void
    {
        $this->assertNull(
            get_admin_shortcut_redirect($this->server(), $this->settings(['adminPath' => '']))
        );
        $this->assertNull(
            get_admin_shortcut_redirect($this->server(), $this->settings(['adminPath' => '../admin']))
        );
    }

    public function testUsesTrustedCloudflareVisitorIp(): void
    {
        $server = $this->server([
            'REMOTE_ADDR' => '104.16.1.1',
            'HTTP_CF_CONNECTING_IP' => self::ADMIN_IP,
        ]);
        $cloudflareChecker = static fn(string $ip): bool => $ip === '104.16.1.1';

        $this->assertSame(
            '/e3c80abc/',
            get_admin_shortcut_redirect($server, $this->settings(), $cloudflareChecker)
        );
    }

    public function testIgnoresSpoofedCloudflareVisitorIp(): void
    {
        $server = $this->server([
            'REMOTE_ADDR' => '198.51.100.10',
            'HTTP_CF_CONNECTING_IP' => self::ADMIN_IP,
        ]);
        $cloudflareChecker = static fn(string $ip): bool => false;

        $this->assertNull(
            get_admin_shortcut_redirect($server, $this->settings(), $cloudflareChecker)
        );
    }
}
