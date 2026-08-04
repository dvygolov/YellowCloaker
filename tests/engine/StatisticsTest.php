<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestDb.php';
require_once __DIR__ . '/../../code/admin/tablecolumns.php';

class StatisticsTest extends TestCase
{
    private TestDb $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/yellowtds_test_' . uniqid() . '.db';
        $this->db = new TestDb($this->dbPath);
        $this->db->initSchema();
        $this->db->seedCampaign(1, 'Test Campaign');
    }

    protected function tearDown(): void
    {
        $this->db->cleanup();
    }

    // ─── Helper ───────────────────────────────────────────────

    private function allFields(): array
    {
        return [
            'clicks', 'uniques', 'uniques_ratio',
            'cra', 'epc', 'uepc', 'conversion', 'purchase', 'hold',
            'reject', 'trash', 'revenue', 'costs', 'profit', 'roi',
            'cpc', 'ucpc', 'ec', 'cpa',
        ];
    }

    private function stat(
        array $fields = [],
        array $groupBy = [],
        string $tz = 'UTC',
        array $mvtGrouping = []
    ): array
    {
        return $this->db->get_statistics(
            $fields ?: $this->allFields(),
            $groupBy,
            1,
            '0',
            '9999999999',
            $tz,
            [],
            [],
            $mvtGrouping
        );
    }

    // ─── Empty DB ─────────────────────────────────────────────

    public function testEmptyDbReturnsEmptyArray(): void
    {
        $result = $this->stat();
        // No clicks → single row with all zeros
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(0, $result[0]['clicks']);
        $this->assertEquals(0, $result[0]['revenue']);
    }

    // ─── Basic counts ─────────────────────────────────────────

    public function testBasicClickCount(): void
    {
        $this->db->seedClicks([
            ['userid' => 'aaa', 'clickid' => 'c1'],
            ['userid' => 'bbb', 'clickid' => 'c2'],
            ['userid' => 'ccc', 'clickid' => 'c3'],
        ]);

        $result = $this->stat(['clicks', 'uniques']);
        $this->assertEquals(3, $result[0]['clicks']);
        $this->assertEquals(3, $result[0]['uniques']);
    }

    public function testMvtCombinationAndSingleTestGrouping(): void
    {
        $this->db->seedClicks([
            [
                'clickid' => 'm1',
                'path' => ['landing-a'],
                'step_mvt' => [['1' => 'A', '2' => 'A']],
                'status' => 'Purchase',
            ],
            [
                'clickid' => 'm2',
                'path' => ['landing-a'],
                'step_mvt' => [['1' => 'A', '2' => 'B']],
            ],
            [
                'clickid' => 'm3',
                'path' => ['landing-a'],
                'step_mvt' => [['1' => 'B', '2' => 'A']],
            ],
            [
                'clickid' => 'm4',
                'path' => ['landing-b'],
                'step_mvt' => [['1' => 'A', '2' => 'A']],
            ],
        ]);
        $placement = [
            'flow' => 'Flow 1',
            'step' => 0,
            'landing' => 'landing-a',
            'test' => 0,
        ];

        $combinations = $this->stat(
            ['clicks', 'conversion', 'cra'],
            [],
            'UTC',
            $placement
        );
        $this->assertSame(['AA', 'AB', 'BA'], array_column($combinations, 'group'));
        $this->assertSame([1, 1, 1], array_column($combinations, 'clicks'));
        $this->assertSame(1, $combinations[0]['conversion']);
        $this->assertSame(100.0, $combinations[0]['cra']);

        $placement['test'] = 1;
        $testOne = $this->stat(['clicks'], [], 'UTC', $placement);
        $this->assertSame(['A', 'B'], array_column($testOne, 'group'));
        $this->assertSame([2, 1], array_column($testOne, 'clicks'));
    }

    public function testMvtSelectedTestsCreateNestedGroupsInTheConfiguredOrder(): void
    {
        $this->db->seedClicks([
            ['clickid' => 'm1', 'path' => ['landing-a'], 'step_mvt' => [['1' => 'A', '2' => 'A']]],
            ['clickid' => 'm2', 'path' => ['landing-a'], 'step_mvt' => [['1' => 'A', '2' => 'B']]],
            ['clickid' => 'm3', 'path' => ['landing-a'], 'step_mvt' => [['1' => 'B', '2' => 'A']]],
        ]);
        $placement = [
            'flow' => 'Flow 1',
            'step' => 0,
            'landing' => 'landing-a',
            'tests' => [1, 2],
        ];

        $result = $this->stat(['clicks'], ['mvt'], 'UTC', $placement);

        $this->assertSame(['A', 'B'], array_column($result, 'group'));
        $this->assertSame(['A', 'B'], array_column($result[0]['_children'], 'group'));
        $this->assertSame(['A'], array_column($result[1]['_children'], 'group'));
        $this->assertSame([1, 1], array_column($result[0]['_children'], 'clicks'));
    }

    public function testMvtConversionOnlyCountsReachedPlacementBeforeConversionStep(): void
    {
        $this->db->seedClicks([
            [
                'clickid' => 'funnel-mvt',
                'path' => ['preland', 'landing-a'],
                'step' => 1,
                'step_mvt' => [[], ['1' => 'A']],
            ],
        ]);
        $this->db->seedConversions([[
            'clickid' => 'funnel-mvt',
            'status' => 'Purchase',
            'step' => 0,
            'is_initial' => true,
        ]]);

        $result = $this->stat(
            ['clicks', 'conversion'],
            [],
            'UTC',
            [
                'flow' => 'Flow 1',
                'step' => 1,
                'landing' => 'landing-a',
                'test' => 0,
            ]
        );

        $this->assertCount(1, $result);
        $this->assertSame('A', $result[0]['group']);
        $this->assertSame(1, $result[0]['clicks']);
        $this->assertSame(0, $result[0]['conversion']);
    }

    public function testUniquesDeduplicated(): void
    {
        $this->db->seedClicks([
            ['userid' => 'aaa', 'clickid' => 'c1'],
            ['userid' => 'aaa', 'clickid' => 'c2'],
            ['userid' => 'bbb', 'clickid' => 'c3'],
        ]);

        $result = $this->stat(['clicks', 'uniques', 'uniques_ratio']);
        $this->assertEquals(3, $result[0]['clicks']);
        $this->assertEquals(2, $result[0]['uniques']);
        $this->assertEqualsWithDelta(66.67, $result[0]['uniques_ratio'], 0.1);
    }

    // ─── Conversion metrics ──────────────────────────────────

    public function testConversionMetrics(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Lead', 'payout' => 0],
            ['subid' => 's2', 'status' => 'Purchase', 'payout' => 50],
            ['subid' => 's3', 'status' => 'Reject', 'payout' => 0],
            ['subid' => 's4', 'status' => 'Trash', 'payout' => 0],
            ['subid' => 's5', 'status' => null, 'payout' => 0],
        ]);

        $result = $this->stat(['clicks', 'conversion', 'purchase', 'hold', 'reject', 'trash', 'cra']);
        $this->assertEquals(5, $result[0]['clicks']);
        $this->assertEquals(4, $result[0]['conversion']); // Lead+Purchase+Reject+Trash
        $this->assertEquals(1, $result[0]['purchase']);
        $this->assertEquals(1, $result[0]['hold']);
        $this->assertEquals(1, $result[0]['reject']);
        $this->assertEquals(1, $result[0]['trash']);
        // CRa = conversions/clicks * 100 = 4/5*100 = 80%
        $this->assertEqualsWithDelta(80.0, $result[0]['cra'], 0.01);
    }

    // ─── Revenue / Costs / Profit / ROI ──────────────────────

    public function testRevenueAndProfit(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase', 'payout' => 100, 'cost' => 20],
            ['subid' => 's2', 'status' => 'Purchase', 'payout' => 50, 'cost' => 30],
        ]);

        $result = $this->stat(['clicks', 'revenue', 'costs', 'profit', 'roi', 'epc', 'cpc']);
        $this->assertEqualsWithDelta(150, $result[0]['revenue'], 0.01);
        $this->assertEqualsWithDelta(50, $result[0]['costs'], 0.01);
        $this->assertEqualsWithDelta(100, $result[0]['profit'], 0.01);
        // ROI = (150-50)/50 * 100 = 200%
        $this->assertEqualsWithDelta(200.0, $result[0]['roi'], 0.01);
        // EPC = 150/2 = 75
        $this->assertEqualsWithDelta(75.0, $result[0]['epc'], 0.01);
        // CPC = 50/2 = 25
        $this->assertEqualsWithDelta(25.0, $result[0]['cpc'], 0.01);
    }

    // ─── App / Appt ──────────────────────────────────────────

    public function testAppAndApptCanBeBuiltAsCustomFormulas(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase', 'payout' => 100],
            ['subid' => 's2', 'status' => 'Lead', 'payout' => 0],
            ['subid' => 's3', 'status' => 'Trash', 'payout' => 0],
        ]);

        $result = $this->stat([
            'conversion',
            'purchase',
            'trash',
            ['field' => 'custom.app', 'title' => 'App', 'formula' => 'purchase/conversion*100', 'format' => 'percent', 'decimals' => 2, 'custom' => true],
            ['field' => 'custom.appt', 'title' => 'App(t)', 'formula' => 'purchase/(conversion-trash)*100', 'format' => 'percent', 'decimals' => 2, 'custom' => true],
        ]);
        // app = purchase/conversion * 100 = 1/3*100 = 33.33%
        $this->assertEqualsWithDelta(33.33, $result[0]['custom.app'], 0.1);
        // appt = purchase/(conversion - trash) * 100 = 1/(3-1)*100 = 50%
        $this->assertEqualsWithDelta(50.0, $result[0]['custom.appt'], 0.01);
    }

    public function testUniversalStatusCalculations(): void
    {
        $this->db->seedClicks([
            ['clickid' => 'c1', 'userid' => 'u1', 'time' => 100],
            ['clickid' => 'c2', 'userid' => 'u2', 'time' => 100],
            ['clickid' => 'c3', 'userid' => 'u3', 'time' => 100],
            ['clickid' => 'c4', 'userid' => 'u4', 'time' => 100],
        ]);
        $this->db->seedConversions([
            ['clickid' => 'c1', 'status' => 'Reg', 'time' => 200, 'is_initial' => true],
            ['clickid' => 'c2', 'status' => 'Reg', 'time' => 210, 'is_initial' => true],
            ['clickid' => 'c2', 'status' => 'Reg', 'time' => 300, 'changes_status' => false, 'payout' => 10],
            ['clickid' => 'c3', 'status' => 'Reg', 'time' => 220, 'is_initial' => true],
            ['clickid' => 'c3', 'status' => 'Purchase', 'time' => 230],
            ['clickid' => 'c4', 'status' => 'Purchase', 'time' => 240, 'is_initial' => true],
            ['clickid' => 'c4', 'status' => 'Reg', 'time' => 250],
        ]);

        $columns = [
            ['field' => 'status.reg_current', 'title' => 'Reg current', 'status' => 'Reg', 'calculation' => 'current', 'status_metric' => true],
            ['field' => 'status.reg_count', 'title' => 'Reg count', 'status' => 'Reg', 'calculation' => 'count', 'status_metric' => true],
            ['field' => 'status.reg_unique', 'title' => 'Reg unique', 'status' => 'Reg', 'calculation' => 'unique', 'status_metric' => true],
            ['field' => 'status.reg_nth_2', 'title' => 'Reg second', 'status' => 'Reg', 'calculation' => 'nth', 'occurrence' => 2, 'status_metric' => true],
        ];

        $previous = $GLOBALS['cloSettings']['conversionAttribution'] ?? null;
        $GLOBALS['cloSettings']['conversionAttribution'] = 'click_time';
        try {
            $row = $this->db->get_statistics($columns, [], 1, '90', '110', 'UTC')[0];
        } finally {
            $GLOBALS['cloSettings']['conversionAttribution'] = $previous ?? 'click_time';
        }

        $this->assertSame(3, $row['status.reg_current']);
        $this->assertSame(5, $row['status.reg_count']);
        $this->assertSame(4, $row['status.reg_unique']);
        $this->assertSame(1, $row['status.reg_nth_2']);
    }

    public function testConversionTimeAttributionUsesConversionRows(): void
    {
        $this->db->seedClicks([
            ['clickid' => 'c1', 'userid' => 'u1', 'time' => 100],
        ]);
        $this->db->seedConversions([
            ['clickid' => 'c1', 'status' => 'Reg', 'time' => 200, 'is_initial' => true],
            ['clickid' => 'c1', 'status' => 'Reg', 'time' => 300, 'changes_status' => false, 'payout' => 10],
        ]);
        $columns = [
            'clicks', 'conversion', 'revenue',
            ['field' => 'status.reg_count', 'title' => 'Reg count', 'status' => 'Reg', 'calculation' => 'count', 'status_metric' => true],
            ['field' => 'status.reg_nth_2', 'title' => 'Reg second', 'status' => 'Reg', 'calculation' => 'nth', 'occurrence' => 2, 'status_metric' => true],
        ];

        $previous = $GLOBALS['cloSettings']['conversionAttribution'] ?? null;
        $GLOBALS['cloSettings']['conversionAttribution'] = 'conversion_time';
        try {
            $row = $this->db->get_statistics($columns, [], 1, '290', '310', 'UTC')[0];
        } finally {
            $GLOBALS['cloSettings']['conversionAttribution'] = $previous ?? 'click_time';
        }

        $this->assertSame(0, $row['clicks']);
        $this->assertSame(0, $row['conversion']);
        $this->assertEqualsWithDelta(10, $row['revenue'], 0.001);
        $this->assertSame(1, $row['status.reg_count']);
        $this->assertSame(1, $row['status.reg_nth_2']);
    }

    // ─── Zero division safety ────────────────────────────────

    public function testZeroDivisionSafety(): void
    {
        // Single click, no conversions, no costs
        $this->db->seedClicks([
            ['subid' => 's1', 'payout' => 0, 'cost' => 0],
        ]);

        $result = $this->stat($this->allFields());
        $this->assertEquals(0, $result[0]['cra']);
        $this->assertEquals(0, $result[0]['epc']);
    }

    // ─── Group by country ────────────────────────────────────

    public function testGroupByCountry(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US'],
            ['subid' => 's2', 'country' => 'US'],
            ['subid' => 's3', 'country' => 'DE'],
        ]);

        $result = $this->stat(['clicks'], ['country']);
        $this->assertCount(2, $result);

        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        $this->assertEquals(2, $byCountry['US']['clicks']);
        $this->assertEquals(1, $byCountry['DE']['clicks']);
    }

    public function testEventMetricsAggregateFromClickSteps(): void
    {
        $this->db->seedClicks([
            ['clickid' => 'c1', 'path' => ['landing-a'], 'step_events' => [['scroll_50' => 10, 'performance' => ['lcp' => 1000, 'cls' => 0]]]],
            ['clickid' => 'c2', 'path' => ['landing-a'], 'step_events' => [['scroll_50' => 20, 'performance' => ['lcp' => 1500, 'cls' => 0.02]]]],
            ['clickid' => 'c3', 'path' => ['landing-b'], 'step_events' => [['scroll_50' => 30, 'performance' => ['lcp' => 2000]]]],
            ['clickid' => 'c4', 'path' => ['landing-b'], 'step_events' => [['scroll_50' => 40, 'performance' => ['lcp' => 2500]]]],
            ['clickid' => 'c5', 'path' => ['landing-c']],
        ]);

        $row = $this->stat([
            'clicks',
            'event.scroll_50.count',
            'event.scroll_50.avg',
            'event.scroll_50.p75',
            'event.scroll_50.min',
            'event.scroll_50.max',
            'performance.lcp.p75',
            'performance.cls.count',
            'performance.cls.min',
        ])[0];

        $this->assertSame(5, $row['clicks']);
        $this->assertSame(4, $row['event.scroll_50.count']);
        $this->assertEqualsWithDelta(25, $row['event.scroll_50.avg'], 0.001);
        $this->assertEqualsWithDelta(30, $row['event.scroll_50.p75'], 0.001);
        $this->assertEqualsWithDelta(10, $row['event.scroll_50.min'], 0.001);
        $this->assertEqualsWithDelta(40, $row['event.scroll_50.max'], 0.001);
        $this->assertEqualsWithDelta(2000, $row['performance.lcp.p75'], 0.001);
        $this->assertSame(2, $row['performance.cls.count']);
        $this->assertEqualsWithDelta(0, $row['performance.cls.min'], 0.0001);
    }

    public function testEventMetricsGroupByCountryPreservesNullAndZeroSemantics(): void
    {
        $this->db->seedClicks([
            ['clickid' => 'c1', 'country' => 'US', 'path' => ['landing-a'], 'step_events' => [['scroll_50' => 100]]],
            ['clickid' => 'c2', 'country' => 'US', 'path' => ['landing-b'], 'step_events' => [['scroll_50' => 300]]],
            ['clickid' => 'c3', 'country' => 'DE', 'path' => ['landing-c']],
        ]);

        $result = $this->stat(
            ['event.scroll_50.count', 'event.scroll_50.avg'],
            ['country']
        );
        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        $this->assertSame(2, $byCountry['US']['event.scroll_50.count']);
        $this->assertEqualsWithDelta(200, $byCountry['US']['event.scroll_50.avg'], 0.001);
        $this->assertSame(0, $byCountry['DE']['event.scroll_50.count']);
        $this->assertNull($byCountry['DE']['event.scroll_50.avg']);
    }

    public function testWideP75SelectionIgnoresStoredUnselectedMetric(): void
    {
        $eventsForSample = static function (int $sample): array {
            $events = ['cta_click' => 10000 + $sample];
            for ($index = 1; $index <= 97; $index++) {
                $events["custom_{$index}"] = ($sample * 1000) + $index;
            }
            return $events;
        };
        $this->db->seedClicks([
            [
                'clickid' => 'c1',
                'flow' => 'Main',
                'path' => ['landing-a'],
                'step_events' => [$eventsForSample(1)],
            ],
            [
                'clickid' => 'c2',
                'flow' => 'Main',
                'path' => ['landing-a'],
                'step_events' => [$eventsForSample(2)],
            ],
            [
                'clickid' => 'c3',
                'flow' => 'Main',
                'path' => ['landing-b'],
                'step_events' => [$eventsForSample(3)],
            ],
            [
                'clickid' => 'c4',
                'flow' => 'Main',
                'path' => ['landing-b'],
                'step_events' => [$eventsForSample(4)],
            ],
        ]);

        $columns = ['clicks'];
        for ($index = 1; $index <= 97; $index++) {
            $columns[] = "event.custom_{$index}.p75";
        }

        $warnings = [];
        set_error_handler(
            static function (int $severity, string $message) use (&$warnings): bool {
                if (($severity & (E_WARNING | E_NOTICE)) === 0) {
                    return false;
                }
                $warnings[] = $message;
                return true;
            }
        );
        try {
            $tree = $this->stat($columns, ['flow', 'step', 'landing']);
        } finally {
            restore_error_handler();
        }
        $flow = $tree[0];

        $this->assertSame([], $warnings);
        $this->assertEqualsWithDelta(3001, $flow['event.custom_1.p75'], 0.001);
        $this->assertEqualsWithDelta(3097, $flow['event.custom_97.p75'], 0.001);
        $this->assertEqualsWithDelta(3097, $flow['_stats_totals']['event.custom_97.p75'], 0.001);
        $this->assertArrayNotHasKey('event.cta_click.p75', $flow);
        $this->assertArrayNotHasKey('event.cta_click.p75', $flow['_stats_totals']);
    }

    public function testStepLandingTreeUsesExactParentTotalsAndConversionStep(): void
    {
        $this->db->seedClicks([
            [
                'clickid' => 'c1', 'userid' => 'u1', 'flow' => 'Main', 'path' => ['pre-a', 'land-b'],
                'step' => 1, 'cost' => 10, 'status' => 'Purchase', 'payout' => 100,
                'step_events' => [['scroll_50' => 100], ['scroll_50' => 200]],
            ],
            [
                'clickid' => 'c2', 'userid' => 'u2', 'flow' => 'Main', 'path' => ['pre-a'],
                'step' => 0, 'cost' => 5, 'status' => 'Purchase', 'payout' => 50,
                'step_events' => [['scroll_50' => 300]],
            ],
        ]);

        $tree = $this->stat(
            [
                'clicks',
                'conversion',
                'revenue',
                'costs',
                'event.scroll_50.count',
                'event.scroll_50.avg',
                'event.scroll_50.p75',
                'event.scroll_50.min',
                'event.scroll_50.max',
            ],
            ['flow', 'step', 'landing']
        );
        $flow = $tree[0];
        $this->assertSame('Main', $flow['group']);
        $this->assertSame(2, $flow['clicks']);
        $this->assertSame(2, $flow['conversion']);
        $this->assertEqualsWithDelta(150, $flow['revenue'], 0.001);
        $this->assertEqualsWithDelta(15, $flow['costs'], 0.001);
        $this->assertSame(3, $flow['event.scroll_50.count']);
        $this->assertEqualsWithDelta(200, $flow['event.scroll_50.avg'], 0.001);
        $this->assertEqualsWithDelta(300, $flow['event.scroll_50.p75'], 0.001);
        $this->assertEqualsWithDelta(100, $flow['event.scroll_50.min'], 0.001);
        $this->assertEqualsWithDelta(300, $flow['event.scroll_50.max'], 0.001);

        $steps = [];
        foreach ($flow['_children'] as $step) {
            $steps[$step['group']] = $step;
        }
        $this->assertSame(2, $steps['0']['clicks']);
        $this->assertEqualsWithDelta(150, $steps['0']['revenue'], 0.001);
        $this->assertSame(2, $steps['0']['event.scroll_50.count']);
        $this->assertEqualsWithDelta(200, $steps['0']['event.scroll_50.avg'], 0.001);
        $this->assertEqualsWithDelta(300, $steps['0']['event.scroll_50.p75'], 0.001);
        $this->assertSame(1, $steps['1']['clicks']);
        $this->assertEqualsWithDelta(100, $steps['1']['revenue'], 0.001);
        $this->assertSame(1, $steps['1']['event.scroll_50.count']);
        $this->assertEqualsWithDelta(200, $steps['1']['event.scroll_50.p75'], 0.001);
        $this->assertSame(2, $tree[0]['_stats_totals']['clicks']);
        $this->assertEqualsWithDelta(150, $tree[0]['_stats_totals']['revenue'], 0.001);
        $this->assertSame(3, $tree[0]['_stats_totals']['event.scroll_50.count']);
        $this->assertEqualsWithDelta(300, $tree[0]['_stats_totals']['event.scroll_50.p75'], 0.001);
        $this->assertArrayNotHasKey('_stats_totals', $steps['0']);
    }

    public function testIndexedTreeKeepsCollisionSafeHierarchyAndExactTotals(): void
    {
        $this->db->seedClicks([
            [
                'clickid' => 'c1',
                'params' => '{"parent_a":"a","parent_b":"b;c","leaf":"left"}',
            ],
            [
                'clickid' => 'c2',
                'params' => '{"parent_a":"a","parent_b":"b;c","leaf":"second"}',
            ],
            [
                'clickid' => 'c3',
                'params' => '{"parent_a":"a;b","parent_b":"c","leaf":"right"}',
            ],
        ]);

        $tree = $this->stat(
            ['clicks'],
            ['param.parent_a', 'param.parent_b', 'param.leaf']
        );

        $parents = [];
        $totals = null;
        foreach ($tree as $parent) {
            $parents[$parent['group']] = $parent;
            $totals ??= $parent['_stats_totals'] ?? null;
        }

        $this->assertSame(3, $totals['clicks'] ?? null);
        $this->assertSame(2, $parents['a']['clicks']);
        $this->assertSame(1, $parents['a;b']['clicks']);

        $firstBranch = $parents['a']['_children'][0];
        $this->assertSame('b;c', $firstBranch['group']);
        $this->assertSame(2, $firstBranch['clicks']);
        $this->assertSame(
            ['left', 'second'],
            array_column($firstBranch['_children'], 'group')
        );

        $secondBranch = $parents['a;b']['_children'][0];
        $this->assertSame('c', $secondBranch['group']);
        $this->assertSame(1, $secondBranch['clicks']);
        $this->assertSame('right', $secondBranch['_children'][0]['group']);
    }

    public function testNegativeStepFiltersKeepClicksWithAnotherEligibleStepInParentTotals(): void
    {
        $this->db->seedClicks([
            [
                'clickid' => 'c1', 'userid' => 'u1', 'flow' => 'Main',
                'path' => ['landing-a', 'landing-b', 'landing-c'], 'step' => 2,
                'step_events' => [
                    ['scroll_50' => 100],
                    ['scroll_50' => 200],
                    ['scroll_50' => 300],
                ],
            ],
            [
                'clickid' => 'c2', 'userid' => 'u2', 'flow' => 'Main',
                'path' => ['landing-a'], 'step' => 0,
                'step_events' => [['scroll_50' => 400]],
            ],
            [
                'clickid' => 'c3', 'userid' => 'u3', 'flow' => 'Main',
                'path' => ['landing-x', 'landing-y'], 'step' => 1,
                'step_events' => [
                    ['scroll_50' => 500],
                    ['scroll_50' => 600],
                ],
            ],
        ]);

        $notStepOne = $this->db->get_statistics(
            ['clicks', 'event.scroll_50.count'],
            ['flow', 'step'],
            1,
            '0',
            '9999999999',
            'UTC',
            ['condition' => 'AND', 'rules' => [[
                'field' => 'step',
                'operator' => '!=',
                'value' => 1,
            ]]]
        );

        $this->assertSame(3, $notStepOne[0]['clicks']);
        $this->assertSame(4, $notStepOne[0]['event.scroll_50.count']);
        $this->assertSame(3, $notStepOne[0]['_stats_totals']['clicks']);

        $onlyStepOne = $this->db->get_statistics(
            ['clicks', 'event.scroll_50.count'],
            ['flow', 'step'],
            1,
            '0',
            '9999999999',
            'UTC',
            ['condition' => 'AND', 'rules' => [[
                'field' => 'step',
                'operator' => 'not_in',
                'value' => [0, 2],
            ]]]
        );

        $this->assertSame(2, $onlyStepOne[0]['clicks']);
        $this->assertSame(2, $onlyStepOne[0]['event.scroll_50.count']);
        $this->assertSame(2, $onlyStepOne[0]['_stats_totals']['clicks']);
        $this->assertSame('1', (string)$onlyStepOne[0]['_children'][0]['group']);
    }

    public function testNegativeLandingFiltersKeepClicksWithAnotherEligibleLandingInParentTotals(): void
    {
        $this->db->seedClicks([
            [
                'clickid' => 'c1', 'userid' => 'u1', 'flow' => 'Main',
                'path' => ['landing-a', 'landing-b'], 'step' => 1,
                'step_events' => [
                    ['scroll_50' => 100],
                    ['scroll_50' => 200],
                ],
            ],
            [
                'clickid' => 'c2', 'userid' => 'u2', 'flow' => 'Main',
                'path' => ['landing-a'], 'step' => 0,
                'step_events' => [['scroll_50' => 300]],
            ],
            [
                'clickid' => 'c3', 'userid' => 'u3', 'flow' => 'Main',
                'path' => ['landing-b', 'landing-c'], 'step' => 1,
                'step_events' => [
                    ['scroll_50' => 400],
                    ['scroll_50' => 500],
                ],
            ],
        ]);

        $notLandingA = $this->db->get_statistics(
            ['clicks', 'event.scroll_50.count'],
            ['flow', 'step', 'landing'],
            1,
            '0',
            '9999999999',
            'UTC',
            ['condition' => 'AND', 'rules' => [[
                'field' => 'landing',
                'operator' => '!=',
                'value' => 'landing-a',
            ]]]
        );

        $this->assertSame(2, $notLandingA[0]['clicks']);
        $this->assertSame(3, $notLandingA[0]['event.scroll_50.count']);
        $this->assertSame(2, $notLandingA[0]['_stats_totals']['clicks']);

        $onlyLandingB = $this->db->get_statistics(
            ['clicks', 'event.scroll_50.count'],
            ['flow', 'step', 'landing'],
            1,
            '0',
            '9999999999',
            'UTC',
            ['condition' => 'AND', 'rules' => [[
                'field' => 'landing',
                'operator' => 'not_in',
                'value' => ['landing-a', 'landing-c'],
            ]]]
        );

        $this->assertSame(2, $onlyLandingB[0]['clicks']);
        $this->assertSame(2, $onlyLandingB[0]['event.scroll_50.count']);
        $this->assertSame(2, $onlyLandingB[0]['_stats_totals']['clicks']);
    }

    public function testCustomFormulaRejectsEventMetricDependencies(): void
    {
        $column = Db::normalize_custom_metric_column([
            'field' => 'custom.metric_1',
            'title' => 'Legacy event formula',
            'formula' => 'event.scroll_50.count/clicks*100',
            'format' => 'percent',
            'decimals' => 1,
            'custom' => true,
        ]);

        $this->assertNull($column);
    }

    public function testCustomFormulaNormalizationLowercasesFieldNames(): void
    {
        $column = Db::normalize_custom_metric_column([
            'field' => 'custom.metric_1',
            'title' => 'CTR',
            'formula' => 'Clicks/100',
            'format' => 'percent',
            'decimals' => 2,
            'custom' => true,
        ]);

        $this->assertNotNull($column);
        $this->assertSame('clicks/100', $column['formula']);
    }

    public function testCustomFormulaNormalizationRejectsIncompleteExpression(): void
    {
        $column = Db::normalize_custom_metric_column([
            'field' => 'custom.metric_1',
            'title' => 'Broken',
            'formula' => 'clicks+',
            'format' => 'number',
            'decimals' => 2,
            'custom' => true,
        ]);

        $this->assertNull($column);
    }

    public function testCustomFormulaNormalizationRejectsUnbalancedParentheses(): void
    {
        $column = Db::normalize_custom_metric_column([
            'field' => 'custom.metric_1',
            'title' => 'Broken',
            'formula' => '(clicks/revenue',
            'format' => 'number',
            'decimals' => 2,
            'custom' => true,
        ]);

        $this->assertNull($column);
    }

    public function testTabulatorCustomFormulaUsesServerTotals(): void
    {
        $columns = Tabulator::get_stats_columns([
            [
                'field' => 'custom.metric_1',
                'title' => 'Revenue per click',
                'formula' => 'revenue/clicks',
                'format' => 'percent',
                'decimals' => 1,
                'custom' => true,
            ],
        ]);

        $this->assertStringContainsString('Revenue per click', $columns);
        $this->assertStringContainsString('_stats_totals', $columns);
        $this->assertStringContainsString('.find(function(row)', $columns);
        $this->assertStringContainsString("custom.metric_1", $columns);
        $this->assertStringNotContainsString('revenue/clicks', $columns);
    }

    public function testTabulatorSkipsInvalidCustomFormula(): void
    {
        $columns = Tabulator::get_stats_columns([
            [
                'field' => 'custom.metric_1',
                'title' => 'Broken custom',
                'formula' => 'clicks+',
                'format' => 'number',
                'decimals' => 2,
                'custom' => true,
            ],
        ]);

        $this->assertSame('[]', $columns);
        $this->assertStringNotContainsString('Broken custom', $columns);
        $this->assertStringNotContainsString('clicks+', $columns);
    }

    public function testTabulatorFormatsClsCountAsAnInteger(): void
    {
        $columns = Tabulator::get_stats_columns([[
            'field' => 'performance.cls.count',
            'title' => 'CLS count',
        ]]);

        $this->assertStringContainsString('minimumFractionDigits:0', $columns);
        $this->assertStringContainsString('maximumFractionDigits:0', $columns);
        $this->assertStringNotContainsString('minimumFractionDigits:4', $columns);
        $this->assertStringNotContainsString('\\"', $columns);
    }

    public function testCampaignDashboardKeepsItsNativeBottomCalculations(): void
    {
        $columns = Tabulator::get_campaigns_columns([
            ['field' => 'name', 'width' => 120],
            ['field' => 'clicks', 'width' => -1],
            ['field' => 'cra', 'width' => -1],
        ]);

        $this->assertStringNotContainsString('_stats_totals', $columns);
        $this->assertStringContainsString('"bottomCalc":"sum"', $columns);
        $this->assertStringContainsString('cv+=r.conversion||0', $columns);
        $this->assertStringContainsString('c+=r.clicks||0', $columns);
    }

    public function testStatisticsSkipsInvalidLegacyCustomFormulaWithoutSqlError(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1'],
        ]);

        $result = $this->db->get_statistics([
            [
                'field' => 'custom.metric_1',
                'title' => 'Broken custom',
                'formula' => 'clicks+',
                'format' => 'number',
                'decimals' => 2,
                'custom' => true,
            ],
        ], [], 1, '0', '9999999999', 'UTC');

        $this->assertSame([], $result);
    }

    public function testCustomFormulaDerivedProfitRecalculatesPerGroupWithoutBaseColumns(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => 'Purchase', 'payout' => 100, 'cost' => 25],
            ['subid' => 's2', 'country' => 'US', 'status' => 'Purchase', 'payout' => 20, 'cost' => 5],
            ['subid' => 's3', 'country' => 'DE', 'status' => 'Purchase', 'payout' => 40, 'cost' => 10],
        ]);

        $result = $this->db->get_statistics([
            'clicks',
            [
                'field' => 'custom.metric_1',
                'title' => 'Profit per click',
                'formula' => 'profit/clicks',
                'format' => 'currency',
                'decimals' => 2,
                'custom' => true,
            ],
        ], ['country'], 1, '0', '9999999999', 'UTC');

        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        $this->assertEqualsWithDelta(45.0, $byCountry['US']['custom.metric_1'], 0.01);
        $this->assertEqualsWithDelta(30.0, $byCountry['DE']['custom.metric_1'], 0.01);
        $this->assertArrayNotHasKey('revenue', $byCountry['US']);
        $this->assertArrayNotHasKey('costs', $byCountry['US']);
        $this->assertArrayNotHasKey('profit', $byCountry['US']);
    }

    public function testCustomFormulaDerivedRoiRecalculatesWithoutBaseColumns(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => 'Purchase', 'payout' => 150, 'cost' => 50],
            ['subid' => 's2', 'country' => 'DE', 'status' => 'Purchase', 'payout' => 60, 'cost' => 30],
        ]);

        $result = $this->db->get_statistics([
            [
                'field' => 'custom.metric_1',
                'title' => 'ROI copy',
                'formula' => 'roi',
                'format' => 'percent',
                'decimals' => 2,
                'custom' => true,
            ],
        ], ['country'], 1, '0', '9999999999', 'UTC');

        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        $this->assertEqualsWithDelta(200.0, $byCountry['US']['custom.metric_1'], 0.01);
        $this->assertEqualsWithDelta(100.0, $byCountry['DE']['custom.metric_1'], 0.01);
    }

    public function testCustomFormulaDerivedCraRecalculatesWithoutBaseColumns(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => 'Lead'],
            ['subid' => 's2', 'country' => 'US', 'status' => null],
            ['subid' => 's3', 'country' => 'DE', 'status' => 'Purchase'],
        ]);

        $result = $this->db->get_statistics([
            [
                'field' => 'custom.metric_1',
                'title' => 'CR x2',
                'formula' => 'cra*2',
                'format' => 'percent',
                'decimals' => 2,
                'custom' => true,
            ],
        ], ['country'], 1, '0', '9999999999', 'UTC');

        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        $this->assertEqualsWithDelta(100.0, $byCountry['US']['custom.metric_1'], 0.01);
        $this->assertEqualsWithDelta(200.0, $byCountry['DE']['custom.metric_1'], 0.01);
    }

    // ─── Multi-level grouping (country → step) ──────────────

    public function testMultiLevelGrouping(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'step' => 0, 'path' => ['Landing A']],
            ['subid' => 's2', 'country' => 'US', 'step' => 1, 'path' => ['Landing A', 'Landing B']],
            ['subid' => 's3', 'country' => 'US', 'step' => 0, 'path' => ['Landing A']],
            ['subid' => 's4', 'country' => 'DE', 'step' => 0, 'path' => ['Landing C']],
        ]);

        $result = $this->stat(['clicks'], ['country', 'step']);
        $this->assertCount(2, $result); // US, DE

        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        // Parent totals count clicks exactly once. Step children represent reached
        // steps, so the step-1 click is also present under step 0.
        $this->assertEquals(3, $byCountry['US']['clicks']);
        $this->assertArrayHasKey('_children', $byCountry['US']);
        $this->assertCount(2, $byCountry['US']['_children']);
        $usByStep = [];
        foreach ($byCountry['US']['_children'] as $row) {
            $usByStep[$row['group']] = $row;
        }
        $this->assertSame(3, (int)$usByStep['0']['clicks']);
        $this->assertSame(1, (int)$usByStep['1']['clicks']);

        // DE has 1 click
        $this->assertEquals(1, $byCountry['DE']['clicks']);
    }

    // ─── TOTAL row (no grouping = single aggregated row) ────

    public function testTotalRowNoGrouping(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'payout' => 10, 'cost' => 5, 'status' => 'Purchase'],
            ['subid' => 's2', 'payout' => 20, 'cost' => 8, 'status' => 'Lead'],
            ['subid' => 's3', 'payout' => 0, 'cost' => 3, 'status' => null],
        ]);

        $result = $this->stat(['clicks', 'revenue', 'costs', 'profit', 'conversion', 'purchase']);
        $this->assertCount(1, $result);
        $this->assertEquals(3, $result[0]['clicks']);
        $this->assertEqualsWithDelta(30, $result[0]['revenue'], 0.01);
        $this->assertEqualsWithDelta(16, $result[0]['costs'], 0.01);
        $this->assertEqualsWithDelta(14, $result[0]['profit'], 0.01);
        $this->assertEquals(2, $result[0]['conversion']);
        $this->assertEquals(1, $result[0]['purchase']);
    }

    // ─── Derived metrics precision ───────────────────────────

    public function testDerivedMetricsPrecision(): void
    {
        // 3 clicks, 1 conversion → CRa should be 33.33...%, not 33 or 34
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Lead'],
            ['subid' => 's2', 'status' => null],
            ['subid' => 's3', 'status' => null],
        ]);

        $result = $this->stat(['clicks', 'cra', 'conversion']);
        $this->assertEquals(3, $result[0]['clicks']);
        $this->assertEquals(1, $result[0]['conversion']);
        // CRa = 1/3 * 100 = 33.333...
        $this->assertEqualsWithDelta(33.33, $result[0]['cra'], 0.1);
        // Must NOT be an integer
        $this->assertNotEquals(33, $result[0]['cra']);
        $this->assertNotEquals(34, $result[0]['cra']);
    }

    // ─── Group by date with timezone ─────────────────────────

    public function testGroupByDateWithTimezone(): void
    {
        // Two clicks: one at 2023-11-14 23:00 UTC, one at 2023-11-15 01:00 UTC
        $t1 = mktime(23, 0, 0, 11, 14, 2023); // 2023-11-14 23:00 UTC
        $t2 = mktime(1, 0, 0, 11, 15, 2023);  // 2023-11-15 01:00 UTC

        $this->db->seedClicks([
            ['subid' => 's1', 'time' => $t1],
            ['subid' => 's2', 'time' => $t2],
        ]);

        // In UTC, these are on different dates
        $resultUtc = $this->db->get_statistics(['clicks'], ['date'], 1, '0', '9999999999', 'UTC');
        $this->assertCount(2, $resultUtc);

        // In UTC+3 (Europe/Moscow), 23:00 UTC = 02:00 next day, so both are on 2023-11-15
        $resultMsk = $this->db->get_statistics(['clicks'], ['date'], 1, '0', '9999999999', 'Europe/Moscow');
        $this->assertCount(1, $resultMsk);
        $this->assertEquals(2, $resultMsk[0]['clicks']);
    }

    // ─── JSON params grouping ────────────────────────────────

    public function testGroupByJsonParam(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'params' => '{"source":"fb"}'],
            ['subid' => 's2', 'params' => '{"source":"fb"}'],
            ['subid' => 's3', 'params' => '{"source":"google"}'],
            ['subid' => 's4', 'params' => '{}'],
        ]);

        $result = $this->stat(['clicks'], ['source']);
        $this->assertCount(3, $result); // fb, google, unknown

        $bySource = [];
        foreach ($result as $row) {
            $bySource[$row['group']] = $row;
        }

        $this->assertEquals(2, $bySource['fb']['clicks']);
        $this->assertEquals(1, $bySource['google']['clicks']);
        $this->assertEquals(1, $bySource['unknown']['clicks']);
    }

    // ─── Group by step ───────────────────────────────────────

    public function testGroupByStep(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'path' => ['landing-a'], 'step' => 0, 'status' => 'Purchase', 'payout' => 10],
            ['subid' => 's2', 'path' => ['landing-b'], 'step' => 0, 'status' => 'Purchase', 'payout' => 20],
            ['subid' => 's3', 'path' => ['pre-a', 'landing-c'], 'step' => 1, 'status' => 'Purchase', 'payout' => 30],
        ]);

        $result = $this->stat(['clicks', 'revenue'], ['step']);
        $this->assertCount(2, $result);

        $byStep = [];
        foreach ($result as $row) {
            $byStep[$row['group']] = $row;
        }

        $this->assertEquals(3, $byStep['0']['clicks']);
        $this->assertEqualsWithDelta(60, $byStep['0']['revenue'], 0.01);
        $this->assertEquals(1, $byStep['1']['clicks']);
        $this->assertEqualsWithDelta(30, $byStep['1']['revenue'], 0.01);
        $this->assertEquals(3, $result[0]['_stats_totals']['clicks']);
        $this->assertEqualsWithDelta(60, $result[0]['_stats_totals']['revenue'], 0.01);
    }

    // ─── Edge: custom App(t) with all clicks in Trash ────────

    public function testAllTrashApptIsZero(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Trash'],
            ['subid' => 's2', 'status' => 'Trash'],
        ]);

        $result = $this->stat([
            'conversion', 'trash', 'purchase',
            ['field' => 'custom.appt', 'title' => 'App(t)', 'formula' => 'purchase/(conversion-trash)*100', 'format' => 'percent', 'decimals' => 2, 'custom' => true],
        ]);
        // appt denominator = conversion - trash = 2 - 2 = 0 → should be 0
        $this->assertEquals(0, $result[0]['custom.appt']);
    }

    // ─── Edge: zero costs → ROI should handle gracefully ────

    public function testZeroCostsRoi(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase', 'payout' => 100, 'cost' => 0],
        ]);

        $result = $this->stat(['revenue', 'costs', 'roi']);
        // ROI = (100-0)/0 → division by zero, SQLite returns NULL
        // Should not crash
        $this->assertIsArray($result);
    }

    // ─── Date range filtering ────────────────────────────────

    public function testDateRangeFiltering(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'time' => 1000],
            ['subid' => 's2', 'time' => 2000],
            ['subid' => 's3', 'time' => 3000],
        ]);

        $result = $this->db->get_statistics(['clicks'], [], 1, '1500', '2500', 'UTC');
        $this->assertEquals(1, $result[0]['clicks']);
    }

    // ─── EPuC (uepc) ──────────────────────────────────────────

    public function testUepcCalculation(): void
    {
        $this->db->seedClicks([
            ['userid' => 'aaa', 'clickid' => 'c1', 'status' => 'Purchase', 'payout' => 100],
            ['userid' => 'aaa', 'clickid' => 'c2', 'status' => 'Purchase', 'payout' => 50],  // duplicate userid
            ['userid' => 'bbb', 'clickid' => 'c3', 'status' => 'Purchase', 'payout' => 30],
        ]);

        $result = $this->stat(['clicks', 'uniques', 'revenue', 'uepc']);
        $this->assertEquals(3, $result[0]['clicks']);
        $this->assertEquals(2, $result[0]['uniques']);
        $this->assertEqualsWithDelta(180, $result[0]['revenue'], 0.01);
        // uepc = revenue / uniques = 180 / 2 = 90 (NOT * 100)
        $this->assertEqualsWithDelta(90.0, $result[0]['uepc'], 0.01);
    }

    public function testUepcZeroUniques(): void
    {
        // Edge: no clicks at all → uepc = 0
        $result = $this->stat(['uniques', 'revenue', 'uepc']);
        $this->assertEquals(0, $result[0]['uepc']);
    }

    // ─── CPuC (ucpc) ──────────────────────────────────────────

    public function testUcpcCalculation(): void
    {
        $this->db->seedClicks([
            ['userid' => 'aaa', 'clickid' => 'c1', 'cost' => 10],
            ['userid' => 'aaa', 'clickid' => 'c2', 'cost' => 5],   // duplicate userid
            ['userid' => 'bbb', 'clickid' => 'c3', 'cost' => 15],
        ]);

        $result = $this->stat(['uniques', 'costs', 'ucpc']);
        $this->assertEquals(2, $result[0]['uniques']);
        $this->assertEqualsWithDelta(30, $result[0]['costs'], 0.01);
        // ucpc = costs / uniques = 30 / 2 = 15
        $this->assertEqualsWithDelta(15.0, $result[0]['ucpc'], 0.01);
    }

    public function testUcpcZeroUniques(): void
    {
        $result = $this->stat(['uniques', 'costs', 'ucpc']);
        $this->assertEquals(0, $result[0]['ucpc']);
    }

    // ─── EC (earnings per conversion) ─────────────────────────

    public function testEcCalculation(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase', 'payout' => 100],
            ['subid' => 's2', 'status' => 'Lead', 'payout' => 0],
            ['subid' => 's3', 'status' => null, 'payout' => 0],
        ]);

        $result = $this->stat(['conversion', 'revenue', 'ec']);
        $this->assertEquals(2, $result[0]['conversion']);
        $this->assertEqualsWithDelta(100, $result[0]['revenue'], 0.01);
        // ec = revenue / conversion = 100 / 2 = 50
        $this->assertEqualsWithDelta(50.0, $result[0]['ec'], 0.01);
    }

    public function testEcZeroConversions(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => null, 'payout' => 50],
        ]);

        $result = $this->stat(['conversion', 'revenue', 'ec']);
        $this->assertEquals(0, $result[0]['conversion']);
        $this->assertEquals(0, $result[0]['ec']);
    }

    // ─── CPA (cost per action) ────────────────────────────────

    public function testCpaCalculation(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase', 'cost' => 20],
            ['subid' => 's2', 'status' => 'Lead', 'cost' => 30],
            ['subid' => 's3', 'status' => null, 'cost' => 10],
        ]);

        $result = $this->stat(['conversion', 'costs', 'cpa']);
        $this->assertEquals(2, $result[0]['conversion']);
        $this->assertEqualsWithDelta(60, $result[0]['costs'], 0.01);
        // cpa = costs / conversion = 60 / 2 = 30
        $this->assertEqualsWithDelta(30.0, $result[0]['cpa'], 0.01);
    }

    public function testCpaZeroConversions(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => null, 'cost' => 50],
        ]);

        $result = $this->stat(['conversion', 'costs', 'cpa']);
        $this->assertEquals(0, $result[0]['cpa']);
    }

    // ─── Drill-down: derived metrics recalculated in tree ─────

    public function testDrillDownDerivedMetricsRecalculated(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'payout' => 100, 'cost' => 20, 'status' => 'Purchase'],
            ['subid' => 's2', 'country' => 'US', 'payout' => 0, 'cost' => 10, 'status' => 'Lead'],
            ['subid' => 's3', 'country' => 'DE', 'payout' => 50, 'cost' => 5, 'status' => 'Purchase'],
        ]);

        $fields = ['clicks', 'uniques', 'revenue', 'costs', 'profit', 'roi', 'epc', 'uepc', 'cpc', 'ucpc', 'ec', 'cpa', 'conversion', 'purchase'];
        $result = $this->stat($fields, ['country']);

        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        // US: 2 clicks, revenue=100, costs=30, profit=70
        $us = $byCountry['US'];
        $this->assertEquals(2, $us['clicks']);
        $this->assertEqualsWithDelta(100, $us['revenue'], 0.01);
        $this->assertEqualsWithDelta(30, $us['costs'], 0.01);
        $this->assertEqualsWithDelta(70, $us['profit'], 0.01);
        // ROI = (100-30)/30 * 100 = 233.33%
        $this->assertEqualsWithDelta(233.33, $us['roi'], 0.1);
        // EPC = 100/2 = 50
        $this->assertEqualsWithDelta(50.0, $us['epc'], 0.01);
        // CPC = 30/2 = 15
        $this->assertEqualsWithDelta(15.0, $us['cpc'], 0.01);
        // EC = 100/2 = 50
        $this->assertEqualsWithDelta(50.0, $us['ec'], 0.01);
        // CPA = 30/2 = 15
        $this->assertEqualsWithDelta(15.0, $us['cpa'], 0.01);

        // DE: 1 click, revenue=50, costs=5, profit=45
        $de = $byCountry['DE'];
        $this->assertEqualsWithDelta(45, $de['profit'], 0.01);
        // ROI = (50-5)/5 * 100 = 900%
        $this->assertEqualsWithDelta(900.0, $de['roi'], 0.01);
    }

    // ─── All metrics zero division comprehensive ──────────────

    public function testAllMetricsZeroDivisionComprehensive(): void
    {
        // Single click with zero everything
        $this->db->seedClicks([
            ['subid' => 's1', 'payout' => 0, 'cost' => 0, 'status' => null],
        ]);

        $result = $this->stat($this->allFields());
        $row = $result[0];

        // All derived metrics should be 0 or finite, never INF/NAN
        foreach ($this->allFields() as $field) {
            $this->assertIsNumeric($row[$field], "Field $field should be numeric");
            if (is_float($row[$field])) {
                $this->assertFalse(is_nan($row[$field]), "Field $field should not be NaN");
                $this->assertFalse(is_infinite($row[$field]), "Field $field should not be INF");
            }
        }

        // Specific zero-division checks
        $this->assertEquals(0, $row['cra']);   // 0 conversions / 1 click
        $this->assertEquals(0, $row['epc']);   // 0 revenue / 1 click
        $this->assertEquals(0, $row['uepc']);  // 0 revenue / 1 unique
        $this->assertEquals(0, $row['cpc']);   // 0 costs / 1 click
        $this->assertEquals(0, $row['ucpc']); // 0 costs / 1 unique
        $this->assertEquals(0, $row['ec']);    // 0 revenue / 0 conversions
        $this->assertEquals(0, $row['cpa']);   // 0 costs / 0 conversions
        $this->assertEquals(0, $row['roi']);   // (0-0) / 0 costs
    }

    // ─── Profit recalculated (not summed from SQL) ────────────

    public function testProfitRecalculatedInTree(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => 'Purchase', 'payout' => 100, 'cost' => 30],
            ['subid' => 's2', 'country' => 'US', 'status' => 'Purchase', 'payout' => 50, 'cost' => 20],
            ['subid' => 's3', 'country' => 'DE', 'status' => 'Purchase', 'payout' => 10, 'cost' => 40],
        ]);

        $result = $this->stat(['revenue', 'costs', 'profit'], ['country']);
        $byCountry = [];
        foreach ($result as $row) {
            $byCountry[$row['group']] = $row;
        }

        // US: revenue=150, costs=50, profit should be 100 (recalculated)
        $this->assertEqualsWithDelta(100, $byCountry['US']['profit'], 0.01);
        // DE: revenue=10, costs=40, profit should be -30
        $this->assertEqualsWithDelta(-30, $byCountry['DE']['profit'], 0.01);
    }

    // ─── ROI recalculated in tree ─────────────────────────────

    public function testRoiRecalculatedInTree(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => 'Purchase', 'payout' => 200, 'cost' => 100],
            ['subid' => 's2', 'country' => 'US', 'payout' => 0, 'cost' => 50],
        ]);

        $result = $this->stat(['revenue', 'costs', 'roi'], ['country']);
        // US: revenue=200, costs=150, ROI = (200-150)/150 * 100 = 33.33%
        $this->assertEqualsWithDelta(33.33, $result[0]['roi'], 0.1);
    }

    // ─── Appt operator precedence fix ─────────────────────────

    public function testCustomApptConversionEqualsTrash(): void
    {
        // All conversions are Trash → denominator = conversion - trash = 0
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Trash'],
            ['subid' => 's2', 'status' => 'Trash'],
            ['subid' => 's3', 'status' => 'Trash'],
        ]);

        $result = $this->stat([
            'conversion', 'trash', 'purchase',
            ['field' => 'custom.appt', 'title' => 'App(t)', 'formula' => 'purchase/(conversion-trash)*100', 'format' => 'percent', 'decimals' => 2, 'custom' => true],
        ]);
        $this->assertEquals(3, $result[0]['conversion']);
        $this->assertEquals(3, $result[0]['trash']);
        $this->assertEquals(0, $result[0]['purchase']);
        // Must be exactly 0, not INF or NaN
        $this->assertSame(0.0, $result[0]['custom.appt']);
    }

    public function testCustomApptMixedStatuses(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase'],
            ['subid' => 's2', 'status' => 'Purchase'],
            ['subid' => 's3', 'status' => 'Lead'],
            ['subid' => 's4', 'status' => 'Reject'],
            ['subid' => 's5', 'status' => 'Trash'],
        ]);

        $result = $this->stat([
            'conversion', 'purchase', 'trash',
            ['field' => 'custom.appt', 'title' => 'App(t)', 'formula' => 'purchase/(conversion-trash)*100', 'format' => 'percent', 'decimals' => 2, 'custom' => true],
        ]);
        // conversion=5, trash=1, purchase=2
        // appt = 2 / (5-1) * 100 = 50%
        $this->assertEqualsWithDelta(50.0, $result[0]['custom.appt'], 0.01);
    }

    // ─── Filter: equals ───────────────────────────────────────

    public function testFilterEquals(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US'],
            ['subid' => 's2', 'country' => 'US'],
            ['subid' => 's3', 'country' => 'DE'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'country', 'operator' => '=', 'value' => 'US']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: not equals ───────────────────────────────────

    public function testFilterNotEquals(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US'],
            ['subid' => 's2', 'country' => 'DE'],
            ['subid' => 's3', 'country' => 'DE'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'country', 'operator' => '!=', 'value' => 'US']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: in ───────────────────────────────────────────

    public function testFilterIn(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US'],
            ['subid' => 's2', 'country' => 'DE'],
            ['subid' => 's3', 'country' => 'FR'],
            ['subid' => 's4', 'country' => 'RU'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'country', 'operator' => 'in', 'value' => 'US,DE']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: not_in ───────────────────────────────────────

    public function testFilterNotIn(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US'],
            ['subid' => 's2', 'country' => 'DE'],
            ['subid' => 's3', 'country' => 'FR'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'country', 'operator' => 'not_in', 'value' => 'US,DE']]]
        );
        $this->assertEquals(1, $result[0]['clicks']);
    }

    // ─── Filter: is_not_null (status) ─────────────────────────

    public function testFilterIsNotNull(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase'],
            ['subid' => 's2', 'status' => 'Lead'],
            ['subid' => 's3', 'status' => null],
            ['subid' => 's4', 'status' => null],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'status', 'operator' => 'is_not_null']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: is_null ──────────────────────────────────────

    public function testFilterIsNull(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'status' => 'Purchase'],
            ['subid' => 's2', 'status' => null],
            ['subid' => 's3', 'status' => null],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'status', 'operator' => 'is_null']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: combined AND ─────────────────────────────────

    public function testFilterCombinedAnd(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => 'Purchase'],
            ['subid' => 's2', 'country' => 'US', 'status' => null],
            ['subid' => 's3', 'country' => 'DE', 'status' => 'Purchase'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [
                ['field' => 'country', 'operator' => '=', 'value' => 'US'],
                ['field' => 'status', 'operator' => 'is_not_null'],
            ]]
        );
        $this->assertEquals(1, $result[0]['clicks']);
    }

    // ─── Filter: combined OR ──────────────────────────────────

    public function testFilterCombinedOr(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'status' => null],
            ['subid' => 's2', 'country' => 'DE', 'status' => 'Purchase'],
            ['subid' => 's3', 'country' => 'FR', 'status' => null],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'OR', 'rules' => [
                ['field' => 'country', 'operator' => '=', 'value' => 'US'],
                ['field' => 'status', 'operator' => 'is_not_null'],
            ]]
        );
        // US(1) OR has status(1 DE) = 2
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: empty filters = all data ─────────────────────

    public function testFilterEmptyReturnsAll(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1'],
            ['subid' => 's2'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            []
        );
        $this->assertEquals(2, $result[0]['clicks']);

        // Also test with empty rules array
        $result2 = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => []]
        );
        $this->assertEquals(2, $result2[0]['clicks']);
    }

    // ─── Filter: invalid field ignored ────────────────────────

    public function testFilterInvalidFieldIgnored(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1'],
            ['subid' => 's2'],
        ]);

        // "badfield" is not in FILTERABLE_FIELDS → should be ignored, return all
        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'badfield', 'operator' => '=', 'value' => 'x']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);
    }

    // ─── Filter: with grouping ────────────────────────────────

    public function testFilterWithGrouping(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'country' => 'US', 'step' => 0, 'path' => ['Landing A']],
            ['subid' => 's2', 'country' => 'US', 'step' => 1, 'path' => ['Landing A', 'Landing B']],
            ['subid' => 's3', 'country' => 'DE', 'step' => 0, 'path' => ['Landing C']],
            ['subid' => 's4', 'country' => 'DE', 'step' => 1, 'path' => ['Landing C', 'Landing D']],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], ['step'], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'country', 'operator' => '=', 'value' => 'US']]]
        );
        // Only US clicks, grouped by reached step.
        $this->assertCount(2, $result);
        $byStep = [];
        foreach ($result as $row) {
            $byStep[$row['group']] = $row;
        }
        $this->assertSame(2, (int)$byStep['0']['clicks']);
        $this->assertSame(1, (int)$byStep['1']['clicks']);
        $this->assertSame(2, (int)$result[0]['_stats_totals']['clicks']);
    }

    // ─── Filter: param.* JSON field ─────────────────────────────

    public function testFilterParamJsonField(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'params' => '{"utm_source":"fb","utm_campaign":"camp1"}'],
            ['subid' => 's2', 'params' => '{"utm_source":"fb","utm_campaign":"camp2"}'],
            ['subid' => 's3', 'params' => '{"utm_source":"google","utm_campaign":"camp1"}'],
            ['subid' => 's4', 'params' => '{}'],
        ]);

        // Filter by param.utm_source = fb
        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'param.utm_source', 'operator' => '=', 'value' => 'fb']]]
        );
        $this->assertEquals(2, $result[0]['clicks']);

        // Filter by param.utm_campaign in camp1,camp2
        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'param.utm_campaign', 'operator' => 'in', 'value' => 'camp1,camp2']]]
        );
        $this->assertEquals(3, $result[0]['clicks']);

        // Filter by param.utm_source != fb
        $result = $this->db->get_statistics(
            ['clicks'], [], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'param.utm_source', 'operator' => '!=', 'value' => 'fb']]]
        );
        $this->assertEquals(1, $result[0]['clicks']); // google only; empty params has NULL json_extract
    }

    // ─── GroupBy: param.* prefix ────────────────────────────────

    public function testGroupByParamPrefix(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'params' => '{"source":"fb"}'],
            ['subid' => 's2', 'params' => '{"source":"fb"}'],
            ['subid' => 's3', 'params' => '{"source":"google"}'],
        ]);

        $result = $this->stat(['clicks'], ['param.source']);
        $this->assertCount(2, $result);

        $bySource = [];
        foreach ($result as $row) {
            $bySource[$row['group']] = $row;
        }
        $this->assertEquals(2, $bySource['fb']['clicks']);
        $this->assertEquals(1, $bySource['google']['clicks']);
    }

    // ─── Filter + GroupBy: param.* combined ─────────────────────

    public function testFilterParamWithGroupby(): void
    {
        $this->db->seedClicks([
            ['subid' => 's1', 'params' => '{"utm_source":"fb","geo":"US"}'],
            ['subid' => 's2', 'params' => '{"utm_source":"fb","geo":"DE"}'],
            ['subid' => 's3', 'params' => '{"utm_source":"google","geo":"US"}'],
        ]);

        $result = $this->db->get_statistics(
            ['clicks'], ['param.geo'], 1, '0', '9999999999', 'UTC',
            ['condition' => 'AND', 'rules' => [['field' => 'param.utm_source', 'operator' => '=', 'value' => 'fb']]]
        );
        $this->assertCount(2, $result);
        $total = 0;
        foreach ($result as $row) {
            $total += $row['clicks'];
        }
        $this->assertEquals(2, $total);
    }
}
