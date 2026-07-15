<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/clmns.php';

class Tabulator
{
    private static function custom_metric_formatter(array $column): array
    {
        $format = $column['format'] ?? 'number';
        $decimals = max(0, (int)($column['decimals'] ?? 2));

        if ($format === 'percent') {
            return [
                'formatter' => 'money',
                'formatterParams' => [
                    'decimal' => '.',
                    'thousand' => ',',
                    'symbol' => '%',
                    'symbolAfter' => true,
                    'precision' => $decimals,
                ],
                'bottomCalcFormatter' => 'money',
                'bottomCalcFormatterParams' => [
                    'decimal' => '.',
                    'thousand' => ',',
                    'symbol' => '%',
                    'symbolAfter' => true,
                    'precision' => $decimals,
                ],
            ];
        }

        if ($format === 'currency') {
            return [
                'formatter' => 'money',
                'formatterParams' => [
                    'decimal' => '.',
                    'thousand' => ',',
                    'precision' => $decimals,
                ],
                'bottomCalcFormatter' => 'money',
                'bottomCalcFormatterParams' => [
                    'decimal' => '.',
                    'thousand' => ',',
                    'precision' => $decimals,
                ],
            ];
        }

        return [
            'formatter' => 'money',
            'formatterParams' => [
                'decimal' => '.',
                'thousand' => ',',
                'precision' => $decimals,
            ],
            'bottomCalcFormatter' => 'money',
            'bottomCalcFormatterParams' => [
                'decimal' => '.',
                'thousand' => ',',
                'precision' => $decimals,
            ],
        ];
    }

    private static function build_custom_metric_column(array $column): array
    {
        $field = $column['field'];
        $title = $column['title'] ?? $field;
        $escapedField = addcslashes($field, "'\\");
        $tabulatorColumn = [
            'title' => $title,
            'field' => $field,
            'sorter' => 'number',
            'hozAlign' => 'right',
            'bottomCalc' => 'FSTARTfunction(values,data){var totals=(data&&data[0]&&data[0]._stats_totals)||{};var result=((totals[\'' . $escapedField . '\'] ?? 0) * 1);return isFinite(result)?result:0;}FEND',
        ];

        return array_merge($tabulatorColumn, self::custom_metric_formatter($column));
    }

    public static function get_stats_columns(array $columns, ?string $groupByClmnTitle = null, array $groupByFields = []): string
    {
        $columns = Db::normalize_stats_columns_config($columns);
        $columnSettings = TableColumns::$statsClmns;
        $tabulatorColumns = [];

        // Prepend the group column titled "Groups" — it holds the tree hierarchy
        if (!empty($groupByFields)) {
            $groupCol = $columnSettings['group'];
            $groupCol['title'] = 'Groups';
            $tabulatorColumns[] = $groupCol;
        }

        for ($i = 0; $i < count($columns); $i++) {
            $field = $columns[$i]['field'];
            // Skip the group column (already prepended) and groupby dimension columns
            if ($field === 'group' || in_array($field, $groupByFields))
                continue;
            $width = $columns[$i]['width'] ?? -1;
            if (array_key_exists($field, $columnSettings)) {
                $tabulatorColumns[] = $columnSettings[$field];
            } elseif (!empty($columns[$i]['custom'])) {
                $tabulatorColumns[] = self::build_custom_metric_column($columns[$i]);
            } elseif (str_starts_with($field, 'event.')) {
                $title = $columns[$i]['title'] ?? ucwords(str_replace('_', ' ', substr($field, 6)));
                $tabulatorColumns[] = [
                    'title' => $title,
                    'field' => $field,
                    'sorter' => 'number',
                    'hozAlign' => 'right',
                    'bottomCalc' => 'sum',
                ];
            } else {
                $tabulatorColumns[] = ["title" => $field, "field" => $field];
            }
            if ($width === -1)
                continue;
            $tabulatorColumns[count($tabulatorColumns) - 1]["width"] = $width;
        }

        $clmnsJson = json_encode($tabulatorColumns, JSON_UNESCAPED_SLASHES);
        $clmnsJson = str_replace('"FSTART', '', $clmnsJson);
        $clmnsJson = str_replace('FEND"', '', $clmnsJson);
        return $clmnsJson;
    }


