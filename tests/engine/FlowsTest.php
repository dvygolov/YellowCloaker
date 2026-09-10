<?php

use PHPUnit\Framework\TestCase;

class FlowsTest extends TestCase
{
    private function folder(string $name, int $weight = 0, array $mvt = []): array
    {
        return [
            'name' => $name,
            'loadtype' => 'base',
            'weight' => $weight,
            'mvt' => $mvt + ['enabled' => false, 'tests' => []],
        ];
    }

    // ── FlowSettings::fromArray with steps ──

    public function testFlowSettingsFromArray(): void
    {
        $data = [
            'name' => 'Test Flow',
            'filters' => ['condition' => 'AND', 'rules' => []],
            'steps' => [
                ['action' => 'folder', 'folders' => [$this->folder('pre1')], 'redirect' => ['urls' => [], 'type' => 302]],
                ['action' => 'folder', 'folders' => [$this->folder('land1', 100)], 'redirect' => ['urls' => [], 'type' => 302]]
            ]
        ];
        $flow = FlowSettings::fromArray($data);
        $this->assertEquals('Test Flow', $flow->name);
        $this->assertCount(2, $flow->steps);
        $this->assertEquals('folder', $flow->steps[0]->action);
        $this->assertEquals(['pre1'], $flow->steps[0]->getItems());
        $this->assertEquals('folder', $flow->steps[1]->action);
        $this->assertEquals([100], $flow->steps[1]->getWeights());
    }

    public function testFlowSettingsDefaultName(): void
    {
        $data = [
            'steps' => [
                ['action' => 'folder', 'folders' => [$this->folder('l')], 'redirect' => ['urls' => [], 'type' => 302]]
            ]
        ];
        $flow = FlowSettings::fromArray($data);
        $this->assertEquals('Flow', $flow->name);
        $this->assertEquals([], $flow->filters);
    }

    // ── BlackSettings with flows ──

    public function testBlackSettingsFlows(): void
    {
        $data = [
            'jsconnect' => 'replace',
            'jsbotdetection' => ['enabled' => false, 'events' => [], 'timeout' => 3, 'timezone' => ['min' => -12, 'max' => 12]],
            'flows' => [
                ['name' => 'F1', 'filters' => [], 'steps' => [
                    ['action' => 'folder', 'folders' => [$this->folder('l1')], 'redirect' => ['urls' => [], 'type' => 302]]
                ]],
                ['name' => 'F2', 'filters' => [], 'steps' => [
                    ['action' => 'folder', 'folders' => [$this->folder('p1', 100)], 'redirect' => ['urls' => [], 'type' => 302]],
                    ['action' => 'redirect', 'folders' => [], 'redirect' => ['urls' => [['url' => 'https://x.com', 'label' => 'x.com', 'weight' => 100]], 'type' => 301]]
                ]]
            ]
        ];
        $bs = BlackSettings::fromArray($data);
        $this->assertCount(2, $bs->flows);
        $this->assertEquals('F1', $bs->flows[0]->name);
        $this->assertEquals('F2', $bs->flows[1]->name);
        $this->assertCount(2, $bs->flows[1]->steps);
        $this->assertTrue($bs->flows[1]->steps[1]->isRedirect());
    }

    // ── StepSettings defaults ──

    public function testStepSettingsDefaults(): void
    {
        $data = ['action' => 'folder', 'folders' => [$this->folder('p1')]];
        $ss = StepSettings::fromArray($data);
        $this->assertEquals('folder', $ss->action);
        $this->assertEquals(['p1'], $ss->getItems());
        $this->assertEquals([0], $ss->getWeights());
        $this->assertFalse($ss->isRedirect());
        $this->assertTrue($ss->isFolder());
    }

    public function testStepSettingsRedirect(): void
    {
        $data = ['action' => 'redirect', 'folders' => [], 'redirect' => ['urls' => [['url' => 'https://a.com', 'label' => 'a.com', 'weight' => 100]], 'type' => 301]];
        $ss = StepSettings::fromArray($data);
        $this->assertTrue($ss->isRedirect());
        $this->assertEquals(301, $ss->redirectType);
        $this->assertEquals('https://a.com', $ss->getRedirectUrlByLabel('a.com'));
    }

    // ── Tds::pick_flow ──

    private function makeFlow(string $name, array $filters): FlowSettings
    {
        return FlowSettings::fromArray([
            'name' => $name,
            'filters' => $filters,
            'steps' => [
                ['action' => 'folder', 'folders' => [$this->folder('l')], 'redirect' => ['urls' => [], 'type' => 302]]
            ]
        ]);
    }

