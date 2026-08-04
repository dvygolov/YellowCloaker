<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/campaign.php';

final class StatisticsMvtEditorTest extends TestCase
{
    public function testMvtGroupingLivesInsideTableEditor(): void
    {
        $editor = (string)file_get_contents(__DIR__ . '/../../code/admin/statstableeditor.html');
        $statistics = (string)file_get_contents(__DIR__ . '/../../code/admin/statistics.php');

        self::assertStringContainsString('id="mvtPlacement"', $editor);
        self::assertStringContainsString('id="mvtAllCombinations"', $editor);
        self::assertStringContainsString('id="mvtTestsList"', $editor);
        self::assertStringContainsString('id="openMvtGrouping"', $editor);
        self::assertStringContainsString('MVT grouping', $editor);
        self::assertStringNotContainsString('stats-mvt-menu', $statistics);
        self::assertStringNotContainsString('stats-mvt-editor', $editor);
    }

    public function testEditorPersistsMvtAlongsideTableColumns(): void
    {
        $script = (string)file_get_contents(__DIR__ . '/../../code/admin/js/statstableeditor.js');
        $endpoint = (string)file_get_contents(__DIR__ . '/../../code/admin/clmnseditor.php');

        self::assertStringContainsString('const mvt = collectMvtGrouping();', $script);
        self::assertStringContainsString('JSON.stringify({ name, columns, groupby, filters, orderby, mvt })', $script);
        self::assertStringContainsString("'mvt' => \$tableConfig['mvt'] ?? []", $endpoint);
        self::assertStringContainsString('normalize_stats_mvt_config', $endpoint);
    }

    public function testStatisticsTableMvtRoundTripsAndDefaultsToEmpty(): void
    {
        $configured = StatisticsTable::fromArray([
            'name' => 'Landing MVT',
            'columns' => [['field' => 'clicks']],
            'groupby' => ['mvt'],
            'mvt' => ['flow' => 'Main', 'step' => 1, 'landing' => 'landing-a', 'tests' => [2, 1]],
        ]);
        $plain = StatisticsTable::fromArray([
            'name' => 'Date',
            'columns' => [['field' => 'clicks']],
            'groupby' => ['date'],
        ]);

        self::assertSame(
            ['flow' => 'Main', 'step' => 1, 'landing' => 'landing-a', 'tests' => [2, 1]],
            $configured->mvt
        );
        self::assertSame([], $plain->mvt);
        self::assertSame($configured->mvt, $configured->jsonSerialize()['mvt']);
    }
}
