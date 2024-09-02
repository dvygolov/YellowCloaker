<?php
require_once __DIR__ . '/../db.php';
function show_stats($startDate, $endDate, $dtz, $config):string    
{
    $tableData ='';
    $db = new Db();
    $sTables = get_table_settings();
    foreach ($sTables['tables'] as $tSettings) {
        $dataset = $db->get_statistics(
            $tSettings['columns'], $tSettings['groupby'], $config, 
            $startDate->getTimestamp(),$endDate->getTimestamp(), $dtz);
        $dJson = json_encode($dataset);
        $tName = $tSettings['name'];
        $tColumns = get_stats_columns($tName, $tSettings['columns'], $tSettings['groupby'], $dtz);
        $tableData.= <<<EOF
            <div id="t$tName"></div>
            <script>
                let t{$tName}Data = $dJson;
                let t{$tName}Columns = $tColumns;
                let t{$tName}Table = new Tabulator('#t{$tSettings["name"]}', {
                    layout: "fitColumns",
                    columns: t{$tName}Columns,
                    columnCalcs: "both",
                    pagination: "local",
                    paginationSize: 500,
                    paginationSizeSelector: [25, 50, 100, 200, 500, 1000, 2000, 5000],
                    paginationCounter: "rows",
                    dataTree: true,
                    dataTreeBranchElement:false,
                    dataTreeStartExpanded:false,
                    dataTreeChildIndent: 35,
                    height: "100%",
                    data: t{$tName}Data,
                    tooltips: true
                });
            </script>
            <br/>
            <br/>
EOF;
   }
    return $tableData;
}

