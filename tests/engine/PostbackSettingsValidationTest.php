<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/admin/campaignvalidation.php';

final class PostbackSettingsValidationTest extends TestCase
{
    public function testS2sRulesAreNormalizedAgainstCurrentCampaignCatalogs(): void
    {
        $input = $this->validInput();
        $input['postback']['pbkey'] = [
            'enabled' => 'true',
            'keys' => ' alpha, beta, alpha, ',
        ];
        $input['postback']['s2s'][0] = [
            'url' => ' https://receiver.example/goal?status={status}&event={event} ',
            'method' => 'post',
            'statuses' => ['purchase', 'Purchase'],
            'events' => ['scroll_50', 'cta_click', 'scroll_50'],
        ];

        self::assertNull(normalize_event_input($input));
        self::assertNull(normalize_conversion_input($input));
        self::assertNull(normalize_postback_input($input));
        self::assertSame(
            ['enabled' => true, 'keys' => ['alpha', 'beta']],
            $input['postback']['pbkey']
        );
        self::assertSame(
            [[
                'url' => 'https://receiver.example/goal?status={status}&event={event}',
                'method' => 'POST',
                'statuses' => ['Purchase'],
                'events' => ['scroll_50', 'cta_click'],
            ]],
            $input['postback']['s2s']
        );
    }

    public function testS2sSupportsStatusOnlyAndEventOnlyRules(): void
    {
        $input = $this->validInput();
        $input['postback']['s2s'] = [
            [
                'url' => 'https://receiver.example/conversion',
                'method' => 'GET',
                'statuses' => ['Lead'],
                'events' => [],
            ],
            [
                'url' => 'https://receiver.example/event',
                'method' => 'POST',
                'statuses' => [],
                'events' => ['stay_60s'],
            ],
        ];

        self::assertNull(normalize_event_input($input));
        self::assertNull(normalize_conversion_input($input));
        self::assertNull(normalize_postback_input($input));
    }

    #[DataProvider('invalidS2sProvider')]
    public function testInvalidS2sRulesAreRejected(callable $mutate, string $message): void
    {
        $input = $this->validInput();
        $mutate($input);
        self::assertNull(normalize_event_input($input));
        self::assertNull(normalize_conversion_input($input));

        self::assertSame($message, normalize_postback_input($input));
    }

    public static function invalidS2sProvider(): array
    {
        return [
            'legacy status events field' => [
                static function (array &$input): void {
                    unset($input['postback']['s2s'][0]['statuses']);
                    $input['postback']['s2s'][0]['events'] = ['Purchase'];
                },
                'S2S postback #1 conversion statuses must be a list.',
            ],
            'non-http URL' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['url'] = 'ftp://receiver.example/goal';
                },
                'S2S postback #1 URL must be a valid http:// or https:// address.',
            ],
            'URL credentials' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['url'] = 'https://user:pass@receiver.example/goal';
                },
                'S2S postback #1 URL must be a valid http:// or https:// address.',
            ],
            'backslash in URL' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['url'] = 'https://receiver.example/a\\b';
                },
                'S2S postback #1 URL must be a valid http:// or https:// address.',
            ],
            'malformed percent escape' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['url'] = 'https://receiver.example/hook?x=%zz';
                },
                'S2S postback #1 URL must be a valid http:// or https:// address.',
            ],
            'unknown method' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['method'] = 'PUT';
                },
                'S2S postback #1 method must be GET or POST.',
            ],
            'unknown status' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['statuses'] = ['Missing'];
                },
                'S2S postback #1 conversion statuses contains an unavailable value: Missing.',
            ],
            'unknown event' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['events'] = ['missing_event'];
                },
                'S2S postback #1 events contains an unavailable value: missing_event.',
            ],
            'performance is not an event trigger' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['events'] = ['performance'];
                },
                'S2S postback #1 events contains an unavailable value: performance.',
            ],
            'no triggers' => [
                static function (array &$input): void {
                    $input['postback']['s2s'][0]['statuses'] = [];
                    $input['postback']['s2s'][0]['events'] = [];
                },
                'S2S postback #1 must select at least one conversion status or event.',
            ],
            'too many rules' => [
                static function (array &$input): void {
                    $input['postback']['s2s'] = array_fill(
                        0,
                        PostbackSettings::MAX_S2S_POSTBACKS + 1,
                        $input['postback']['s2s'][0]
                    );
                },
                'A campaign supports at most 5 S2S postbacks.',
            ],
        ];
    }

    public function testS2sModelUsesSeparateStatusAndEventListsWithoutLegacyMapping(): void
    {
        $settings = PostbackSettings::fromArray([
            'pbkey' => ['enabled' => false, 'keys' => []],
            's2s' => [[
                'url' => 'https://receiver.example/goal',
                'method' => 'GET',
                'events' => ['Purchase'],
            ]],
        ]);

        self::assertSame([], $settings->s2sPostbacks[0]->statuses);
        self::assertSame(['Purchase'], $settings->s2sPostbacks[0]->events);
        self::assertSame(
            [
                'pbkey' => ['enabled' => false, 'keys' => []],
                's2s' => [[
                    'url' => 'https://receiver.example/goal',
                    'method' => 'GET',
                    'statuses' => [],
                    'events' => ['Purchase'],
                ]],
            ],
            json_decode(json_encode($settings, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    private function validInput(): array
    {
        return [
            'events' => [
                'scroll' => ['use' => true, 'thresholds' => [50]],
                'time' => ['use' => true, 'thresholds' => [60]],
                'performance' => ['use' => true],
                'custom' => ['cta_click'],
            ],
            'conversions' => [
                'statuses' => array_map(
                    static fn(ConversionStatus $status): array => $status->jsonSerialize(),
                    ConversionSettings::defaultStatuses()
                ),
                'deduplication' => [
                    'enabled' => true,
                    'transaction_id_parameters' => ['tid'],
                    'paid_repeat_without_tid' => 'reject',
                ],
                'form' => ['enabled' => false, 'status' => 'Lead'],
                'site' => ['enabled' => false],
            ],
            'postback' => [
                'pbkey' => ['enabled' => false, 'keys' => []],
                's2s' => [[
                    'url' => 'https://receiver.example/goal',
                    'method' => 'GET',
                    'statuses' => ['Purchase'],
                    'events' => [],
                ]],
            ],
        ];
    }
}
