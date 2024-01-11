<?php

function get_columns(string $filter, $timezone): string
{
    $columns = [];
    switch ($filter) {
        case 'blocked':
            $columns = <<<JSON
            [
                {
                    "title": "IP",
                    "field": "ip",
                    "width": "150"
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
                    "width": "80"
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
                    "formatter": "textarea"
                },
                {
                    "title": "Subs",
                    "field": "subs",
                    "headerSort":false,
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
                }
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
                    "field": "os"
                },
                {
                    "title": "UA",
                    "field": "ua"
                },
                {
                    "title": "Subs",
                    "field": "subs",
                    "headerSort":false,
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
                    "title": "UA",
                    "field": "ua",
                    "headerFilter": "input",
                    "formatter": "textarea",
                    "width":"200"
                },
                {
                    "title": "Subs",
                    "field": "subs",
                    "headerSort":false,
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
