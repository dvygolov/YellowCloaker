<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/paths.php';

class AdminPathTest extends TestCase
{
    private string $originalAdminPath;

    protected function setUp(): void
    {
        $this->originalAdminPath = (string)($GLOBALS['cloSettings']['adminPath'] ?? 'admin');
    }

    protected function tearDown(): void
    {
        $GLOBALS['cloSettings']['adminPath'] = $this->originalAdminPath;
    }

    public function testDefaultAdminPathIsAdmin(): void
    {
        unset($GLOBALS['cloSettings']['adminPath']);

        $this->assertSame('admin', get_admin_path_segment());
    }

    public function testValidCustomAdminPathIsReturned(): void
    {
        $GLOBALS['cloSettings']['adminPath'] = 'e3c80abc';

        $this->assertSame('e3c80abc', get_admin_path_segment());
    }

    public function testInvalidAdminPathFallsBackToAdmin(): void
    {
        $GLOBALS['cloSettings']['adminPath'] = '../admin';

        $this->assertSame('admin', get_admin_path_segment());
    }

    public function testAdminRequestPathMatchesDefaultAndCustomPaths(): void
    {
        $GLOBALS['cloSettings']['adminPath'] = 'admin';
        $this->assertTrue(is_admin_request_path('/admin'));
        $this->assertTrue(is_admin_request_path('/admin/index.php'));
        $this->assertFalse(is_admin_request_path('/administrator'));

        $GLOBALS['cloSettings']['adminPath'] = 'e3c80abc';
        $this->assertTrue(is_admin_request_path('/e3c80abc'));
        $this->assertTrue(is_admin_request_path('/e3c80abc/login.php'));
        $this->assertFalse(is_admin_request_path('/admin'));
    }

    public function testAdminDirUsesConfiguredPathUnderAppRoot(): void
    {
        $GLOBALS['cloSettings']['adminPath'] = 'e3c80abc';

        $this->assertSame(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'code' . DIRECTORY_SEPARATOR . 'e3c80abc',
            get_admin_dir()
        );
    }
}
