<?php

function insert_file_content_with_replace($html, $scriptname, $needle, $search, $replacement)
{
    $code_file_name=__DIR__.'/scripts/'.$scriptname;
    if (!file_exists($code_file_name)) {
        echo 'File Not Found '.$code_file_name;
        return $html;
    }
    $script_code = file_get_contents($code_file_name);
    if (empty($script_code)) return $html;
    //we have multiple replacements
    if (is_array($search)&&is_array($replacement)&&count($search)===count($replacement)){
        for ($i = 0; $i < count($search); $i++)
        {
            $script_code = str_replace($search[$i], $replacement[$i], $script_code);
        }
    }
    else
        $script_code = str_replace($search, $replacement, $script_code);
    return insert_before_tag($html, $needle, $script_code);
}

function insert_file_content($html, $scriptname, $needle, $before=true)
{
    $code_file_name=__DIR__.'/scripts/'.$scriptname;
    if (!file_exists($code_file_name)) {
        echo 'File Not Found '.$code_file_name;
        return $html;
    }
    $script_code = file_get_contents($code_file_name);
    if (empty($script_code)) return $html;
    if ($before)
        return insert_before_tag($html, $needle, $script_code);
    else
        return insert_after_tag($html, $needle, $script_code);
}

function insert_after_tag($html, $needle, $str_to_insert)
{
    $lastPos = 0;
    $positions = array();
    while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
        $positions[] = $lastPos;
        $lastPos = $lastPos + strlen($needle);
    }
    $positions = array_reverse($positions);
    foreach ($positions as $pos) {
        $finalpos=$pos+strlen($needle);
        //если у нас задан НЕ закрытый тег, то надо найти его конец
        if (strpos($needle,'>')===false)
        {
            while($html[$finalpos]!=='>')
                $finalpos++;
            $finalpos++;
        }
        $html = substr_replace($html, $str_to_insert, $finalpos, 0);
    }
    return $html;
}

function insert_before_tag($html, $needle, $str_to_insert)
{
    $lastPos = 0;
    $positions = array();
    while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
        $positions[] = $lastPos;
        $lastPos = $lastPos + strlen($needle);
    }
    $positions = array_reverse($positions);

    foreach ($positions as $pos) {
        $html = substr_replace($html, $str_to_insert, $pos, 0);
    }
    return $html;
}
?>
