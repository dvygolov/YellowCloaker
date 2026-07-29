<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../code/admin/campaignvalidation.php';

final class CampaignEventValidationTest extends TestCase
{
    public function testValidEventsAreStoredInCanonicalForm(): void
    {
        $input = [
            'events' => [
                'scroll' => ['use' => 'true', 'thresholds' => '90, 50, 50'],
                'time' => ['use' => 'false', 'thresholds' => ''],
                'performance' => ['use' => '1'],
                'custom' => ['cta_click', 'form_open'],
            ],
        ];

        self::assertNull(normalize_event_input($input));
        self::assertSame([
            'scroll' => ['use' => true, 'thresholds' => [50, 90]],
            'time' => ['use' => false, 'thresholds' => []],
            'performance' => ['use' => true],
            'custom' => ['cta_click', 'form_open'],
        ], $input['events']);
    }

    public function testDisabledCollectorsDiscardHiddenGarbageWithoutBlockingSave(): void
    {
        $input = [
            'events' => [
                'scroll' => [
                    'use' => false,
                    'thresholds' => '90,,,oops,0,50,101,50',
                ],
                'time' => [
                    'use' => false,
                    'thresholds' => [120, null, [], '60', '999999', '60'],
                ],
                'performance' => ['use' => false],
                'custom' => [],
            ],
        ];

        self::assertNull(normalize_event_input($input));
        self::assertSame([50, 90], $input['events']['scroll']['thresholds']);
        self::assertSame([60, 120], $input['events']['time']['thresholds']);
    }

    public function testEnabledCollectorRejectsRatherThanDropsGarbage(): void
    {
        $input = [
            'events' => [
                'scroll' => ['use' => true, 'thresholds' => '50,,oops,90'],
                'time' => ['use' => false, 'thresholds' => []],
                'performance' => ['use' => false],
                'custom' => [],
            ],
        ];

        self::assertSame(
            'Scroll thresholds must be whole numbers.',
            normalize_event_input($input)
        );
    }

    public function testServerNormalizationMatchesClientFixtures(): void
    {
        $fixtures = json_decode(
            (string)file_get_contents(__DIR__ . '/fixtures/event-threshold-normalization.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($fixtures as $fixture) {
            $eventKey = $fixture['maximum'] === 100 ? 'scroll' : 'time';
            $otherKey = $eventKey === 'scroll' ? 'time' : 'scroll';
            $input = [
                'events' => [
                    $eventKey => ['use' => false, 'thresholds' => $fixture['raw']],
                    $otherKey => ['use' => false, 'thresholds' => []],
                    'performance' => ['use' => false],
                    'custom' => [],
                ],
            ];

            self::assertNull(normalize_event_input($input));
            self::assertSame(
                $fixture['expected'],
                $input['events'][$eventKey]['thresholds']
            );
        }
    }

    #[DataProvider('invalidEventsProvider')]
    public function testInvalidEventsAreRejected(array $events, string $message): void
    {
        $input = ['events' => $events];
        self::assertSame($message, normalize_event_input($input));
    }

    public static function invalidEventsProvider(): array
    {
        $base = [
            'scroll' => ['use' => false, 'thresholds' => [50]],
            'time' => ['use' => false, 'thresholds' => [60]],
            'performance' => ['use' => false],
            'custom' => [],
        ];

        $enabledWithoutThreshold = $base;
        $enabledWithoutThreshold['scroll'] = ['use' => true, 'thresholds' => ''];

        $tooManyThresholds = $base;
        $tooManyThresholds['time'] = [
            'use' => true,
            'thresholds' => range(1, EventSettings::MAX_THRESHOLDS + 1),
        ];

        $reservedCustom = $base;
        $reservedCustom['custom'] = ['performance_lcp'];

        $duplicateCustom = $base;
        $duplicateCustom['custom'] = ['cta_click', 'cta_click'];

        return [
            'enabled collector needs a threshold' => [
                $enabledWithoutThreshold,
                'Add at least one scroll threshold before enabling it.',
            ],
            'threshold count is capped' => [
                $tooManyThresholds,
                'Visible-time supports at most 32 thresholds.',
            ],
            'reserved custom name' => [
                $reservedCustom,
                "Custom event name 'performance_lcp' is reserved.",
            ],
            'duplicate custom name' => [
                $duplicateCustom,
                'Duplicate custom event name: cta_click.',
            ],
        ];
    }
}
