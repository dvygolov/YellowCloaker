<?php
function get_columns(string $filter): string
{
    $columns = [];
    switch($filter){
    case 'blocked':
        $columns = [
            ["title"=>"IP","field"=>"ip","width"=>"150"],
            ["title"=>"Country","field"=>"country", "headerFilter"=>"input", "width"=>"50"], 
            ["title"=>"ISP","field"=>"isp", "headerFilter"=>"input"], 
            ["title"=>"Time","field"=>"time"], 
            ["title"=>"Reason","field"=>"reason", "formatter"=>"plaintext", "width"=>"80"], 
            ["title"=>"OS","field"=>"os", "headerFilter"=>"input","width"=>"100"], 
            ["title"=>"UA","field"=>"ua", "formatter"=>"textarea"], 
            ["title"=>"Subs","field"=>"subs"]
        ];
        break;
    case 'single':
        $columns = [
            ["title"=>"Subid","field"=>"subid"], 
            ["title"=>"IP","field"=>"iP"], 
            ["title"=>"Country","field"=>"country"], 
            ["title"=>"ISP","field"=>"isp"], 
            ["title"=>"Time","field"=>"time"], 
            ["title"=>"OS","field"=>"os"], 
            ["title"=>"UA","field"=>"ua"], 
            ["title"=>"Subs","field"=>"subs"], 
            ["title"=>"Preland","field"=>"preland"], 
            ["title"=>"Land","field"=>"land"]
        ];
        break;
    case 'leads':
        $columns = [
            ["title"=>"Subid","field"=>"subid", 
             "formatter"=>"link", 
             "formatterParams"=>["urlPrefix"=>"index.php?filter=single&subid=" ] ], 
            ["title"=>"Time","field"=>"time"], 
            ["title"=>"Name","field"=>"name"], 
            ["title"=>"Phone","field"=>"phone"], 
            ["title"=>"Status","field"=>"status"], 
            ["title"=>"Preland","field"=>"preland"], 
            ["title"=>"Land","field"=>"land"]
        ];
        break;
    default:
        $columns = [
            ["title"=>"Subid","field"=>"subid", 
             "formatter"=>"link", 
             "formatterParams"=>["urlPrefix"=>"index.php?filter=single&subid=" ] ], 
            ["title"=>"IP","field"=>"ip", "headerFilter"=>"input"], 
            ["title"=>"Country","field"=>"country", "headerFilter"=>"input"], 
            ["title"=>"ISP","field"=>"isp", "headerFilter"=>"input"], 
            ["title"=>"Time","field"=>"time"], 
            ["title"=>"OS","field"=>"os", "headerFilter"=>"input"], 
            ["title"=>"UA","field"=>"ua", "headerFilter"=>"input", "formatter"=>"textarea"], 
            ["title"=>"Subs","field"=>"subs"], 
            ["title"=>"Preland","field"=>"preland", "headerFilter"=>"input"], 
            ["title"=>"Land","field"=>"land", "headerFilter"=>"input"]
        ];
        break;
    }
    return json_encode($columns);
}

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
                    $itemPart = "<TD><a href='index.php?filter=single&subid={$itemPart}'>{$itemPart}</a></TD>";
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
                    if (is_array($itemPart)) {
                        $itemPart = implode(',', $itemPart);
                    }
                    $itemPart = "<TD>{$itemPart}</TD>";
                    break;
            }
            $table .= $itemPart;
        }
        $table .= "</TR>";
    }
    return $table;
}
