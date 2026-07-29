<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestDb.php';

final class RuntimeCampaignCacheTest extends TestCase
{
    private string $dbPath;
    private string $cacheRoot;
    private TestDb $db;

    protected function setUp(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $this->dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ywb_runtime_' . $suffix . '.db';
        $this->cacheRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ywb_runtime_cache_' . $suffix;
        mkdir($this->cacheRoot, 0755, true);
        $GLOBALS['cloSettings']['cachingDir'] = $this->cacheRoot;
        $this->db = new TestDb($this->dbPath);
        $this->db->initSchema();
    }

    protected function tearDown(): void
    {
        $this->db->cleanup();
        $this->removeDirectory($this->cacheRoot);
    }

    public function testExactAndWildcardDomainsLoadGeneratedCampaignSnapshots(): void
    {
        $exactId = $this->createCampaign('Exact', ['Example.COM']);
        $wildcardId = $this->createCampaign('Wildcard', ['*.Sub.Example.COM']);
        $this->assertTrue($this->db->rebuild_runtime_cache());

        $runtime = $this->cacheRoot . DIRECTORY_SEPARATOR . 'runtime';
        $this->assertFileExists($runtime . DIRECTORY_SEPARATOR . 'domains.php');
        $this->assertFileExists($runtime . DIRECTORY_SEPARATOR . 'campaign-' . $exactId . '.php');
        $this->assertFileExists($runtime . DIRECTORY_SEPARATOR . 'campaign-' . $wildcardId . '.php');

        ob_start();
        $domainIndex = include $runtime . DIRECTORY_SEPARATOR . 'domains.php';
        $output = ob_get_clean();
        $this->assertSame('', $output);
        $this->assertSame($exactId, $domainIndex['exact']['example.com']);
        $this->assertSame($wildcardId, $domainIndex['wildcard']['sub.example.com']);

        $this->setRequestHost('example.com');
        $this->assertSame($exactId, $this->db->get_campaign_by_domain()['id']);
        $this->setRequestHost('a.b.sub.example.com');
        $this->assertSame($wildcardId, $this->db->get_campaign_by_domain()['id']);
        $this->setRequestHost('sub.example.com');
        $this->assertFalse($this->db->get_campaign_by_domain());
    }

    public function testMissingCampaignSnapshotFallsBackToSqlite(): void
    {
        $campaignId = $this->createCampaign('Fallback', ['fallback.example.com']);
        $this->assertTrue($this->db->rebuild_runtime_cache());
        unlink($this->cacheRoot . '/runtime/campaign-' . $campaignId . '.php');

        $this->setRequestHost('fallback.example.com');
        $campaign = $this->db->get_campaign_by_domain();
        $this->assertIsArray($campaign);
        $this->assertSame($campaignId, $campaign['id']);
    }

    public function testOverlappingDomainsAreRejectedWithCampaignNames(): void
    {
        $this->createCampaign('Wildcard owner', ['*.example.com']);
        $otherId = $this->createCampaign('Exact owner', []);
        $settings = $this->db->get_campaign_settings($otherId);
        $settings['domains'] = ['shop.example.com'];

        try {
            $this->db->save_campaign_settings($otherId, $settings);
            $this->fail('Expected a campaign domain conflict.');
        } catch (CampaignDomainException $e) {
            $this->assertStringContainsString('Wildcard owner', $e->getMessage());
            $this->assertStringContainsString('Exact owner', $e->getMessage());
        }
        $this->assertSame([], $this->db->get_campaign_settings($otherId)['domains']);
    }

    public function testCloneStartsWithoutDomains(): void
    {
        $sourceId = $this->createCampaign('Source', ['source.example.com']);
        $cloneId = $this->db->clone_campaign($sourceId, false);

        $this->assertIsInt($cloneId);
        $this->assertSame([], $this->db->get_campaign_settings($cloneId)['domains']);
        $this->assertFalse($this->db->get_campaign_settings($cloneId)['white']['domainfilter']['use']);
    }

    private function createCampaign(string $name, array $domains): int
    {
        $campaignId = $this->db->add_campaign($name, false);
        $this->assertIsInt($campaignId);
        $settings = $this->db->get_campaign_settings($campaignId);
        $settings['domains'] = $domains;
        $this->assertTrue($this->db->save_campaign_settings($campaignId, $settings, false));
        return $campaignId;
    }

    private function setRequestHost(string $host): void
    {
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['SERVER_PORT'] = 80;
        unset($_SERVER['HTTP_X_FORWARDED_HOST'], $_SERVER['HTTP_X_FORWARDED_PORT']);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (new DirectoryIterator($path) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}
