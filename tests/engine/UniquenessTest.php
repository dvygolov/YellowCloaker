<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestDb.php';

final class UniquenessTest extends TestCase
{
    private TestDb $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/yellowtds_unique_' . uniqid('', true) . '.db';
        $this->db = new TestDb($this->dbPath);
        $this->db->initSchema();
        $this->db->seedCampaign();
        $GLOBALS['db'] = $this->db;
        $_COOKIE = [];
        $_SERVER['QUERY_STRING'] = '';
    }

    protected function tearDown(): void
    {
        $this->db->cleanup();
        $_COOKIE = [];
    }

    public function testSettingsDefaultsAndBounds(): void
    {
        $defaults = UniquenessSettings::fromArray([]);
        $this->assertFalse($defaults->enabled);
        $this->assertSame('cookie_ip_ua', $defaults->method);
        $this->assertSame(24, $defaults->ttlHours);

        $bounded = UniquenessSettings::fromArray([
            'enabled' => true,
            'method' => 'get',
            'ttl_hours' => 999,
            'get_parameter' => 'visitor',
        ]);
        $this->assertTrue($bounded->enabled);
        $this->assertSame(720, $bounded->ttlHours);
        $this->assertSame('visitor', $bounded->getParameter);
    }

    public function testAllIdentityMethods(): void
    {
        $click = $this->clickParams();

        $ip = $this->identity('ip', $click);
        $this->assertSame(16, strlen($ip->hash));
        $this->assertSame('', $ip->userid);

        $ipUa = $this->identity('ip_ua', $click);
        $this->assertNotSame($ip->hash, $ipUa->hash);

        $cookie = $this->identity('cookie', $click, 'cookie-a');
        $this->assertNull($cookie->hash);
        $this->assertTrue($cookie->lookupByUserid);
        $this->assertSame('cookie-a', $cookie->userid);

        $cookieIp = $this->identity('cookie_ip', $click);
        $this->assertFalse($cookieIp->lookupByUserid);
        $this->assertSame($ip->hash, $cookieIp->hash);
        $this->assertNotSame('', $cookieIp->userid);

        $cookieIpUa = $this->identity('cookie_ip_ua', $click, 'cookie-b');
        $this->assertTrue($cookieIpUa->lookupByUserid);
        $this->assertSame($ipUa->hash, $cookieIpUa->hash);
    }

    public function testGetCanonicalizationCoversEmptyListsMapsAndMissingValues(): void
    {
        $base = $this->clickParams();
        $emptyString = $base;
        $emptyString['qs'] = ['visitor' => ''];
        $emptyIdentity = $this->identity('get', $emptyString, '', 'visitor');
        $this->assertNotNull($emptyIdentity->hash);

        $listA = $base;
        $listA['qs'] = ['visitor' => ['a', 'b']];
        $listB = $base;
        $listB['qs'] = ['visitor' => ['b', 'a']];
        $this->assertNotSame(
            $this->identity('get', $listA, '', 'visitor')->hash,
            $this->identity('get', $listB, '', 'visitor')->hash
        );

        $mapA = $base;
        $mapA['qs'] = ['visitor' => ['z' => ['b' => '2', 'a' => '1'], 'a' => []]];
        $mapB = $base;
        $mapB['qs'] = ['visitor' => ['a' => [], 'z' => ['a' => '1', 'b' => '2']]];
        $this->assertSame(
            $this->identity('get', $mapA, '', 'visitor')->hash,
            $this->identity('get', $mapB, '', 'visitor')->hash
        );

        $missing = $this->identity('get', $base, '', 'visitor');
        $this->assertTrue($missing->missingGetParameter);
        $this->assertNull($missing->hash);
    }

    public function testExactTtlBoundaryAndSlidingWindow(): void
    {
        $identity = $this->identity('ip', $this->clickParams());
        $now = 2_000_000_000;
        $cutoff = $now - 3600;
        $this->db->seedClicks([
            ['clickid' => 'boundary', 'time' => $cutoff, 'unique_hash' => $identity->hash, 'unique_flags' => 3],
        ]);

        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READWRITE);
        $this->assertTrue(UniquenessService::isUnique($connection, 1, null, $identity, $cutoff));

        $this->db->seedClicks([
            ['clickid' => 'inside', 'time' => $cutoff + 1, 'unique_hash' => $identity->hash, 'unique_flags' => 0],
        ]);
        $this->assertFalse(UniquenessService::isUnique($connection, 1, null, $identity, $cutoff));

        $this->db->seedClicks([
            ['clickid' => 'latest-non-unique', 'time' => $now - 10, 'unique_hash' => $identity->hash, 'unique_flags' => 0],
        ]);
        $this->assertFalse(UniquenessService::isUnique($connection, 1, null, $identity, $now - 20));
        $connection->close();
    }

    public function testCombinedLookupReturnsCampaignAndFlowScopes(): void
    {
        $identity = $this->identity('ip', $this->clickParams());
        $this->db->seedClicks([[
            'clickid' => 'other-flow',
            'flow' => 'Other Flow',
            'time' => time(),
            'unique_hash' => $identity->hash,
            'unique_flags' => 3,
        ]]);
        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READWRITE);

        $this->assertSame(
            [false, true],
            UniquenessService::bothScopesAreUnique(
                $connection,
                1,
                'Flow 1',
                $identity,
                time() - 60
            )
        );

        $this->db->seedClicks([[
            'clickid' => 'same-flow',
            'flow' => 'Flow 1',
            'time' => time(),
            'unique_hash' => $identity->hash,
            'unique_flags' => 0,
        ]]);
        $this->assertSame(
            [false, false],
            UniquenessService::bothScopesAreUnique(
                $connection,
                1,
                'Flow 1',
                $identity,
                time() - 60
            )
        );
        $connection->close();
    }

    public function testCookiePrimaryAndFallbackAfterCookieLoss(): void
    {
        $click = $this->clickParams();
        $stored = $this->identity('cookie_ip', $click, 'known-cookie');
        $this->db->seedClicks([
            [
                'clickid' => 'stored',
                'userid' => 'known-cookie',
                'unique_hash' => $stored->hash,
                'unique_flags' => 3,
                'time' => time(),
            ],
        ]);
        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READWRITE);

        $sameCookie = $this->identity('cookie_ip', $click, 'known-cookie');
        $this->assertFalse(UniquenessService::isUnique($connection, 1, null, $sameCookie, time() - 60));

        $differentCookie = $this->identity('cookie_ip', $click, 'different-cookie');
        $this->assertTrue(UniquenessService::isUnique($connection, 1, null, $differentCookie, time() - 60));

        $lostCookie = $this->identity('cookie_ip', $click);
        $this->assertFalse(UniquenessService::isUnique($connection, 1, null, $lostCookie, time() - 60));
        $connection->close();
    }

    public function testUniquenessFilterUsesRequestedScopeAndOperator(): void
    {
        $core = $this->core($this->clickParams());
        $scopes = [];
        $rules = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'uniqueness',
                'field' => 'uniqueness',
                'operator' => 'is_not_unique',
                'value' => 'flow',
            ]],
        ];

        $matched = $core->click_matches_filters($rules, function (string $scope) use (&$scopes): bool {
            $scopes[] = $scope;
            return false;
        });
        $this->assertTrue($matched);
        $this->assertSame(['flow'], $scopes);

        $campaignRules = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'uniqueness',
                'field' => 'uniqueness',
                'operator' => 'is_unique',
                'value' => 'campaign',
            ]],
        ];
        $this->assertTrue($core->click_matches_filters(
            $campaignRules,
            static fn(string $scope): bool => $scope === 'campaign'
        ));
    }

    public function testAtomicPathRecordsMasksAndFirstStep(): void
    {
        $campaign = $this->campaign('ip', [
            $this->flow('Flow 1', [], [
                $this->redirectStep('https://example.com/one'),
                $this->redirectStep('https://example.com/two'),
            ]),
        ]);

        $first = black_unique($campaign, $this->core($this->clickParams()));
        $second = black_unique($campaign, $this->core($this->clickParams()));
        $this->assertSame('redirect', $first?->action);
        $this->assertSame('redirect', $second?->action);

        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $rows = [];
        $result = $connection->query('SELECT clickid, unique_flags, path FROM clicks ORDER BY id');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        $this->assertSame([3, 0], array_map('intval', array_column($rows, 'unique_flags')));
        $this->assertCount(2, json_decode($rows[0]['path'], true));
        $this->assertNotSame($rows[0]['clickid'], $rows[1]['clickid']);

        $steps = $connection->querySingle('SELECT COUNT(*) FROM click_steps');
        $this->assertSame(2, (int)$steps);
        $joined = $connection->querySingle(
            'SELECT COUNT(*) FROM click_steps cs JOIN clicks c ON c.clickid = cs.clickid'
        );
        $this->assertSame(2, (int)$joined);
        $connection->close();
    }

    public function testMissingGetParameterRecordsBothUniqueWithNullHash(): void
    {
        $campaign = $this->campaign('get', [$this->flow('Flow 1')], 'visitor');
        $action = black_unique($campaign, $this->core($this->clickParams()));
        $this->assertNotNull($action);

        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $row = $connection->querySingle('SELECT unique_hash, unique_flags FROM clicks LIMIT 1', true);
        $this->assertNull($row['unique_hash']);
        $this->assertSame(3, (int)$row['unique_flags']);
        $connection->close();
    }

    public function testNoMatchingFlowRollsBackClickAndStep(): void
    {
        $filters = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'uniqueness',
                'field' => 'uniqueness',
                'operator' => 'is_not_unique',
                'value' => 'campaign',
            ]],
        ];
        $campaign = $this->campaign('ip', [$this->flow('Only non-unique', $filters)]);
        $this->assertNull(black_unique($campaign, $this->core($this->clickParams())));

        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $this->assertSame(0, (int)$connection->querySingle('SELECT COUNT(*) FROM clicks'));
        $this->assertSame(0, (int)$connection->querySingle('SELECT COUNT(*) FROM click_steps'));
        $connection->close();
    }

    public function testWriteFailureRollsBackAndReturnsGenericHttp500Action(): void
    {
        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READWRITE);
        $this->assertTrue($connection->exec(
            "CREATE TRIGGER fail_unique_step BEFORE INSERT ON click_steps "
            . "BEGIN SELECT RAISE(ABORT, 'forced step failure'); END"
        ));
        $connection->close();

        $campaign = $this->campaign('ip', [$this->flow('Flow 1')]);
        $action = black_unique($campaign, $this->core($this->clickParams()));

        $this->assertNotNull($action);
        $this->assertSame('error', $action->action);
        $this->assertSame('500', $action->value);
        $this->assertGreaterThan(0, $this->db->last_write_error_code());
        $this->assertStringContainsString('forced step failure', $this->db->last_write_error_message());

        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $this->assertSame(0, (int)$connection->querySingle('SELECT COUNT(*) FROM clicks'));
        $this->assertSame(0, (int)$connection->querySingle('SELECT COUNT(*) FROM click_steps'));
        $connection->close();
    }

    public function testBitStatisticsAndMixedGroups(): void
    {
        $this->db->seedClicks([
            ['clickid' => 'm0', 'unique_flags' => 0],
            ['clickid' => 'm1', 'unique_flags' => 1],
            ['clickid' => 'm2', 'unique_flags' => 2],
            ['clickid' => 'm3', 'unique_flags' => 3],
        ]);
        $stats = $this->db->get_statistics(
            ['clicks', 'uniques', 'flow_uniques', 'uniques_ratio'],
            [],
            1,
            '0',
            '9999999999',
            'UTC'
        );
        $this->assertSame(2, (int)$stats[0]['uniques']);
        $this->assertSame(2, (int)$stats[0]['flow_uniques']);
        $this->assertSame(50.0, (float)$stats[0]['uniques_ratio']);

        $this->db->seedClicks([['clickid' => 'legacy', 'unique_flags' => null]]);
        $mixed = $this->db->get_statistics(
            ['clicks', 'uniques', 'flow_uniques', 'uniques_ratio', 'revenue', 'uepc', 'costs', 'ucpc'],
            [],
            1,
            '0',
            '9999999999',
            'UTC'
        );
        foreach (['uniques', 'flow_uniques', 'uniques_ratio', 'uepc', 'ucpc'] as $field) {
            $this->assertNull($mixed[0][$field]);
        }
    }

    public function testTwoConcurrentWritersProduceExactlyOneUniqueClick(): void
    {
        $barrier = sys_get_temp_dir() . '/yellowtds_unique_barrier_' . uniqid('', true);
        $worker = __DIR__ . '/fixtures/uniqueness_worker.php';
        $hash = base64_encode($this->identity('ip', $this->clickParams())->hash);
        $processes = [];

        try {
            for ($workerId = 1; $workerId <= 2; $workerId++) {
                $command = [PHP_BINARY, $worker, $this->dbPath, $barrier, $hash, (string)$workerId];
                $pipes = [];
                $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                $this->assertIsResource($process);
                $processes[] = [$process, $pipes];
            }
            touch($barrier);

            $flags = [];
            foreach ($processes as [$process, $pipes]) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
                $this->assertSame(0, $exitCode, $stderr);
                $this->assertMatchesRegularExpression('/FLAG=([03])/', $stdout);
                preg_match('/FLAG=([03])/', $stdout, $matches);
                $flags[] = (int)$matches[1];
            }
            sort($flags);
            $this->assertSame([0, 3], $flags);
        } finally {
            @unlink($barrier);
        }
    }

    private function identity(
        string $method,
        array $click,
        string $userid = '',
        string $getParameter = ''
    ): UniquenessIdentity {
        return UniquenessService::prepareIdentity(
            UniquenessSettings::fromArray([
                'enabled' => true,
                'method' => $method,
                'ttl_hours' => 1,
                'get_parameter' => $getParameter,
            ]),
            $click,
            $userid
        );
    }

    private function clickParams(): array
    {
        return [
            'ip' => '2001:0db8:0:0:0:0:0:1',
            'country' => 'US',
            'lang' => 'en',
            'os' => 'Windows',
            'osver' => '11',
            'client' => 'Chrome',
            'clientver' => '126',
            'device' => 'desktop',
            'brand' => '',
            'model' => '',
            'isp' => 'Example ISP',
            'ua' => 'Full User Agent/1.0',
            'qs' => [],
        ];
    }

    private function core(array $click): FiltrationCore
    {
        $reflection = new ReflectionClass(FiltrationCore::class);
        $core = $reflection->newInstanceWithoutConstructor();
        $core->click_params = $click;
        return $core;
    }

    private function campaign(string $method, array $flows, string $getParameter = ''): Campaign
    {
        $settings = json_decode(file_get_contents(__DIR__ . '/../../code/db/default.json'), true);
        $settings['uniqueness'] = [
            'enabled' => true,
            'method' => $method,
            'ttl_hours' => 24,
            'get_parameter' => $getParameter,
        ];
        $settings['black']['flows'] = $flows;
        return new Campaign(1, $settings);
    }

    private function flow(string $name, array $filters = [], ?array $steps = null): array
    {
        return [
            'name' => $name,
            'filters' => $filters,
            'distribution' => 'equal',
            'optimize_for' => 'Lead',
            'optimize_mode' => 'funnels',
            'steps' => $steps ?? [$this->redirectStep('https://example.com')],
        ];
    }

    private function redirectStep(string $url): array
    {
        return [
            'action' => 'redirect',
            'folders' => [],
            'redirect' => [
                'urls' => [[
                    'url' => $url,
                    'label' => parse_url($url, PHP_URL_HOST),
                    'weight' => 100,
                ]],
                'type' => 302,
            ],
        ];
    }
}
