<?php

function get_file_content($scriptname): string
{
    $code_file_name = __DIR__ . '/scripts/' . $scriptname;
    if (!file_exists($code_file_name)) {
        echo 'File Not Found: ' . $code_file_name;
        return '';
    }

    return file_get_contents($code_file_name) ?: '';
}

function insert_file_content($html, $scriptname, $needle,
                             $before = true, $add_script_tags = false,
                             $search = null, $replacement = null): string
{
    $content = get_file_content($scriptname);
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

function insert_after_tag($html, $needle, $str_to_insert): string
{
    $positions = find_tag_positions($html, $needle, true);
    foreach ($positions as $pos) {
        $html = substr_replace($html, $str_to_insert, $pos, 0);
    }
    return $html;
}

function insert_before_tag($html, $needle, $str_to_insert): string
{
    $positions = find_tag_positions($html, $needle, false);
    foreach ($positions as $pos) {
        $html = substr_replace($html, $str_to_insert, $pos, 0);
    }
    return $html;
}

function find_tag_positions($html, $needle, $after_tag): array
{
    $lastPos = 0;
    $positions = array();
    while (($lastPos = strpos($html, $needle, $lastPos)) !== false) {
        $pos = $after_tag ? $lastPos + strlen($needle) + strpos(substr($html, $lastPos), '>') + 1 : $lastPos;
        $positions[] = $pos;
        $lastPos = $pos;
    }
    return array_reverse($positions);
}
