<?php
class AvailableColumns
{
    static $blockedColumns = [
        "time",
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params",
        "reason"
    ];

    static $allowedColumns = [
        "userid",
        "clickid",
        "time",
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params",
        "flow",
        "path",
        "step",
        "status",
        "cost",
        "payout"
    ];

    static $leadsColumns = [
        "userid",
        "clickid",
        "time",
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params",
        "flow",
        "path",
        "step",
        "status",
        "payout",
        "name",
        "phone"
    ];
    static $trafficbackColumns = [
        "ip",
        "country",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "isp",
        "client",
        "clientver",
        "ua",
        "params"
    ];

    static $groupbyColumns = [
        "date",
        "country",
        "isp",
        "lang",
        "os",
        "osver",
        "device",
        "brand",
        "model",
        "client",
        "clientver",
        "flow",
        "step",
        "landing"
    ];

    static $statsColumns = [
        "clicks",
        "uniques",
        "flow_uniques",
        "uniques_ratio",
        "cra",
        "epc",
        "uepc",
        "cpc",
        "ucpc",
        "conversion",
        "purchase",
        "hold",
        "reject",
        "trash",
        "cpa",
        "ec",
        "revenue",
        "costs",
        "profit",
        "roi"
    ];

    public static function get_columns_for_type($type)
    {
        $clmnsName = $type . 'Columns';
        return self::$$clmnsName;
    }

    public static function get_stats_columns_for_campaign(Campaign $campaign): array
    {
        $columns = self::$statsColumns;

        $ordinaryEvents = [];
        if ($campaign->events->scrollTrackingUse) {
            foreach ($campaign->events->scrollTrackingThresholds as $threshold) {
                $ordinaryEvents['scroll_' . $threshold] = "Scroll {$threshold}%";
            }
        }
        if ($campaign->events->timeTrackingUse) {
            foreach ($campaign->events->timeTrackingThresholds as $threshold) {
                $ordinaryEvents['stay_' . $threshold . 's'] = "Visible {$threshold}s";
            }
        }
        foreach ($campaign->events->customEventNames as $eventName) {
            $ordinaryEvents[$eventName] = ucwords(str_replace('_', ' ', $eventName));
        }

        foreach ($ordinaryEvents as $eventName => $title) {
            if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $eventName) !== 1) {
                continue;
            }
            foreach (['count', 'avg', 'p75', 'min', 'max'] as $aggregation) {
                $columns[] = self::event_metric_column(
                    "event.{$eventName}.{$aggregation}",
                    $title,
                    $aggregation,
                    'Elapsed milliseconds from tracker start; Count is the number of reached steps where the event occurred.'
                );
            }
        }

        if ($campaign->events->performanceTrackingUse) {
            $performanceMetrics = [
                'ttfb' => ['TTFB', 'Time to First Byte in milliseconds.'],
                'fcp' => ['FCP', 'First Contentful Paint in milliseconds.'],
                'lcp' => ['LCP', 'Largest Contentful Paint in milliseconds.'],
                'inp' => ['INP', 'Interaction to Next Paint in milliseconds; absent when no measurable interaction occurred.'],
                'cls' => ['CLS', 'Cumulative Layout Shift score; zero is a valid value.'],
            ];
            foreach ($performanceMetrics as $metric => [$title, $description]) {
                foreach (['p75', 'avg', 'min', 'max', 'count'] as $aggregation) {
                    $columns[] = self::event_metric_column(
                        "performance.{$metric}.{$aggregation}",
                        $title,
                        $aggregation,
                        $description
                    );
                }
            }
        }

        return $columns;
    }

    private static function event_metric_column(
        string $field,
        string $metricTitle,
        string $aggregation,
        string $description
    ): array
    {
        $aggregationTitles = [
            'count' => 'Count',
            'avg' => 'Average',
            'p75' => 'P75',
            'min' => 'Minimum',
            'max' => 'Maximum',
        ];
        $aggregationTitle = $aggregationTitles[$aggregation] ?? strtoupper($aggregation);
        return [
            'field' => $field,
            'title' => "{$metricTitle} — {$aggregationTitle}",
            'description' => "{$description} Aggregation: {$aggregationTitle}.",
            'event_metric' => true,
        ];
    }
}