function get_table_settings(): array
{
    try {
        $tables = json_decode(file_get_contents(__DIR__ . '/settings.json'), true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        die($e->getMessage());
    }
    return $tables;
}
function get_stats_columns(string $tName, array $columns, array $groupby, $timezone): string
{
    $columnSettings = [
        'preland' => [
            "title" => "Preland",
            "field" => "preland",
            "headerFilter" => "input",
        ],
        'land' => [
            "title" => "Land",
            "field" => "land",
            "headerFilter" => "input",
        ],
        'country' => [
            "title" => "Country",
            "field" => "country",
            "headerFilter" => "input",
            "width" => "50",
        ],
        'lang' => [
            "title" => "Lang",
            "field" => "lang",
            "headerFilter" => "input",
            "width" => "50",
        ],
        'isp' => [
            "title" => "ISP",
            "field" => "isp",
            "headerFilter" => "input",
        ],
        'date' => [
            "title" => "Date",
            "field" => "date",
            "sorter" => "date",
            "sorterParams"=>[
                "format"=>"yyyy-MM-dd",
                "alignEmptyValues"=>"top",
            ]
        ],
        'os' => [
            "title" => "OS",
            "field" => "os",
            "headerFilter" => "input",
            "width" => "100",
        ],
        'clicks' => [
            "title" => "Clicks",
            "field" => "clicks",
            "width"=>"90",
            "bottomCalc"=>"sum"
        ],
        'uniques' => [
            "title" => "Uniques",
            "field" => "uniques",
            "width"=>"90",
            "bottomCalc"=>"sum"
        ],
        'uniques_ratio' => [
            "title" => "U/C",
            "field" => "uniques_ratio",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "symbol"=> "%",
                "symbolAfter"=> true,
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
        'conversion' => [
            "title" => "CV",
            "field" => "conversion",
            "width" => "60",
            "bottomCalc"=>"sum"
        ],
        'purchase' => [
            "title" => "P",
            "field" => "purchase",
            "width" => "50",
            "bottomCalc"=>"sum"
        ],
        'hold' => [
            "title" => "H",
            "field" => "hold",
            "width" => "50",
            "bottomCalc"=>"sum"
        ],
        'reject' => [
            "title" => "R",
            "field" => "reject",
            "width" => "50",
            "bottomCalc"=>"sum"
        ],
        'trash' => [
            "title" => "T",
            "field" => "trash",
            "width" => "50",
            "bottomCalc"=>"sum"
        ],
        'lpclicks' => [
            "title" => "LPClicks",
            "field" => "lpclicks",
            "width" => "70",
            "bottomCalc"=>"sum"
        ],
        'lpctr' => [
            "title" => "LPCTR",
            "field" => "lpctr",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "symbol"=> "%",
                "symbolAfter"=> true,
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
        'cra' => [
            "title" => "CRa",
            "field" => "cra",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "symbol"=> "%",
                "symbolAfter"=> true,
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
        'crs' => [
            "title" => "CRs",
            "field" => "crs",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "symbol"=> "%",
                "symbolAfter"=> true,
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
        'appt' => [
            "title" => "App(t)",
            "field" => "appt",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "symbol"=> "%",
                "symbolAfter"=> true,
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
        'app' => [
            "title" => "App",
            "field" => "app",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "symbol"=> "%",
                "symbolAfter"=> true,
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
        'cpc' => [
            "title" => "CPC",
            "field" => "cpc",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 5,
            ],
            "bottomCalc"=>"avg"
        ],
        'costs' => [
            "title" => "Costs",
            "field" => "costs",
            "width" => "100",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 2,
            ],
            "bottomCalc"=>"sum",
            "bottomCalcParams"=>[
                "precision" => 2,
            ]
        ],
        'epc' => [
            "title" => "EPC",
            "field" => "epc",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 5,
            ],
            "bottomCalc"=>"avg"
        ],
        'epuc' => [
            "title" => "EPuC",
            "field" => "epuc",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 5,
            ],
            "bottomCalc"=>"avg"
        ],
        'revenue' => [
            "title" => "Rev.",
            "field" => "revenue",
            "width" => "100",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 2,
            ],
            "bottomCalc"=>"sum",
            "bottomCalcParams"=>[
                "precision" => 2,
            ]
        ],
        'profit' => [
            "title" => "Profit",
            "field" => "profit",
            "width" => "100",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 2,
            ],
            "bottomCalc"=>"sum",
            "bottomCalcParams"=>[
                "precision" => 2,
            ]
        ],
        'roi' => [
            "title" => "ROI",
            "field" => "roi",
            "width"=>"90",
            "formatter"=> "money",
            "formatterParams"=>[
                "decimal"=> ".",
                "thousand"=> ",",
                "precision"=> 2,
            ],
            "bottomCalc"=>"avg"
        ],
    ];

    $tabulatorColumns = [];
    if (count($groupby) > 0)
        $tabulatorColumns[] = ["title" => $tName, "field" => "group"];

    foreach ($columns as $field) {
        if (array_key_exists($field, $columnSettings)) {
            $tabulatorColumns[] = $columnSettings[$field];
        }
        else{
            $tabulatorColumns[] = ["title"=>$field,"field"=>$field];
        }
    }
    return json_encode($tabulatorColumns);
}

function get_clicks_columns(string $filter, $timezone): string
{
    $columns = [];
    switch ($filter) {
        case 'blocked':
            $columns = <<<JSON
            [
                {
                    "title": "IP",
                    "field": "ip",
                    "width": "150",
                    "headerFilter": "input"
                },
                {
                    "title": "Country",
                    "field": "country",
                    "headerFilter": "input",
                    "width": "50"
                },
                {
                    "title": "ISP",
                    "field": "isp",
                    "headerFilter": "input"
                },
                {
                    "title": "Time",
                    "field": "time",
                    "formatter": "datetime",
                    "formatterParams": {
                        "inputFormat": "unix",
                        "outputFormat": "yyyy-MM-dd HH:mm:ss",
                        "timezone": "$timezone"
                    },
                    "sorter": "datetime",
                    "sorterParams": {
                        "format": "unix"
                    }
                },
                {
                    "title": "Reason",
                    "field": "reason",
                    "formatter": "plaintext",
                    "sorter": "string",
                    "width": "80",
                    "headerFilter": "input"
                },
                {
                    "title": "OS",
                    "field": "os",
                    "headerFilter": "input",
                    "width": "100"
                },
                {
                    "title": "UA",
                    "field": "ua",
                    "formatter": "textarea",
                    "headerFilter": "input"
                },
                {
                    "title": "Subs",
                    "field": "params",
                    "headerFilter": "input",
                    "headerFilterFunc": function(headerValue, rowValue, rowData, filterParams){
                        if (rowValue.length===0) return false;
                        return JSON.stringify(rowValue).includes(headerValue); 
                    },
                    "headerSort":false,
                    "tooltip": function(e, cell, onRendered){
                        var data = cell.getValue();

                        var keys = Object.keys(data).sort();
                        var formattedData = "";

                        keys.forEach(function(key) {
                            if (data.hasOwnProperty(key)) {
                                formattedData += key + "=" + data[key] + "<br>";
                            }
                        });
                        return formattedData;
                    },
                    "formatter": function(cell, formatterParams, onRendered) {
                        var data = cell.getValue();

                        var keys = Object.keys(data).sort();
                        var formattedData = "";

                        keys.forEach(function(key) {
                            if (data.hasOwnProperty(key)) {
                                formattedData += key + "=" + data[key] + "<br>";
                            }
                        });
                        return formattedData;
                    }
                },
            ]
JSON;
            break;
        case 'single':
            $columns = <<<JSON
            [
                {
                    "title": "Subid",
                    "field": "subid"
                },
                {
                    "title": "IP",
                    "field": "ip"
                },
                {
                    "title": "Country",
                    "field": "country"
                },
                {
                    "title": "Lang",
                    "field": "lang"
                },
                {
                    "title": "ISP",
                    "field": "isp"
                },
                {
                    "title": "Time",
                    "field": "time",
                    "formatter": "datetime",
                    "formatterParams": {
                        "inputFormat": "unix",
                        "outputFormat": "yyyy-MM-dd HH:mm:ss",
                        "timezone": "$timezone"
                    },
                    "sorter": "datetime",
                    "sorterParams": {
                        "format": "unix"
                    }
                },
                {
                    "title": "OS",
                    "field": "os",
                },
                {
                    "title": "UA",
                    "field": "ua",
                },
                {
                    "title": "Subs",
                    "field": "params",
                    "headerFilter": "input",
                    "headerFilterFunc": function(headerValue, rowValue, rowData, filterParams){
                        if (rowValue.length===0) return false;
                        return JSON.stringify(rowValue).includes(headerValue); 
                    },
                    "headerSort":false,
                    "tooltip": function(e, cell, onRendered){
                        var data = cell.getValue();

                        var keys = Object.keys(data).sort();
                        var formattedData = "";

                        keys.forEach(function(key) {
                            if (data.hasOwnProperty(key)) {
                                formattedData += key + "=" + data[key] + "<br>";
                            }
                        });
                        return formattedData;
                    },
                    "formatter": function(cell, formatterParams, onRendered) {
                        var data = cell.getValue();

                        var keys = Object.keys(data).sort();
                        var formattedData = "";

                        keys.forEach(function(key) {
                            if (data.hasOwnProperty(key)) {
                                formattedData += key + "=" + data[key] + "<br>";
                            }
                        });
                        return formattedData;
                    }
                },
                {
                    "title": "Preland",
                    "field": "preland"
                },
                {
                    "title": "Land",
                    "field": "land"
                }
            ]

JSON;
            break;
        case 'leads':
            $columns = <<<JSON
            [
                {
                    "title": "Subid",
                    "field": "subid",
                    "formatter": "link",
                    "formatterParams": {
                        "urlPrefix": "index.php?filter=single&subid="
                    }
                },
                {
                    "title": "Time",
                    "field": "time",
                    "formatter": "datetime",
                    "formatterParams": {
                        "inputFormat": "unix",
                        "outputFormat": "yyyy-MM-dd HH:mm:ss",
                        "timezone": "$timezone"
                    },
                    "sorter": "datetime",
                    "sorterParams": {
                        "format": "unix"
                    }
                },
                {
                    "title": "Name",
                    "field": "name"
                },
                {
                    "title": "Phone",
                    "field": "phone"
                },
                {
                    "title": "Status",
                    "field": "status"
                },
                {
                    "title": "Preland",
                    "field": "preland"
                },
                {
                    "title": "Land",
                    "field": "land"
                }
            ]
JSON;
            break;
        default:
            $columns = <<<JSON
            [
                {
                    "title": "Subid",
                    "field": "subid",
                    "formatter": "link",
                    "formatterParams": {
                        "urlPrefix": "index.php?filter=single&subid="
                    },
                    "headerSort":false,
                    "width":"100"
                },
                {
                    "title": "IP",
                    "field": "ip",
                    "headerFilter": "input",
                    "width": "120"
                },
                {
                    "title": "Country",
                    "field": "country",
                    "headerFilter": "input",
                    "width": "80"
                },
                {
                    "title": "Lang",
                    "field": "lang",
                    "headerFilter": "input",
                    "width": "50"
                },
                {
                    "title": "ISP",
                    "field": "isp",
                    "headerFilter": "input"
                },
                {
                    "title": "Time",
                    "field": "time",
                    "formatter": "datetime",
                    "formatterParams": {
                        "inputFormat": "unix",
                        "outputFormat": "yyyy-MM-dd HH:mm:ss",
                        "timezone": "$timezone"
                    },
                    "sorter": "datetime",
                    "sorterParams": {
                        "format": "unix"
                    }
                },
                {
                    "title": "OS",
                    "field": "os",
                    "headerFilter": "input",
                    "width": "100"
                },
                {
                    "title": "OSVer",
                    "field": "osver",
                    "headerFilter": "input",
                    "width": "100"
                },
                {
                    "title": "UA",
                    "field": "ua",
                    "headerFilter": "input",
                    "formatter": "textarea",
                    "width":"200"
                },
                {
                    "title": "Subs",
                    "field": "params",
                    "headerFilter": "input",
                    "headerFilterFunc": function(headerValue, rowValue, rowData, filterParams){
                        if (rowValue.length===0) return false;
                        return JSON.stringify(rowValue).includes(headerValue); 
                    },
                    "headerSort":false,
                    "tooltip": function(e, cell, onRendered){
                        var data = cell.getValue();

                        var keys = Object.keys(data).sort();
                        var formattedData = "";

                        keys.forEach(function(key) {
                            if (data.hasOwnProperty(key)) {
                                formattedData += key + "=" + data[key] + "<br>";
                            }
                        });
                        return formattedData;
                    },
                    "formatter": function(cell, formatterParams, onRendered) {
                        var data = cell.getValue();

                        var keys = Object.keys(data).sort();
                        var formattedData = "";

                        keys.forEach(function(key) {
                            if (data.hasOwnProperty(key)) {
                                formattedData += key + "=" + data[key] + "<br>";
                            }
                        });
                        return formattedData;
                    }
                },
                {
                    "title": "Preland",
                    "field": "preland",
                    "headerFilter": "input",
                    "width":"100"
                },
                {
                    "title": "Land",
                    "field": "land",
                    "headerFilter": "input",
                    "width":"150"
                }
            ]
JSON;
            break;
    }
    return $columns;
}

function get_campaigns_columns(): string
{
    $columnSettings = [
        [
            "title" => "Name",
            "field" => "name",
            "headerFilter" => "input",
        ]
    ];

    return json_encode($columnSettings);
}
