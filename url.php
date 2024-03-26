<?php
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/cookies.php';

//заменяем все макросы на реальные значения из куки
function replace_url_macros($url)
{
    $landing = get_cookie('landing');
    $prelanding = get_cookie('prelanding');
    $subid = get_subid();
    $px = get_cookie('px');
    //TODO:сделать вытаскивание пикселей

    $url = str_replace('{px}', $px, $url);
    $url = str_replace('{landing}', $landing, $url);
    $url = str_replace('{prelanding}', $prelanding, $url);
    $url = str_replace('{subid}', $subid, $url);
    $url = str_replace('{domain}', $_SERVER['HTTP_HOST'], $url);
    return $url;
}

function replace_subs_in_link($url)
{
    $preset = ['subid', 'prelanding', 'landing'];

    $url_components = parse_url($url);
    parse_str($url_components['query'] ?? '', $query_array);

    // Iterate over the $sub_ids and replace the keys
    foreach ($query_array as $qk=>$qv) {
        if ($qv[0]!=='{' || $qv[count($qv)-1]!=='}') continue; //we need only macroses

        if (in_array($key, $preset)) {
            // Set from cookie if not in query string
            $cookie = get_cookie($qv);
            if ($cookie!=='')
                $query_array[$qk] = $cookie;
        }
    }

    // Build the new query string
    $new_query = http_build_query($query_array);

    // Rebuild the URL
    $new_url = $url_components['scheme'] . '://' . $url_components['host'];
    if (isset($url_components['path'])) {
        $new_url .= $url_components['path'];
    }
    if ($new_query) {
        $new_url .= '?' . $new_query;
    }

    return $new_url;
}