class TableColumns
{
    public static array $clickClmns =
        [
            "ip" => [
                "title" => "IP",
                "field" => "ip",
                "editor" => false,
                "headerFilter" => false,
            ],
            "country" => [
                "title" => "Country",
                "field" => "country",
                "editor" => false,
                "headerFilter" => false,
            ],
            "lang" => [
                "title" => "Lang",
                "field" => "lang",
                "headerTooltip" => "Language",
                "editor" => false,
                "headerFilter" => false,
            ],
            "isp" => [
                "title" => "ISP",
                "field" => "isp",
                "editor" => false,
                "headerFilter" => false,
                "headerTooltip" => "Internet Service Provider"
            ],
            "os" => [
                "title" => "OS",
                "field" => "os",
                "editor" => false,
                "headerFilter" => false,
                "headerTooltip" => "Operating System",
            ],
            "osver" => [
                "title" => "OSVer",
                "field" => "osver",
                "headerTooltip" => "OS version",
                "editor" => false,
                "headerFilter" => false,
            ],
            "client" => [
                "title" => "Client",
                "field" => "client",
                "headerTooltip" => "Client (browser)",
                "editor" => false,
                "headerFilter" => false,
            ],
            "clientver" => [
                "title" => "ClientVer",
                "field" => "clientver",
                "headerTooltip" => "Client (browser) version",
                "editor" => false,
                "headerFilter" => false,
            ],
            "device" => [
                "title" => "Device",
                "field" => "device",
                "headerTooltip" => "Device",
                "editor" => false,
                "headerFilter" => false,
            ],
            "brand" => [
                "title" => "Brand",
                "field" => "brand",
                "headerTooltip" => "Brand",
                "editor" => false,
                "headerFilter" => false,
            ],
            "model" => [
                "title" => "Model",
                "field" => "model",
                "headerTooltip" => "Model",
                "editor" => false,
                "headerFilter" => false,
            ],
            "ua" => [
                "title" => "UA",
                "field" => "ua",
                "headerTooltip" => "User agent",
                "editor" => false,
                "headerFilter" => false,
                "formatter" => "textarea",
                "width" => "200"
            ],
            "params" => [
                "title" => "Subs",
                "field" => "params",
                "headerTooltip" => "All url parameters",
                "editor" => false,
                "headerFilter" => false,
                "headerFilterFunc" => "FSTARTfunction(headerValue, rowValue, rowData, filterParams){ if (rowValue.length===0) return false; return JSON.stringify(rowValue).includes(headerValue);}FEND", 
                "headerSort" => false,
                "tooltip" => "FSTARTfunction(e, cell, onRendered){ var data = cell.getValue(); var keys = Object.keys(data).sort(); var formattedData = ''; keys.forEach(function(key) { if (data.hasOwnProperty(key)) { formattedData += key + '=' + data[key] + '<br>'; } }); return formattedData;}FEND",
                "formatter" => "FSTARTfunction(cell, formatterParams, onRendered){var data = cell.getValue();var keys = Object.keys(data).sort();var formattedData = ''; keys.forEach(function(key) { if (data.hasOwnProperty(key)) { formattedData += key + '=' + data[key] + '<br>';}}); return formattedData;}FEND"
            ],
            "userid"=>[
                "title" => "UserID",
                "field" => "userid",
                "headerTooltip" => "Persistent user identifier",
                "editor" => false,
                "headerFilter" => false,
            ],
            "clickid"=>[
                "title" => "ClickID",
                "field" => "clickid",
                "headerTooltip" => "Click identifier for full funnel pass",
                "editor" => false,
                "headerFilter" => false,
            ],
            "path"=>[
                "title" => "Path",
                "field" => "path",
                "headerTooltip" => "Funnel path (selected variants)",
                "editor" => false,
                "headerFilter" => false,
                "formatter" => "FSTARTfunction(cell){var v=cell.getValue();if(!v||!Array.isArray(v))return '';return v.join(' → ');}FEND",
            ],
            "step"=>[
                "title" => "Step",
                "field" => "step",
                "headerTooltip" => "Current funnel step index",
                "editor" => false,
                "headerFilter" => false,
                "sorter" => "number",
                "hozAlign" => "center",
            ],
            "flow"=>[
                "title" => "Flow",
                "field" => "flow",
                "headerTooltip" => "Traffic flow",
                "editor" => false,
                "headerFilter" => false,
            ],
            "reason"=>[
                "title" => "Reason",
                "headerTooltip" => "Why click was blocked",
                "field" => "reason",
                "formatter" => "plaintext",
                "sorter" => "string",
                "editor" => false,
                "headerFilter" => false,
            ],
            "status"=>[
                "title" => "Status",
                "field" => "status",
                "headerFilter" => false,
                "editor" => false,
            ],
            "cost"=>[
                "title" => "Cost",
                "field" => "cost",
                "headerFilter" => false,
                "editor" => false,
            ],
            "payout"=>[
                "title" => "Payout",
                "field" => "payout",
                "headerFilter" => false,
                "editor" => false,
            ],
        ];


