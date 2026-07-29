<?php

function insert_file_content($html, $scriptname, $needle,
                             $before = true, $add_script_tags = false,
                             $search = null, $replacement = null): string
{
    $content = file_get_contents(__DIR__.'/scripts/'.$scriptname);
    if (empty($content)) {
        return $html;
    }

    $content = replace_content($content, $search, $replacement);

    if ($add_script_tags) {
        $content = "<script>{$content}</script>";
    }

    return $before ?
        insert_before_tag($html, $needle, $content) :
        insert_after_tag($html, $needle, $content);
}

function replace_content($content, $search, $replacement): string
{
    if ($search && $replacement) {
        $content = str_replace($search, $replacement, $content);
    }
    return $content;
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
        $finalpos = $pos + strlen($needle);
        //если у нас задан НЕ закрытый тег, то надо найти его конец
        if (!str_contains($needle, '>')) {
            while ($html[$finalpos] !== '>')
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
