<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../code/admin/campaignvalidation.php';

final class CampaignUniquenessValidationTest extends TestCase
{
    public function testValidInputIsNormalized(): void
    {
        $input = [
            'uniqueness' => [
                'enabled' => 'true',
                'method' => 'get',
                'ttl_hours' => '720',
                'get_parameter' => 'visitor',
            ],
        ];

        $this->assertNull(normalize_uniqueness_input($input));
        $this->assertTrue($input['uniqueness']['enabled']);
        $this->assertSame(720, $input['uniqueness']['ttl_hours']);
        $this->assertSame('visitor', $input['uniqueness']['get_parameter']);
    }

    #[DataProvider('invalidInputProvider')]
    public function testInvalidInputIsRejected(array $uniqueness, string $expectedMessage): void
    {
        $input = ['uniqueness' => $uniqueness];
        $this->assertSame($expectedMessage, normalize_uniqueness_input($input));
    }

    public static function invalidInputProvider(): array
    {
        $ttlMessage = 'Uniqueness TTL must be a whole number from 1 to 720 hours.';
        return [
            'unknown method' => [[
                'enabled' => true,
                'method' => 'fingerprint',
                'ttl_hours' => 24,
                'get_parameter' => '',
            ], 'Invalid uniqueness method.'],
            'zero ttl' => [[
                'enabled' => true,
                'method' => 'ip',
                'ttl_hours' => 0,
                'get_parameter' => '',
            ], $ttlMessage],
            'fractional ttl' => [[
                'enabled' => true,
                'method' => 'ip',
                'ttl_hours' => '1.5',
                'get_parameter' => '',
            ], $ttlMessage],
            'ttl over maximum' => [[
                'enabled' => true,
                'method' => 'ip',
                'ttl_hours' => 721,
                'get_parameter' => '',
            ], $ttlMessage],
            'non-string GET name' => [[
                'enabled' => true,
                'method' => 'get',
                'ttl_hours' => 24,
                'get_parameter' => ['visitor'],
            ], 'GET parameter name must be a string.'],
        ];
    }

    public function testNestedUniquenessRulesReturnAffectedFlowNames(): void
    {
        $flows = [
            [
                'name' => 'Primary',
                'filters' => [
                    'condition' => 'OR',
                    'rules' => [[
                        'condition' => 'AND',
                        'rules' => [[
                            'id' => 'uniqueness',
                            'field' => 'uniqueness',
                            'operator' => 'is_unique',
                            'value' => 'campaign',
                        ]],
                    ]],
                ],
            ],
            ['name' => 'Ordinary', 'filters' => ['rules' => []]],
            ['name' => '', 'filters' => ['rules' => [['field' => 'uniqueness']]]],
            ['name' => 'Primary', 'filters' => ['rules' => [['id' => 'uniqueness']]]],
        ];

        $this->assertSame(['Primary', 'Flow 3'], find_uniqueness_rule_flows($flows));
    }
}
