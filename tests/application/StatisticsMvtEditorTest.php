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
        self::assertStringContainsString('id="mvtMode"', $editor);
        self::assertStringContainsString('MVT grouping', $editor);
        self::assertStringNotContainsString('stats-mvt-menu', $statistics);
        self::assertStringNotContainsString('>+MVT<', $statistics);
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
            'groupby' => ['flow'],
            'mvt' => ['flow' => 'Main', 'step' => 1, 'landing' => 'landing-a', 'test' => 2],
        ]);
        $plain = StatisticsTable::fromArray([
            'name' => 'Date',
            'columns' => [['field' => 'clicks']],
            'groupby' => ['date'],
        ]);

        self::assertSame(
            ['flow' => 'Main', 'step' => 1, 'landing' => 'landing-a', 'test' => 2],
            $configured->mvt
        );
        self::assertSame([], $plain->mvt);
        self::assertSame($configured->mvt, $configured->jsonSerialize()['mvt']);
    }
}