    public static array $statsClmns = [
        'group' => [
            "field" => "group",
            "headerTooltip" => "Group By",
            "headerFilter" => false,
            "editor" => false,
            "minWidth" => 120,
            "cellClick"=>"FSTARTfunction(e,cell){var row=cell.getRow();if(row.getTreeChildren().length){row.treeToggle();}}FEND",
            "bottomCalc"=>"FSTARTfunction(values, data, calcParams){return 'TOTAL';}FEND",
        ],
        'step' => [
            "title" => "Step",
            "headerTooltip" => "Funnel step index",
            "field" => "step",
            "headerFilter" => "input",
            "sorter" => "number",
        ],
        'flow' => [
            "title" => "Flow",
            "headerTooltip" => "Traffic flow",
            "field" => "flow",
            "headerFilter" => "input",
        ],
        'country' => [
            "title" => "Country",
            "field" => "country",
            "headerFilter" => "input",
        ],
        'lang' => [
            "title" => "Lang",
            "headerTooltip" => "Browser language",
            "field" => "lang",
            "headerFilter" => "input",
        ],
        'isp' => [
            "title" => "ISP",
            "headerTooltip" => "Internet Service Provider",
            "field" => "isp",
            "headerFilter" => "input",
        ],
        'date' => [
            "title" => "Date",
            "field" => "date",
            "sorter" => "date",
            "sorterParams" => [
                "format" => "yyyy-MM-dd",
                "alignEmptyValues" => "top",
            ]
        ],
        'os' => [
            "title" => "OS",
            "headerTooltip" => "Operating System",
            "field" => "os",
            "headerFilter" => "input",
        ],
        'clicks' => [
            "title" => "Clicks",
            "headerTooltip" => "Number of visitors",
            "field" => "clicks",
            "sorter" => "number",
            "hozAlign" => "right",
            "bottomCalc" => "sum"
        ],
        'uniques' => [
            "title" => "Uniques",
            "headerTooltip" => "Number of unique visitors",
            "field" => "uniques",
            "sorter" => "number",
            "hozAlign" => "right",
            "formatter" => "FSTARTfunction(cell){var v=cell.getValue();return v===null||v===undefined?'—':v;}FEND",
            "bottomCalc" => "FSTARTfunction(v,d){if(d.some(function(r){return r.uniques===null||r.uniques===undefined;}))return '—';return v.reduce(function(a,b){return a+(Number(b)||0);},0);}FEND"
        ],
        'flow_uniques' => [
            "title" => "Flow uniques",
            "headerTooltip" => "Sum of unique entries into flows",
            "field" => "flow_uniques",
            "sorter" => "number",
            "hozAlign" => "right",
            "formatter" => "FSTARTfunction(cell){var v=cell.getValue();return v===null||v===undefined?'—':v;}FEND",
            "bottomCalc" => "FSTARTfunction(v,d){if(d.some(function(r){return r.flow_uniques===null||r.flow_uniques===undefined;}))return '—';return v.reduce(function(a,b){return a+(Number(b)||0);},0);}FEND"
        ],
        'uniques_ratio' => [
            "title" => "U/C",
            "headerTooltip" => "Unique visitors / visitors",
            "field" => "uniques_ratio",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 76,
            "formatter" => "FSTARTfunction(cell){var v=cell.getValue();return v===null||v===undefined?'—':Number(v).toFixed(2)+'%';}FEND",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){if(d.some(function(r){return r.uniques===null||r.uniques===undefined;}))return '—';var u=0,c=0;d.forEach(function(r){u+=r.uniques||0;c+=r.clicks||0;});return c===0?0:Math.round(u/c*10000)/100;}FEND",
            "bottomCalcFormatter" => "FSTARTfunction(cell){var v=cell.getValue();return v==='—'?'—':Number(v).toFixed(2)+'%';}FEND",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'conversion' => [
            "title" => "Conversions",
            "headerTooltip" => "Conversions",
            "field" => "conversion",
            "sorter" => "number",
            "hozAlign" => "right",
            "bottomCalc" => "sum"
        ],
        'purchase' => [
            "title" => "Purchase",
            "headerTooltip" => "Purchases",
            "field" => "purchase",
            "sorter" => "number",
            "hozAlign" => "right",
            "bottomCalc" => "sum"
        ],
        'hold' => [
            "title" => "Lead",
            "headerTooltip" => "Leads",
            "field" => "hold",
            "sorter" => "number",
            "hozAlign" => "right",
            "bottomCalc" => "sum"
        ],
        'reject' => [
            "title" => "Reject",
            "headerTooltip" => "Rejects",
            "field" => "reject",
            "sorter" => "number",
            "hozAlign" => "right",
            "bottomCalc" => "sum"
        ],
        'trash' => [
            "title" => "Trash",
            "headerTooltip" => "Trashes",
            "field" => "trash",
            "sorter" => "number",
            "hozAlign" => "right",
            "bottomCalc" => "sum"
        ],
        'cra' => [
            "title" => "CRa",
            "headerTooltip" => "Total conversion rate",
            "field" => "cra",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 76,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){var cv=0,c=0;d.forEach(function(r){cv+=r.conversion||0;c+=r.clicks||0;});return c===0?0:Math.round(cv/c*10000)/100;}FEND",
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "symbol" => "%",
                "symbolAfter" => true,
                "precision" => 2,
            ],
        ],
        'cpc' => [
            "title" => "CPC",
            "headerTooltip" => "Cost per click",
            "field" => "cpc",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 85,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){var co=0,c=0;d.forEach(function(r){co+=r.costs||0;c+=r.clicks||0;});return c===0?0:Math.round(co/c*100000)/100000;}FEND",
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'ucpc' => [
            "title" => "CPuC",
            "headerTooltip" => "Cost per unique click",
            "field" => "ucpc",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 85,
            "formatter" => "FSTARTfunction(cell){var v=cell.getValue();return v===null||v===undefined?'—':Number(v).toFixed(5);}FEND",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){if(d.some(function(r){return r.uniques===null||r.uniques===undefined;}))return '—';var co=0,u=0;d.forEach(function(r){co+=r.costs||0;u+=r.uniques||0;});return u===0?0:Math.round(co/u*100000)/100000;}FEND",
            "bottomCalcFormatter" => "FSTARTfunction(cell){var v=cell.getValue();return v==='—'?'—':Number(v).toFixed(5);}FEND",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'cpa' => [
            "title" => "CPA",
            "headerTooltip" => "Cost per action (conversion)",
            "field" => "cpa",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 85,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){var co=0,cv=0;d.forEach(function(r){co+=r.costs||0;cv+=r.conversion||0;});return cv===0?0:Math.round(co/cv*100000)/100000;}FEND",
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'ec' => [
            "title" => "EC",
            "headerTooltip" => "Earnings per conversion",
            "field" => "ec",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 85,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){var rv=0,cv=0;d.forEach(function(r){rv+=r.revenue||0;cv+=r.conversion||0;});return cv===0?0:Math.round(rv/cv*100000)/100000;}FEND",
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'costs' => [
            "title" => "Costs",
            "headerTooltip" => "Traffic costs",
            "field" => "costs",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 90,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
            "bottomCalc" => "sum",
            "bottomCalcParams" => [
                "precision" => 2,
            ]
        ],
        'epc' => [
            "title" => "EPC",
            "headerTooltip" => "Earnings Per Click",
            "field" => "epc",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 85,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){var rv=0,c=0;d.forEach(function(r){rv+=r.revenue||0;c+=r.clicks||0;});return c===0?0:Math.round(rv/c*100000)/100000;}FEND",
            "bottomCalcFormatter" => "money",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'uepc' => [
            "title" => "EPuC",
            "headerTooltip" => "Earnings Per Unique Click",
            "field" => "uepc",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 85,
            "formatter" => "FSTARTfunction(cell){var v=cell.getValue();return v===null||v===undefined?'—':Number(v).toFixed(5);}FEND",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
            "bottomCalc" => "FSTARTfunction(v,d){if(d.some(function(r){return r.uniques===null||r.uniques===undefined;}))return '—';var rv=0,u=0;d.forEach(function(r){rv+=r.revenue||0;u+=r.uniques||0;});return u===0?0:Math.round(rv/u*100000)/100000;}FEND",
            "bottomCalcFormatter" => "FSTARTfunction(cell){var v=cell.getValue();return v==='—'?'—':Number(v).toFixed(5);}FEND",
            "bottomCalcFormatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 5,
            ],
        ],
        'revenue' => [
            "title" => "Rev.",
            "headerTooltip" => "Revenue",
            "field" => "revenue",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 90,
            "formatter" => "money",
            "formatterParams" => [
                "decimal" => ".",
                "thousand" => ",",
                "precision" => 2,
            ],
            "bottomCalc" => "sum",
            "bottomCalcParams" => [
                "precision" => 2,
            ]
        ],
        'profit' => [
            "title" => "Profit",
            "headerTooltip" => "Profit",
            "field" => "profit",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 90,
            "formatter" => "FSTARTfunction(cell){var v=parseFloat(cell.getValue())||0;var c=v>0?'#4caf50':v<0?'#f44336':'inherit';return '<span style=color:'+c+'>'+v.toFixed(2)+'</span>';}FEND",
            "bottomCalc" => "sum",
            "bottomCalcParams" => [
                "precision" => 2,
            ],
            "bottomCalcFormatter" => "FSTARTfunction(cell){var v=parseFloat(cell.getValue())||0;var c=v>0?'#4caf50':v<0?'#f44336':'inherit';return '<span style=color:'+c+'>'+v.toFixed(2)+'</span>';}FEND",
        ],
        'roi' => [
            "title" => "ROI",
            "headerTooltip" => "Return On Investment",
            "field" => "roi",
            "sorter" => "number",
            "hozAlign" => "right",
            "width" => 76,
            "formatter" => "FSTARTfunction(cell){var v=parseFloat(cell.getValue());if(isNaN(v))return '';var c=v>0?'#4caf50':v<0?'#f44336':'inherit';return '<span style=color:'+c+'>'+v.toFixed(2)+'%</span>';}FEND",
            "bottomCalc" => "FSTARTfunction(v,d){var rv=0,co=0;d.forEach(function(r){rv+=r.revenue||0;co+=r.costs||0;});return co===0?0:Math.round((rv-co)/co*10000)/100;}FEND",
            "bottomCalcFormatter" => "FSTARTfunction(cell){var v=parseFloat(cell.getValue())||0;var c=v>0?'#4caf50':v<0?'#f44336':'inherit';return '<span style=color:'+c+'>'+v.toFixed(2)+'%</span>';}FEND",
        ],
    ];
}
