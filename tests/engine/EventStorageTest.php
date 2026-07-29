<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestDb.php';
if (!defined('YELLOWTDS_EVENTS_API_NO_RUN')) {
    define('YELLOWTDS_EVENTS_API_NO_RUN', true);
}
require_once __DIR__ . '/../../code/api/events.php';

final class EventStorageTest extends TestCase
{
    private TestDb $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/yellowtds_events_' . bin2hex(random_bytes(6)) . '.db';
        $this->db = new TestDb($this->dbPath);
        $this->db->initSchema();
        $this->db->seedCampaign(1, 'Events', [
            'events' => [
                'scroll' => ['use' => true, 'thresholds' => [50]],
                'time' => ['use' => true, 'thresholds' => [60]],
                'performance' => ['use' => true],
                'custom' => ['cta_click'],
            ],
        ]);
        $this->db->seedClicks([[
            'campaign_id' => 1,
            'clickid' => 'click-one',
            'userid' => 'user-one',
            'flow' => 'Flow 1',
            'path' => ['landing-a'],
            'step' => 0,
        ]]);
    }

    protected function tearDown(): void
    {
        $this->db->cleanup();
    }

    public function testGreenfieldSchemaKeepsEventsOnlyOnSteps(): void
    {
        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $clickColumns = $this->columnNames($connection, 'clicks');
        $stepColumns = $this->columnNames($connection, 'click_steps');
        $conversionColumns = $this->columnNames($connection, 'conversions');

        self::assertNotContains('events', $clickColumns);
        self::assertContains('events', $stepColumns);
        self::assertContains('step', $conversionColumns);
        self::assertSame(
            0,
            (int)$connection->querySingle(
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'click_event_log'"
            )
        );
        $connection->close();
    }

    public function testSchemaRejectsNonObjectStepEventJson(): void
    {
        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READWRITE);
        self::assertFalse(@$connection->exec(
            "UPDATE click_steps SET events = '[]' WHERE clickid = 'click-one' AND step = 0"
        ));
        self::assertSame(
            '{}',
            $connection->querySingle(
                "SELECT events FROM click_steps WHERE clickid = 'click-one' AND step = 0"
            )
        );
        $connection->close();
    }

    public function testEventSettingsBuildExplicitAllowlist(): void
    {
        $settings = EventSettings::fromArray([
            'scroll' => ['use' => true, 'thresholds' => [75, 50, 75, 101]],
            'time' => ['use' => true, 'thresholds' => [60, 0, 86401]],
            'performance' => ['use' => true],
            'custom' => [
                'cta_click',
                'CTA_CLICK',
                'performance',
                'performance_lcp',
                'scroll_42',
                'scroll_999',
                'stay_45s',
                'stay_999999s',
                'bad-name',
            ],
        ]);

        self::assertSame(['scroll_50', 'scroll_75', 'stay_60s', 'cta_click'], $settings->getConfiguredEventNames());
        self::assertTrue($settings->accepts('cta_click'));
        self::assertFalse($settings->accepts('performance_lcp'));
        self::assertFalse($settings->accepts('scroll_42'));
        self::assertFalse($settings->accepts('stay_45s'));
        self::assertTrue(EventSettings::isReservedEventName('scroll_999'));
        self::assertTrue(EventSettings::isReservedEventName('stay_999999s'));
        self::assertTrue($settings->performanceTrackingUse);
    }

    public function testEventSettingsCapCollectorAndCustomEventCounts(): void
    {
        $settings = EventSettings::fromArray([
            'scroll' => ['use' => true, 'thresholds' => range(1, 100)],
            'time' => ['use' => true, 'thresholds' => range(1, 100)],
            'custom' => array_map(
                static fn(int $index): string => 'custom_' . $index,
                range(1, 100)
            ),
        ]);

        self::assertCount(EventSettings::MAX_THRESHOLDS, $settings->scrollTrackingThresholds);
        self::assertCount(EventSettings::MAX_THRESHOLDS, $settings->timeTrackingThresholds);
        self::assertCount(EventSettings::MAX_CUSTOM_EVENTS, $settings->customEventNames);
        self::assertSame(32, $settings->scrollTrackingThresholds[array_key_last($settings->scrollTrackingThresholds)]);
        self::assertSame('custom_64', $settings->customEventNames[array_key_last($settings->customEventNames)]);
    }

    public function testOrdinaryEventIsFirstWriteWinsAndRequiresExactVariant(): void
    {
        self::assertSame(
            Db::STEP_EVENT_CREATED,
            $this->db->save_step_event('click-one', 0, 'landing-a', 'scroll_50', 1200)
        );
        self::assertSame(
            Db::STEP_EVENT_SAVED,
            $this->db->save_step_event('click-one', 0, 'landing-a', 'scroll_50', 300)
        );
        self::assertSame(
            Db::STEP_EVENT_NOT_FOUND,
            $this->db->save_step_event('click-one', 0, 'landing-b', 'scroll_50', 400)
        );
        self::assertSame(
            Db::STEP_EVENT_NOT_ALLOWED,
            $this->db->save_step_event('click-one', 0, 'landing-a', 'unknown_event', 500)
        );
        self::assertSame(
            Db::STEP_EVENT_NOT_ALLOWED,
            $this->db->save_step_event('click-one', 0, 'landing-a', 'scroll_42', 500)
        );
        self::assertSame(
            Db::STEP_EVENT_NOT_ALLOWED,
            $this->db->save_step_event('click-one', 0, 'landing-a', 'stay_45s', 500)
        );

        self::assertSame(['scroll_50' => 1200], $this->storedEvents());
        self::assertSame(200, yellowtds_events_status_for_result(Db::STEP_EVENT_CREATED));
        self::assertSame(422, yellowtds_events_status_for_result(Db::STEP_EVENT_NOT_ALLOWED));
    }

    public function testPerformancePacketIsStoredAsOneImmutableObject(): void
    {
        self::assertSame(
            Db::STEP_EVENT_CREATED,
            $this->db->save_step_performance('click-one', 0, 'landing-a', [
                'ttfb' => 311,
                'lcp' => 1541,
                'cls' => 0.03,
            ])
        );
        self::assertSame(
            Db::STEP_EVENT_SAVED,
            $this->db->save_step_performance('click-one', 0, 'landing-a', [
                'ttfb' => 1,
                'lcp' => 2,
                'cls' => 0,
            ])
        );

        self::assertSame([
            'performance' => ['ttfb' => 311, 'lcp' => 1541, 'cls' => 0.03],
        ], $this->storedEvents());
    }

    public function testParserAcceptsExactlyOneOrdinaryEventOrPerformancePacket(): void
    {
        $ordinary = yellowtds_parse_events_request(json_encode([
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing-a',
            'event' => 'cta_click',
            'value' => 12.6,
        ], JSON_THROW_ON_ERROR));
        self::assertSame('event', $ordinary['type']);
        self::assertSame(13, $ordinary['value']);

        $performance = yellowtds_parse_events_request(json_encode([
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing-a',
            'performance' => ['fcp' => 720.4, 'cls' => 0],
        ], JSON_THROW_ON_ERROR));
        self::assertSame(['fcp' => 720, 'cls' => 0.0], $performance['performance']);
        self::assertTrue(yellowtds_events_content_type_is_supported('text/plain;charset=UTF-8'));
        self::assertTrue(yellowtds_events_content_type_is_supported('application/json'));
        self::assertFalse(yellowtds_events_content_type_is_supported('application/x-www-form-urlencoded'));
    }

    public function testOrdinaryElapsedTimeCanExceedOneDayButPerformanceTimingCannot(): void
    {
        $ordinary = yellowtds_parse_events_request(json_encode([
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing-a',
            'event' => 'cta_click',
            'value' => 86400001,
        ], JSON_THROW_ON_ERROR));
        self::assertSame(86400001, $ordinary['value']);
        self::assertSame(
            Db::STEP_EVENT_CREATED,
            $this->db->save_step_event(
                'click-one',
                0,
                'landing-a',
                'cta_click',
                86400001
            )
        );

        $this->expectException(InvalidArgumentException::class);
        yellowtds_parse_events_request(json_encode([
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing-a',
            'performance' => ['lcp' => 86400001],
        ], JSON_THROW_ON_ERROR));
    }

    public function testParserRejectsEventsMapAndMixedPacket(): void
    {
        foreach ([
            [
                'clickid' => 'click-one',
                'step_index' => 0,
                'variant' => 'landing-a',
                'events' => ['cta_click' => 12],
            ],
            [
                'clickid' => 'click-one',
                'step_index' => 0,
                'variant' => 'landing-a',
                'event' => 'cta_click',
                'value' => 12,
                'performance' => ['lcp' => 100],
            ],
        ] as $payload) {
            try {
                yellowtds_parse_events_request(json_encode($payload, JSON_THROW_ON_ERROR));
                self::fail('Invalid event payload was accepted');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testPublicApiBoundsBodyReadWithoutTrustingContentLength(): void
    {
        $source = file_get_contents(__DIR__ . '/../../code/api/events.php');
        self::assertIsString($source);
        self::assertStringContainsString(
            'YELLOWTDS_EVENTS_MAX_BODY_BYTES + 1',
            $source
        );
        self::assertStringContainsString(
            'yellowtds_parse_events_request(yellowtds_read_events_body())',
            $source
        );
    }

    /** @return list<string> */
    private function columnNames(SQLite3 $connection, string $table): array
    {
        $result = $connection->query('PRAGMA table_info(' . $table . ')');
        $columns = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = (string)$row['name'];
        }
        return $columns;
    }

    /** @return array<string, mixed> */
    private function storedEvents(): array
    {
        $connection = new SQLite3($this->dbPath, SQLITE3_OPEN_READONLY);
        $json = $connection->querySingle(
            "SELECT events FROM click_steps WHERE clickid = 'click-one' AND step = 0"
        );
        $connection->close();
        $events = json_decode((string)$json, true);
        self::assertIsArray($events);
        return $events;
    }
}
