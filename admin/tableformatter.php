<?php
function fill_table(string $table, array $header, array $data): string
{
    $countLines = 0;
    foreach ($data as $item) {
        $countLines++;
        $table .= "<TR><TD>" . $countLines . "</TD>";
        foreach ($header as $h) {
            $h = strtolower($h);
            $itemPart = $item[$h];
            switch ($h) {
                case 'subid':
                    $itemPart = "<TD><a href='#{$itemPart}'>{$itemPart}</a></TD>";
                    break;
                case 'time':
                    $itemPart = "<TD>" . date('Y-m-d H:i:s', $itemPart) . "</TD>";
                    break;
                case 'subs':
                    $itemPart = "<TD>" . http_build_query($itemPart) . "</TD>";
                    break;
                case 'email':
                    $itemPart = "<TD>" . (empty($itemPart) ? 'no' : $itemPart) . "</TD>";
                    break;
                default:
                    $itemPart = "<TD>{$itemPart}</TD>";
                    break;
            }
            $table .= $itemPart;
        }
        $table .= "</TR>";
    }
    return $table;
}

function create_table($header): string
{
    $table = "<TABLE class='table w-auto table-striped'>";
    $table .= "<thead class='thead-dark'>";
    $table .= "<TR>";
    $table .= "<TH scope='col'>Row</TH>";
    foreach ($header as $field) {
        $table .= "<TH scope='col'>" . $field . "</TH>";
    }
    $table .= "</TR></thead><tbody>";
    return $table;
}

function close_table($table): string
{
    $table .= "</tbody></TABLE>";
    return $table;
}