    public function testPickFlowCatchAll(): void
    {
        $clkr = new FiltrationCore();
        $flow = $this->makeFlow('Catch-all', []);
        $result = Tds::pick_flow_index($clkr, [$flow]);
        $this->assertNotNull($result);
        $this->assertEquals(0, $result);
    }

    public function testPickFlowNoMatch(): void
    {
        $clkr = new FiltrationCore();
        $flow = $this->makeFlow('RU only', [
            'condition' => 'AND',
            'rules' => [['id' => 'country', 'field' => 'country', 'type' => 'string', 'input' => 'text', 'operator' => 'equal', 'value' => 'XX']]
        ]);
        $result = Tds::pick_flow_index($clkr, [$flow]);
        $this->assertNull($result);
    }

    public function testBotFilterMatchesServerSideDeviceDetectorResult(): void
    {
        $bot = new FiltrationCore([
            'tds_ua' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ]);
        $human = new FiltrationCore([
            'tds_ua' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ]);
        $botRule = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'bot',
                'field' => 'bot',
                'type' => 'string',
                'operator' => 'equal',
                'value' => 'yes',
            ]],
        ];

        $this->assertSame('yes', $bot->click_params['bot']);
        $this->assertSame('no', $human->click_params['bot']);
        $this->assertTrue($bot->click_matches_filters($botRule));
        $this->assertFalse($human->click_matches_filters($botRule));
    }

    public function testDeviceMobileAliasMatchesPhoneTypesOnly(): void
    {
        $rule = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'device',
                'field' => 'device',
                'type' => 'string',
                'operator' => 'in',
                'value' => 'mobile',
            ]],
        ];

        foreach (['smartphone', 'feature phone', 'phablet'] as $device) {
            $core = new FiltrationCore();
            $core->click_params['device'] = $device;
            $this->assertTrue($core->click_matches_filters($rule), $device);
        }

        foreach (['tablet', 'desktop'] as $device) {
            $core = new FiltrationCore();
            $core->click_params['device'] = $device;
            $this->assertFalse($core->click_matches_filters($rule), $device);
        }
    }

    public function testDeviceMobileAliasWorksWithNotIn(): void
    {
        $rule = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'device',
                'field' => 'device',
                'type' => 'string',
                'operator' => 'not_in',
                'value' => 'mobile',
            ]],
        ];

        $phone = new FiltrationCore();
        $phone->click_params['device'] = 'smartphone';
        $desktop = new FiltrationCore();
        $desktop->click_params['device'] = 'desktop';

        $this->assertFalse($phone->click_matches_filters($rule));
        $this->assertTrue($desktop->click_matches_filters($rule));
    }

    public function testDeviceOtherAliasMatchesRemainingDeviceTypesOnly(): void
    {
        $rule = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'device',
                'field' => 'device',
                'type' => 'string',
                'operator' => 'in',
                'value' => 'other',
            ]],
        ];

        foreach ([
            'console',
            'tv',
            'car browser',
            'smart display',
            'camera',
            'portable media player',
            'smart speaker',
            'wearable',
            'peripheral',
        ] as $device) {
            $core = new FiltrationCore();
            $core->click_params['device'] = $device;
            $this->assertTrue($core->click_matches_filters($rule), $device);
        }

        foreach (['desktop', 'smartphone', 'feature phone', 'phablet', 'tablet'] as $device) {
            $core = new FiltrationCore();
            $core->click_params['device'] = $device;
            $this->assertFalse($core->click_matches_filters($rule), $device);
        }
    }

    public function testDeviceOtherAliasWorksWithNotIn(): void
    {
        $rule = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'device',
                'field' => 'device',
                'type' => 'string',
                'operator' => 'not_in',
                'value' => 'other',
            ]],
        ];

        $tv = new FiltrationCore();
        $tv->click_params['device'] = 'tv';
        $tablet = new FiltrationCore();
        $tablet->click_params['device'] = 'tablet';

        $this->assertFalse($tv->click_matches_filters($rule));
        $this->assertTrue($tablet->click_matches_filters($rule));
    }

    public function testPickFlowFirstMatchWins(): void
    {
        $clkr = new FiltrationCore();
        $flow1 = $this->makeFlow('No rules', [
            'condition' => 'AND',
            'rules' => [['id' => 'country', 'field' => 'country', 'type' => 'string', 'input' => 'text', 'operator' => 'equal', 'value' => 'ZZ']]
        ]);
        $flow2 = $this->makeFlow('Catch-all', []);
        $result = Tds::pick_flow_index($clkr, [$flow1, $flow2]);
        $this->assertNotNull($result);
        $this->assertEquals(1, $result);
    }

    public function testFailedConversionCapContinuesToNextFlow(): void
    {
        $clkr = new FiltrationCore();
        $capped = $this->makeFlow('Capped', [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'conversion_cap_campaign',
                'field' => 'conversion_cap_campaign',
                'type' => 'string',
                'operator' => 'less',
                'value' => ['statuses' => ['Purchase'], 'limit' => 10],
            ]],
        ]);
        $fallback = $this->makeFlow('Fallback', []);

        $result = Tds::pick_flow_index(
            $clkr,
            [$capped, $fallback],
            static fn(string $kind): bool => $kind !== 'conversion_cap_campaign'
        );

        $this->assertSame(1, $result);
    }

    public function testPickFlowEmptyArray(): void
    {
        $clkr = new FiltrationCore();
        $result = Tds::pick_flow_index($clkr, []);
        $this->assertNull($result);
    }

    // ── JSON serialization round-trip ──

    public function testFlowSettingsJsonRoundTrip(): void
    {
        $data = [
            'name' => 'RT',
            'filters' => ['condition' => 'OR', 'rules' => []],
            'steps' => [
                ['action' => 'folder', 'folders' => [$this->folder('p1', 60), $this->folder('p2', 40)], 'redirect' => ['urls' => [], 'type' => 302]],
                ['action' => 'redirect', 'folders' => [], 'redirect' => ['urls' => [['url' => 'https://a.com', 'label' => 'a.com', 'weight' => 100]], 'type' => 301]]
            ]
        ];
        $flow = FlowSettings::fromArray($data);
        $json = json_encode($flow);
        $decoded = json_decode($json, true);
        $flow2 = FlowSettings::fromArray($decoded);
        $this->assertEquals($flow->name, $flow2->name);
        $this->assertCount(2, $flow2->steps);
        $this->assertEquals($flow->steps[0]->getWeights(), $flow2->steps[0]->getWeights());
        $this->assertEquals($flow->steps[1]->redirectUrls, $flow2->steps[1]->redirectUrls);
    }

    // ── Weight normalization (campeditor.php function) ──

    public function testNormalizeWeightsToHundred(): void
    {
        require_once __DIR__ . '/../../code/admin/campeditor.php';

        $items1 = [['weight' => 50], ['weight' => 50]];
        $items2 = [['weight' => 30], ['weight' => 20], ['weight' => 50]];
        normalize_item_weights($items1);
        normalize_item_weights($items2);
        $this->assertEquals(100, array_sum(array_column($items1, 'weight')));
        $this->assertEquals(100, array_sum(array_column($items2, 'weight')));
    }

    public function testNormalizeWeightsRescale(): void
    {
        require_once __DIR__ . '/../../code/admin/campeditor.php';

        $items1 = [['weight' => 1], ['weight' => 1], ['weight' => 1]];
        $items2 = [['weight' => 10], ['weight' => 20]];
        normalize_item_weights($items1);
        normalize_item_weights($items2);
        $this->assertEqualsWithDelta(100, array_sum(array_column($items1, 'weight')), 0.1);
        $this->assertEquals(100, array_sum(array_column($items2, 'weight')));
    }

    public function testSettingsMergePreservesNewNestedObjects(): void
    {
        require_once __DIR__ . '/../../code/admin/campeditor.php';

        $incoming = [
            'condition' => 'AND',
            'rules' => [[
                'id' => 'conversion_cap_campaign',
                'value' => [
                    'statuses' => ['Purchase', 'Reg'],
                    'limit' => 100,
                ],
            ]],
            'valid' => true,
        ];

        $merged = mergeSettingsRecursive(null, $incoming);

        $this->assertSame('AND', $merged['condition']);
        $this->assertSame(['Purchase', 'Reg'], $merged['rules'][0]['value']['statuses']);
        $this->assertSame(100, $merged['rules'][0]['value']['limit']);
        $this->assertTrue($merged['valid']);
    }

    public function testMvtNormalizationPreservesAppendOnlyIdentity(): void
    {
        require_once __DIR__ . '/../../code/admin/campeditor.php';

        $current = [
            'enabled' => true,
            'tests' => [
                [
                    'active' => true,
                    'values' => [
                        ['active' => true, 'value' => 'A'],
                        ['active' => true, 'value' => 'B'],
                    ],
                ],
            ],
        ];
        $incoming = [
            'enabled' => true,
            'tests' => [
                [
                    'active' => true,
                    'values' => [
                        ['active' => true, 'value' => 'A'],
                        ['active' => false, 'value' => 'B'],
                        ['active' => true, 'value' => 'C'],
                    ],
                ],
                [
                    'active' => true,
                    'values' => [
                        ['active' => true, 'value' => 'D'],
                    ],
                ],
            ],
        ];

        $this->assertNull(normalize_mvt_input($incoming, $current, 'landing'));
        $this->assertFalse($incoming['tests'][0]['values'][1]['active']);
        $this->assertSame('C', $incoming['tests'][0]['values'][2]['value']);
        $this->assertCount(2, $incoming['tests']);
    }

    public function testMvtNormalizationRejectsEditingOrRemovingSavedValues(): void
    {
        require_once __DIR__ . '/../../code/admin/campeditor.php';

        $current = [
            'enabled' => true,
            'tests' => [[
                'active' => true,
                'values' => [
                    ['active' => true, 'value' => 'A'],
                    ['active' => true, 'value' => 'B'],
                ],
            ]],
        ];
        $edited = [
            'enabled' => true,
            'tests' => [[
                'active' => true,
                'values' => [
                    ['active' => true, 'value' => 'changed'],
                    ['active' => true, 'value' => 'B'],
                ],
            ]],
        ];
        $removed = [
            'enabled' => true,
            'tests' => [[
                'active' => true,
                'values' => [
                    ['active' => true, 'value' => 'A'],
                ],
            ]],
        ];

        $this->assertStringContainsString(
            'cannot be edited',
            (string)normalize_mvt_input($edited, $current, 'landing')
        );
        $this->assertStringContainsString(
            'cannot be removed',
            (string)normalize_mvt_input($removed, $current, 'landing')
        );
    }

    public function testFlowNormalizationRejectsDuplicateFolderInSameStep(): void
    {
        require_once __DIR__ . '/../../code/admin/campeditor.php';

        $input = [
            'black' => [
                'flows' => [[
                    'name' => 'Flow',
                    'steps' => [[
                        'action' => 'folder',
                        'folders' => [
                            $this->folder('landing'),
                            $this->folder('landing'),
                        ],
                        'redirect' => ['urls' => [], 'type' => 302],
                    ]],
                ]],
            ],
        ];

        $this->assertStringContainsString(
            'cannot be added twice',
            (string)normalize_flow_input($input, ['black' => ['flows' => []]])
        );
    }

    // ── FlowSettings Thompson defaults ──

    public function testFlowSettingsThompsonDefaults(): void
    {
        $data = [
            'steps' => [['action' => 'folder', 'folders' => [$this->folder('l')], 'redirect' => ['urls' => [], 'type' => 302]]]
        ];
        $flow = FlowSettings::fromArray($data);
        $this->assertEquals('equal', $flow->distribution);
        $this->assertEquals('Lead', $flow->optimize_for);
        $this->assertEquals('funnels', $flow->optimize_mode);
    }

    public function testFlowSettingsThompsonExplicit(): void
    {
        $data = [
            'steps' => [['action' => 'folder', 'folders' => [$this->folder('l')], 'redirect' => ['urls' => [], 'type' => 302]]],
            'distribution' => 'thompson',
            'optimize_for' => 'Purchase',
            'optimize_mode' => 'separate'
        ];
        $flow = FlowSettings::fromArray($data);
        $this->assertEquals('thompson', $flow->distribution);
        $this->assertEquals('Purchase', $flow->optimize_for);
        $this->assertEquals('separate', $flow->optimize_mode);
    }

    public function testMvtUsesAppendOnlyArrayPositionsForIdsAndCodes(): void
    {
        $step = StepSettings::fromArray([
            'action' => 'folder',
            'folders' => [$this->folder('landing', 100, [
                'enabled' => true,
                'tests' => [[
                    'active' => true,
                    'values' => [
                        ['active' => true, 'value' => 'A HTML'],
                        ['active' => false, 'value' => 'B HTML'],
                        ['active' => true, 'value' => 'C HTML'],
                    ],
                ]],
            ])],
            'redirect' => ['urls' => [], 'type' => 302],
        ]);

        $this->assertTrue($step->getMvt('landing')->enabled);
        $this->assertSame('A', MvtSettings::valueCode(0));
        $this->assertSame('Z', MvtSettings::valueCode(25));
        $this->assertSame('AA', MvtSettings::valueCode(26));
        $this->assertSame(26, MvtSettings::valueIndex('AA'));
        $this->assertFalse($step->getMvt('landing')->tests[0]->values[1]->active);
        $this->assertArrayNotHasKey('weights', json_decode(json_encode($step), true));
        $this->assertArrayNotHasKey('folderloadtypes', json_decode(json_encode($step), true));
    }
}
