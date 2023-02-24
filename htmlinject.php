<?php

function get_file_content($scriptname)
{
    $code_file_name = __DIR__ . '/scripts/' . $scriptname;
    if (!file_exists($code_file_name)) {
        echo 'File Not Found ' . $code_file_name;
        return false;
    }
    $script_code = file_get_contents($code_file_name);
    if (empty($script_code)) return false;
    return $script_code;
}

function insert_file_content($html, $scriptname, $needle, $before = true, $add_script_tags = false, $search = null, $replacement = null)
{
    $content = get_file_content($scriptname);
    if (!$content) return $html;

    if ($search && $replacement) {
        if (is_array($search) && is_array($replacement) && count($search) === count($replacement)) {
            for ($i = 0; $i < count($search); $i++) {
                $content = str_replace($search[$i], $replacement[$i], $content);
            }
        } else {
            $content = str_replace($search, $replacement, $content);
        }
    }

    if ($add_script_tags) {
        $content = "<script>{$content}</script>";
    }

    if ($before) {
        $html = insert_before_tag($html, $needle, $content);
    } else {
        $html = insert_after_tag($html, $needle, $content);
    }

    return $html;
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
        if (strpos($needle, '>') === false) {
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