    public static function get_clicks_columns(?int $campId, string $timezone, array $columns): string
    {
        $columnSettings = TableColumns::$clickClmns;

        $defaultColumns =
        [
            "userid" => [
                "title" => "UserID",
                "field" => "userid",
                "headerTooltip" => "Persistent user identifier",
                "headerSort" => false,
                "editor" => false,
            ],
            "clickid" => [
                "title" => "ClickID",
                "field" => "clickid",
                "headerTooltip" => "Click identifier for full funnel pass",
                "headerSort" => false,
                "editor" => false,
            ],
            "time" => [
                "title" => "Time",
                "field" => "time",
                "formatter" => "datetime",
                "formatterParams" => [
                        "inputFormat" => "unix",
                        "outputFormat" => "yyyy-MM-dd HH:mm:ss",
                        "timezone" => "$timezone"
                    ],
                "headerTooltip" => "Date and time according to selected timezone",
                "sorter" => "datetime",
                "sorterParams" => [
                        "format" => "unix"
                ],
                "editor" => false
            ]
        ];

        $tabulatorColumns = [];
        for ($i = 0; $i < count($columns); $i++) {
            $clmn = $columns[$i]['field'];
            $width = $columns[$i]['width'] ?? -1;
            if (array_key_exists($clmn, $columnSettings)) {
                $tabulatorColumns[] = $columnSettings[$clmn];
            } else if (array_key_exists($clmn, $defaultColumns)) {
                $tabulatorColumns[] = $defaultColumns[$clmn];
            } elseif (str_starts_with($clmn, 'param.')) {
                $paramKey = substr($clmn, 6);
                $tabulatorColumns[] = [
                    "title" => "$paramKey\u{1F310}",
                    "field" => "param.$paramKey",
                    "editor" => false,
                    "headerFilter" => false,
                    "headerTooltip" => "URL parameter: $paramKey",
                ];
            } else {
                $tabulatorColumns[] = ["title" => $clmn, "field" => $clmn];
            }
            if ($width === -1)
                continue;
            $tabulatorColumns[count($tabulatorColumns) - 1]["width"] = $width;
        }
        $clmnsJson = json_encode($tabulatorColumns, JSON_UNESCAPED_SLASHES);
        $clmnsJson = str_replace('"FSTART', '', $clmnsJson);
        $clmnsJson = str_replace('FEND"', '', $clmnsJson);
        return $clmnsJson;
    }

    public static function get_campaigns_columns(array $columns): string
    {
        $nameWidth = 90;
        foreach ($columns as $col) {
            if (($col['field'] ?? '') === 'name' && isset($col['width']) && $col['width'] > 0)
                $nameWidth = $col['width'];
        }

        $defaultClmns = <<<JSON
    [
        {
            "title": "ID",
            "field": "id",
            "visible": false,
        },
        {
            "title": "Name",
            "formatter": function(cell) {
                const data = cell.getRow().getData();
                const id = data.id;
                const name = data.name;
                if (!id) return name || '';
                return `<div class="camp-name-cell">
                    <a href="statistics.php?campId=\${id}\${getStartDateEndDateParams()}" class="camp-name-link" title="Open statistics">\${name}</a>
                    <button class="camp-menu-btn" title="Actions"><i class="bi bi-three-dots-vertical"></i></button>
                </div>`;
            },
            "field": "name",
            "headerFilter": false,
            "width": $nameWidth,
            "editor":false,
            "cellClick": campNameCellClick,
            "bottomCalc":() => "TOTAL"
        },
JSON;

        $filteredColumns = array_values(array_filter($columns, fn($c) => !in_array($c['field'] ?? '', ['name', 'actions'])));
        $statColumns = Tabulator::get_stats_columns($filteredColumns);
        $defaultClmns .= substr($statColumns, 1);
        return $defaultClmns;
    }
}
