<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestDb.php';
require_once __DIR__ . '/../../code/conversion.php';

final class ConversionTest extends TestCase
{
    private TestDb $db;
    private string $dbPath;
    private Campaign $campaign;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/yellowtds_conversion_' . uniqid() . '.db';
        $this->db = new TestDb($this->dbPath);
        $this->db->initSchema();
        $this->db->seedCampaign();
        $settings = json_decode(file_get_contents(__DIR__ . '/../../code/db/default.json'), true);
        $settings['postback']['s2s'] = [];
        $settings['conversions']['statuses'][] = ['name' => 'Reg', 'aliases' => ['reg', 'registration']];
        $this->campaign = new Campaign(1, $settings);
    }

    protected function tearDown(): void
    {
        $this->db->cleanup();
    }

    public function testConversionCapturesCurrentLandingStep(): void
    {
        $this->db->seedClicks([[
            'clickid' => 'step-click',
            'userid' => 'step-user',
            'flow' => 'Flow 1',
            'path' => ['landing-a', 'landing-b', 'landing-c'],
            'step' => 2,
        ]]);

        $result = (new ConversionService($this->db))->record(
            $this->campaign,
            'step-click',
            'Lead',
            'postback'
        );

        $this->assertTrue($result['accepted']);
        $conversions = $this->db->fetchConversions('step-click');
        $this->assertCount(1, $conversions);
        $this->assertSame(2, (int)$conversions[0]['step']);
    }

    public function testAliasResolutionPersistsNormalizedAndRawStatus(): void
    {
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1', 'flow' => 'Flow 1']]);

        $result = (new ConversionService($this->db))->record(
            $this->campaign,
            'c1',
            'ReGiStRaTiOn',
            'postback'
        );

        $this->assertTrue($result['accepted']);
        $this->assertSame('Reg', $result['status']);
        $rows = $this->db->fetchConversions('c1');
        $this->assertCount(1, $rows);
        $this->assertSame('Reg', $rows[0]['status']);
        $this->assertSame('ReGiStRaTiOn', $rows[0]['raw_status']);
        $this->assertSame(1, $rows[0]['is_initial']);
        $this->assertSame(1, $rows[0]['changes_status']);
        $this->assertSame('Reg', $this->db->get_click_by_clickid('c1')['status']);
    }

    public function testCatalogRejectsAliasCollisionsAndMissingBuiltIns(): void
    {
        $catalog = array_map(
            static fn(ConversionStatus $status): array => $status->jsonSerialize(),
            ConversionSettings::defaultStatuses()
        );
        $catalog[] = ['name' => 'Reg', 'aliases' => ['pending']];

        try {
            ConversionSettings::normalizeStatusCatalog($catalog);
            $this->fail('Expected alias collision to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('already assigned', $e->getMessage());
        }

        array_pop($catalog);
        array_pop($catalog);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be removed');
        ConversionSettings::normalizeStatusCatalog($catalog);
    }

    public function testStatusChangesDoNotCreateAnotherInitialConversion(): void
    {
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1']]);
        $service = new ConversionService($this->db);

        $first = $service->record($this->campaign, 'c1', 'lead', 'postback');
        $second = $service->record($this->campaign, 'c1', 'reject', 'postback');

        $this->assertTrue($first['accepted']);
        $this->assertTrue($second['accepted']);
        $rows = $this->db->fetchConversions('c1');
        $this->assertSame([1, 0], array_column($rows, 'is_initial'));
        $this->assertSame('Reject', $this->db->get_click_by_clickid('c1')['status']);
    }

    public function testTidAndPaidRepeatRules(): void
    {
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1']]);
        $service = new ConversionService($this->db);
        $this->assertTrue($service->record($this->campaign, 'c1', 'purchase', 'postback', 10, 'USD', 't1')['accepted']);

        $sameTid = $service->record($this->campaign, 'c1', 'purchase', 'postback', 10, 'USD', 't1');
        $withoutPayout = $service->record($this->campaign, 'c1', 'purchase', 'postback');
        $withoutTid = $service->record($this->campaign, 'c1', 'purchase', 'postback', 5);
        $newTid = $service->record($this->campaign, 'c1', 'purchase', 'postback', 5, 'USD', 't2');

        $this->assertSame('duplicate_tid', $sameTid['code']);
        $this->assertSame('duplicate_status', $withoutPayout['code']);
        $this->assertSame('tid_required', $withoutTid['code']);
        $this->assertTrue($newTid['accepted']);
        $this->assertEqualsWithDelta(15, $this->db->get_click_by_clickid('c1')['payout'], 0.001);
        $this->assertCount(2, $this->db->fetchConversions('c1'));
    }

    public function testTransactionIdParameterListNormalizesAndPersists(): void
    {
        $parameters = ConversionSettings::normalizeTransactionIdParameters(
            ' tid, order_id, TID, transaction-id, order_id '
        );

        $this->assertSame(['tid', 'order_id', 'transaction-id'], $parameters);

        $settings = ConversionSettings::fromArray([
            'deduplication' => [
                'transaction_id_parameters' => $parameters,
            ],
        ]);
        $this->assertSame(
            $parameters,
            $settings->jsonSerialize()['deduplication']['transaction_id_parameters']
        );
        $this->assertSame(
            ['tid'],
            ConversionSettings::fromArray([])->transactionIdParameters
        );
    }

    public function testTransactionIdParameterListRejectsInvalidInput(): void
    {
        foreach ([
            '',
            'clickid',
            'has space',
            '1starts_with_number',
            str_repeat('a', 65),
            array_fill(0, ConversionSettings::MAX_TRANSACTION_ID_PARAMETERS + 1, 'tid'),
        ] as $invalid) {
            if (is_array($invalid)) {
                $invalid = array_map(
                    static fn(int $index): string => 'tid_' . $index,
                    array_keys($invalid)
                );
            }
            try {
                ConversionSettings::normalizeTransactionIdParameters($invalid);
                $this->fail('Expected invalid transaction ID parameter list to be rejected.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testTransactionIdRequestResolutionRejectsAmbiguousAndInvalidValues(): void
    {
        $settings = ConversionSettings::fromArray([
            'deduplication' => [
                'transaction_id_parameters' => ['tid', 'order_id', 'transaction_id'],
            ],
        ]);

        $this->assertSame(
            ['ok' => true, 'tid' => '123', 'parameter' => 'order_id'],
            $settings->resolveTransactionIdFromRequest(['tid' => '', 'order_id' => ' 123 '])
        );
        $this->assertSame(
            'ambiguous_tid',
            $settings->resolveTransactionIdFromRequest(['tid' => '123', 'order_id' => '123'])['code']
        );
        $this->assertSame(
            'invalid_tid',
            $settings->resolveTransactionIdFromRequest(['tid' => ['123']])['code']
        );
        $this->assertSame(
            'ambiguous_tid',
            $settings->resolveTransactionIdFromRequest(['tid' => '123'], ['tid' => '123'])['code']
        );
        $this->assertSame(
            'invalid_tid',
            $settings->resolveTransactionIdFromRequest(['tid' => "123\n"])['code']
        );
        $this->assertSame(
            'invalid_tid',
            $settings->resolveTransactionIdFromRequest([
                'tid' => str_repeat('x', ConversionSettings::MAX_TRANSACTION_ID_VALUE_BYTES + 1),
            ])['code']
        );
        $this->assertSame(
            ['ok' => true, 'tid' => null, 'parameter' => null],
            $settings->resolveTransactionIdFromRequest([])
        );
    }

    public function testPostbackInputReadsOnlyExplicitQueryAndFormSources(): void
    {
        $this->assertSame(
            ['ok' => true, 'value' => 'query-click'],
            PostbackInput::readString(['clickid' => 'query-click'], ['status' => 'Lead'], 'clickid')
        );
        $this->assertSame(
            'ambiguous_parameter',
            PostbackInput::readString(
                ['clickid' => 'query-click'],
                ['clickid' => 'body-click'],
                'clickid'
            )['code']
        );
        $this->assertSame(
            'invalid_parameter',
            PostbackInput::readString(['clickid' => ['query-click']], [], 'clickid')['code']
        );
        $this->assertStringNotContainsString(
            '$_REQUEST',
            file_get_contents(__DIR__ . '/../../code/api/postback.php')
        );
    }

    public function testTransactionIdDeduplicationUsesParameterNamespace(): void
    {
        $this->campaign->conversions->transactionIdParameters = ['order_id', 'transaction_id'];
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1']]);
        $service = new ConversionService($this->db);

        $first = $service->record(
            $this->campaign,
            'c1',
            'purchase',
            'postback',
            10,
            'USD',
            '123',
            'order_id'
        );
        $secondNamespace = $service->record(
            $this->campaign,
            'c1',
            'purchase',
            'postback',
            5,
            'USD',
            '123',
            'transaction_id'
        );
        $duplicate = $service->record(
            $this->campaign,
            'c1',
            'purchase',
            'postback',
            5,
            'USD',
            '123',
            'order_id'
        );

        $this->assertTrue($first['accepted']);
        $this->assertTrue($secondNamespace['accepted']);
        $this->assertSame('duplicate_tid', $duplicate['code']);
        $this->assertSame(
            ['order_id', 'transaction_id'],
            array_column($this->db->fetchConversions('c1'), 'tid_parameter')
        );
    }

    public function testTransactionIdDeduplicationUsesNamespacedIndex(): void
    {
        $sqlite = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $columns = [];
        $result = $sqlite->query('PRAGMA index_info(idx_conversion_campaign_tid_parameter)');
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }
        $plan = $sqlite->querySingle(
            "EXPLAIN QUERY PLAN SELECT 1 FROM conversions "
            . "WHERE campaign_id = 1 AND tid_parameter = 'order_id' "
            . "AND tid = '123' AND tid <> '' LIMIT 1",
            true
        );
        $sqlite->close();

        $this->assertSame(['campaign_id', 'tid_parameter', 'tid'], $columns);
        $this->assertStringContainsString(
            'idx_conversion_campaign_tid_parameter',
            (string)($plan['detail'] ?? '')
        );
    }

    public function testPaidRepeatWithoutTidCanBeAcceptedAsUpsell(): void
    {
        $this->campaign->conversions->tidDeduplicationEnabled = false;
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1']]);
        $service = new ConversionService($this->db);
        $this->assertTrue($service->record($this->campaign, 'c1', 'purchase', 'postback', 10)['accepted']);

        $rejected = $service->record($this->campaign, 'c1', 'purchase', 'postback', 5);
        $this->campaign->conversions->paidRepeatWithoutTid = 'upsell';
        $accepted = $service->record($this->campaign, 'c1', 'purchase', 'postback', 5);

        $this->assertSame('duplicate_status', $rejected['code']);
        $this->assertTrue($accepted['accepted']);
        $this->assertSame(0, $this->db->fetchConversions('c1')[1]['changes_status']);
    }

    public function testDisabledDeduplicationRetainsAndAllowsRepeatedTid(): void
    {
        $this->campaign->conversions->tidDeduplicationEnabled = false;
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1']]);
        $service = new ConversionService($this->db);

        $this->assertTrue($service->record($this->campaign, 'c1', 'purchase', 'postback', 10, 'USD', 'same-tid')['accepted']);
        $this->assertTrue($service->record($this->campaign, 'c1', 'purchase', 'postback', 5, 'USD', 'same-tid')['accepted']);
        $this->assertSame(['same-tid', 'same-tid'], array_column($this->db->fetchConversions('c1'), 'tid'));
        $this->assertSame(['tid', 'tid'], array_column($this->db->fetchConversions('c1'), 'tid_parameter'));
    }

    public function testUnknownStatusIsRejectedWithoutChangingClick(): void
    {
        $this->db->seedClicks([['clickid' => 'c1', 'userid' => 'u1']]);
        $result = (new ConversionService($this->db))->record($this->campaign, 'c1', 'not-in-catalog', 'site_script');

        $this->assertSame('unknown_status', $result['code']);
        $this->assertCount(0, $this->db->fetchConversions('c1'));
        $this->assertEmpty($this->db->get_click_by_clickid('c1')['status']);
    }

    public function testCampaignAndFlowCapsCountAllAcceptedRows(): void
    {
        $this->campaign->statistics->timezone = 'Europe/Samara';
        $this->db->seedClicks([
            ['clickid' => 'c1', 'userid' => 'u1', 'flow' => 'Flow 1'],
            ['clickid' => 'c2', 'userid' => 'u2', 'flow' => 'Flow 2'],
            ['clickid' => 'c3', 'userid' => 'u3', 'flow' => 'Flow 1'],
        ]);
        $this->db->seedConversions([[
            'clickid' => 'c3',
            'status' => 'Reg',
            'flow' => 'Flow 1',
            'time' => (new DateTimeImmutable('yesterday 12:00', new DateTimeZone('Europe/Samara')))->getTimestamp(),
            'is_initial' => true,
        ]]);
        $service = new ConversionService($this->db);
        $this->assertTrue($service->record($this->campaign, 'c1', 'reg', 'postback')['accepted']);
        $this->assertTrue($service->record($this->campaign, 'c2', 'reg', 'postback')['accepted']);
        $flow1 = FlowSettings::fromArray(['name' => 'Flow 1']);
        $filter = ['operator' => 'less', 'value' => ['statuses' => ['Reg'], 'limit' => 2]];

        $this->assertFalse(ConversionCapEvaluator::matches($this->db, $this->campaign, $flow1, 'conversion_cap_campaign', $filter));
        $this->assertTrue(ConversionCapEvaluator::matches($this->db, $this->campaign, $flow1, 'conversion_cap_flow', $filter));
        $filter['operator'] = 'equal';
        $filter['value']['limit'] = 1;
        $this->assertTrue(ConversionCapEvaluator::matches($this->db, $this->campaign, $flow1, 'conversion_cap_flow', $filter));

        foreach ([
            ['less', 3],
            ['less_or_equal', 2],
            ['equal', 2],
            ['not_equal', 3],
            ['greater_or_equal', 2],
            ['greater', 1],
        ] as [$operator, $limit]) {
            $this->assertTrue(ConversionCapEvaluator::matches(
                $this->db,
                $this->campaign,
                $flow1,
                'conversion_cap_campaign',
                ['operator' => $operator, 'value' => ['statuses' => ['Reg'], 'limit' => $limit]]
            ), "Operator {$operator} should match count 2 against {$limit}");
        }
    }

    public function testCapQueriesUseStatusFirstCoveringIndexes(): void
    {
        $sqlite = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $campaignPlan = $sqlite->querySingle(
            "EXPLAIN QUERY PLAN SELECT COUNT(*) FROM conversions "
            . "WHERE campaign_id = 1 AND time >= 0 AND time < 2000000000 "
            . "AND status COLLATE NOCASE IN ('Purchase')",
            true
        );
        $flowPlan = $sqlite->querySingle(
            "EXPLAIN QUERY PLAN SELECT COUNT(*) FROM conversions "
            . "WHERE campaign_id = 1 AND time >= 0 AND time < 2000000000 "
            . "AND status COLLATE NOCASE IN ('Purchase') AND flow = 'Flow 1'",
            true
        );
        $sqlite->close();

        $this->assertStringContainsString(
            'idx_conversion_cap_campaign_status_time',
            (string)($campaignPlan['detail'] ?? '')
        );
        $this->assertStringContainsString(
            'idx_conversion_cap_flow_status_time',
            (string)($flowPlan['detail'] ?? '')
        );
    }
}
