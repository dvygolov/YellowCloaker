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
        if (in_array($key,$preset)&& isset($_COOKIE[$key])) {
            $url.= $delimiter.$value.'='.$_COOKIE[$key];
        } elseif (!empty($_GET[$key])) {
            $url.= $delimiter.$value.'='.$_GET[$key];
        }
    }
    return $url;
}
?>
