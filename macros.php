<?php
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logging.php';

function replace_html_macros($html): string
{
    $ip = getip();
    $html = preg_replace_callback('/\{city,([^\}]+)\}/', function ($m) use ($ip) {
        return getcity($ip, $m[1]);
    }, $html);

    $subid = get_subid();
    $html = preg_replace('/\{subid\}/', $subid, $html);

    $px = get_px();
    $html = preg_replace('/\{px\}/', $px, $html);
    return $html;
}

function replace_url_macros($url, $subid=null)
{
    $subid = $subid??get_subid();
    $db = new Db();
    $preset = ['subid', 'prelanding', 'landing'];

    $url_components = parse_url($url);
    parse_str($url_components['query'] ?? '', $query_array);

    // Iterate over the $sub_ids and replace the keys
    foreach ($query_array as $qk=>$qv) {
        if (empty($qv)) continue;
        if ($qv[0]!=='{' || $qv[count($qv)-1]!=='}') continue; //we need only macroses

        $macro = substr($qv,1,strlen($qv)-2);;
        if (in_array($macro, $preset)) {
            $cookie = get_cookie($macro);
            if (!empty($cookie) && $cookie!=='')
                $query_array[$qk] = $cookie;
        }
        //we need to find click parameter with this name, we can do that only if we know subid
        else if (str_starts_with($macro,"c.")){
            if (!isset($subid)||empty($subid)){
                add_log("macros","Couldn't get macros $macro value from DB. Subid not set! Str:$url");
            } 
            else{
                $clicks = $db->get_clicks_by_subid($subid,true);
                $p = json_decode($clicks[0]['params'],true);
                $cmacro = substr($macro,2);
                if (array_key_exists($cmacro,$p)){
                    $query_array[$qk] = $p[$cmacro];
                }
                else{
                    add_log("macros",
                        "Couldn't find click macro $macro value. Subid:$subid, Str:$url, Params:".json_encode($p));
                }
            }
        }
        else if ($macro === 'domain'){
            $query_array[$qk] = $_SERVER['HTTP_HOST'];
        }
        else{ //some kind of strange macros, we need to log this situation
            add_log("macros","Couldn't find macros: $macro. Str:$url, Subid:$subid");
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