<?php
require_once __DIR__.'/settings.php';
require_once __DIR__.'/cookies.php';
require_once __DIR__.'/pixels.php';

//заменяем все макросы на реальные значения из куки
function replace_all_macros($url)
{
    global $fbpixel_subname;
    $px = get_fbpixel();
    $landing = get_cookie('landing');
    $prelanding = get_cookie('prelanding');
    $subid = get_subid();

    $tmp_url = str_replace('{px}', $px, $url);
    $tmp_url = str_replace('{landing}', $landing, $tmp_url);
    $tmp_url = str_replace('{prelanding}', $prelanding, $tmp_url);
    $tmp_url = str_replace('{subid}', $subid, $tmp_url);
    $tmp_url = str_replace('{domain}', $_SERVER['SERVER_NAME'], $tmp_url);
    return $tmp_url;
}

function add_querystring($url)
{
    $delimiter= (strpos($url, '?')===false?"?":"&");
    $querystr = $_SERVER['QUERY_STRING'];
    if (!empty($querystr)) {
        $url = $url.$delimiter.$querystr;
    }
    return $url;
}

function add_subs_to_link($url)
{
    global $sub_ids;
    $preset=['subid','prelanding','landing'];
    foreach ($sub_ids as $sub) {
    	$key = $sub["name"];
        $value = $sub["rewrite"];
        $delimiter= (strpos($url, '?')===false?"?":"&");
        if (in_array($key,$preset)){
            $cookie = get_cookie($key);
            if ($cookie!=='')
                $url.= $delimiter.$value.'='.$cookie;
        } elseif (!empty($_GET[$key])) {
            $url.= $delimiter.$value.'='.$_GET[$key];
        }
    }
    return $url;
}


function replace_subs_in_link($url)
{
    global $sub_ids;
    $preset = ['subid', 'prelanding', 'landing'];

    // Parse the URL to get its components
    $url_components = parse_url($url);

    // Parse the query string into an associative array
    parse_str($url_components['query'] ?? '', $query_array);

    // Iterate over the $sub_ids and replace the keys
    foreach ($sub_ids as $sub) {
        $key = $sub["name"];
        $value = $sub["rewrite"];

        if (array_key_exists($key, $query_array)) {
            // Replace the key in the query array
            $query_array[$value] = $query_array[$key];
            unset($query_array[$key]);
        } elseif (in_array($key, $preset)) {
            // Set from cookie if not in query string
            $cookie = get_cookie($key);
            if ($cookie!=='')
                $query_array[$value] = $cookie;
        }
    }

    // Build the new query string
    $new_query = http_build_query($query_array);

    // Rebuild the URL
    $new_url = $url_components['scheme'] . '://' . $url_components['host'];
    if (isset($url_components['path'])) {
        $new_url .= $url_components['path'];
    }
    
    if (isset($url_components['fragment'])) {
        $new_url .= '#'.$url_components['fragment'];
    }

    if ($new_query) {
        $new_url .= '?' . $new_query;
    }

    return $new_url;
}
?>
