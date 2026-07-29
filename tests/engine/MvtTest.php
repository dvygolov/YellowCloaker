<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/experiments.php';

final class MvtTest extends TestCase
{
    private function settings(bool $enabled = true): MvtSettings
    {
        return MvtSettings::fromArray([
            'enabled' => $enabled,
            'tests' => [
                [
                    'active' => true,
                    'values' => [
                        ['active' => true, 'value' => '<b>A</b>'],
                        ['active' => false, 'value' => '<b>B archived</b>'],
                        ['active' => true, 'value' => '<b>C</b>'],
                    ],
                ],
                [
                    'active' => false,
                    'values' => [
                        ['active' => true, 'value' => 'Old test'],
                    ],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        session_remove(EXPERIMENT_SESSION_KEY);
        unset($_COOKIE['saved_paths'], $GLOBALS['ytds_saved_paths_state']);
    }

    public function testNewAssignmentsUseOnlyActiveTestsAndValues(): void
    {
        $settings = $this->settings();
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $assignment = mvt_new_assignment($settings);
            $this->assertSame([1], array_keys($assignment));
            $this->assertContains($assignment['1'], ['A', 'C']);
        }
    }

    public function testArchivedAssignmentRemainsRenderableAndReplacesEveryPlaceholder(): void
    {
        $settings = $this->settings();
        $html = '#TEST1# / #TEST1# / #TEST2#';
        $rendered = apply_mvt_assignment(
            $html,
            $settings,
            ['1' => 'B', '2' => 'A']
        );

        $this->assertSame(
            '<b>B archived</b> / <b>B archived</b> / Old test',
            $rendered
        );
    }

    public function testDisabledMvtDoesNotReplacePlaceholders(): void
    {
        $this->assertSame(
            '#TEST1#',
            apply_mvt_assignment('#TEST1#', $this->settings(false), ['1' => 'A'])
        );
    }

    public function testUnknownAssignmentIsRejected(): void
    {
        $settings = $this->settings();
        $this->assertFalse(mvt_assignment_entries_are_known($settings, ['1' => 'Z']));
        $this->assertFalse(mvt_assignment_entries_are_known($settings, ['99' => 'A']));
    }

    public function testThreeByThreeDistributionCanProduceAllTwentySevenCombinations(): void
    {
        $values = [
            ['active' => true, 'value' => 'A'],
            ['active' => true, 'value' => 'B'],
            ['active' => true, 'value' => 'C'],
        ];
        $settings = MvtSettings::fromArray([
            'enabled' => true,
            'tests' => array_fill(0, 3, ['active' => true, 'values' => $values]),
        ]);
        $counts = [];
        for ($iteration = 0; $iteration < 2700; $iteration++) {
            $key = implode('', mvt_new_assignment($settings));
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        $this->assertCount(27, $counts);
        foreach ($counts as $count) {
            $this->assertGreaterThan(50, $count);
            $this->assertLessThan(150, $count);
        }
    }

    public function testMissingPlaceholderLeavesHtmlUnchanged(): void
    {
        $this->assertSame(
            '<main>No test marker</main>',
            apply_mvt_assignment(
                '<main>No test marker</main>',
                $this->settings(),
                ['1' => 'A']
            )
        );
    }

    public function testClickStepMvtRoundTripUsesJsonObject(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'mvt-db-');
        $this->assertNotFalse($path);
        unlink($path);
        $db = new TestDb($path);
        $db->initSchema();
        $db->seedClicks([[
            'campaign_id' => 1,
            'clickid' => 'mvt-click',
            'path' => ['landing'],
            'step' => 0,
        ]]);

        try {
            $this->assertSame([], $db->get_click_step_mvt('mvt-click', 0));
            $this->assertTrue(
                $db->update_click_step_mvt(
                    'mvt-click',
                    0,
                    ['1' => 'A', '2' => 'C']
                )
            );
            $this->assertSame(
                ['1' => 'A', '2' => 'C'],
                $db->get_click_step_mvt('mvt-click', 0)
            );
        } finally {
            unset($db);
            @unlink($path);
            @unlink($path . '-wal');
            @unlink($path . '-shm');
        }
    }

    public function testSessionAssignmentPrecedesStickyAssignment(): void
    {
        $settings = json_decode(
            (string)file_get_contents(__DIR__ . '/../../code/db/default.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $settings['saveuserflow'] = true;
        $settings['black']['flows'] = [[
            'name' => 'MVT Flow',
            'filters' => [],
            'distribution' => 'equal',
            'optimize_for' => 'Lead',
            'optimize_mode' => 'funnels',
            'steps' => [[
                'action' => 'folder',
                'folders' => [[
                    'name' => 'landing',
                    'loadtype' => 'base',
                    'weight' => 100,
                    'mvt' => [
                        'enabled' => true,
                        'tests' => [[
                            'active' => true,
                            'values' => [
                                ['active' => true, 'value' => 'A'],
                                ['active' => true, 'value' => 'B'],
                            ],
                        ]],
                    ],
                ]],
                'redirect' => ['urls' => [], 'type' => 302],
            ]],
        ]];
        $campaign = new Campaign(77, $settings);
        $flow = $campaign->black->flows[0];

        experiment_write_session_state([
            '77' => ['MVT Flow' => ['mvt' => ['0' => ['landing' => ['1' => 'B']]]]],
        ]);
        $GLOBALS['ytds_saved_paths_state'] = [
            '77' => ['MVT Flow' => ['mvt' => ['0' => ['landing' => ['1' => 'A']]]]],
        ];

        $this->assertSame(
            ['1' => 'B'],
            select_mvt_assignment($campaign, $flow, 0, 'landing')
        );

        experiment_write_session_state([]);
        $GLOBALS['ytds_saved_paths_state'] = [
            '77' => ['MVT Flow' => ['mvt' => ['0' => ['landing' => ['1' => 'A']]]]],
        ];
        $this->assertSame(
            ['1' => 'A'],
            select_mvt_assignment($campaign, $flow, 0, 'landing')
        );
    }
}
