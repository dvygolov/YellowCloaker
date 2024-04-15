<?php
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/logging.php';

class MacrosProcessor
{
    private string $subid;
    private string $hashSalt;

    public function __construct($hashSalt, $subid = null)
    {
        $this->subid = $subid ?? get_cookie('subid');
        if (is_null($hashSalt))
            add_log("macros","Salt is NULL! Error constructing MarcrosProcessor for subid: $subid");
        $this->hashSalt = $hashSalt;
    }

    public function replace_html_macros($html): string
    {
        $ip = getip();
        $html = preg_replace_callback('/\{city,([^\}]+)\}/', function ($m) use ($ip) {
            return getcity($ip, $m[1]);
        }, $html);

        $html = preg_replace('/\{subid\}/', $this->subid, $html);

        $px = get_cookie('px');
        $html = preg_replace('/\{px\}/', $px, $html);
        return $html;
    }

    public function replace_url_macros($url): string
    {
        $url_components = parse_url($url);
        parse_str($url_components['query'] ?? '', $query_array);

        // Iterate over the $sub_ids and replace the keys
        foreach ($query_array as $qk => $qv) {
            if (empty($qv))
                continue;
            if ($qv[0] !== '{' || $qv[strlen($qv) - 1] !== '}')
                continue; //we need only macroses

            $macro = substr($qv, 1, strlen($qv) - 2);
            $macroValue = $this->get_macro_value($macro);
            if ($macroValue === false)
                continue; //HINT: should we log $url?
            $query_array[$qk] = $macroValue;
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

    private function get_macro_value($macro): string|bool
    {
        $db = new Db();
        $preset = ['subid', 'prelanding', 'landing'];
        if (in_array($macro, $preset)) {
            $cookie = get_cookie($macro);
            if (!empty($cookie) || $cookie !== '') {
                add_log("macros", "Couldn't get macros $macro value from cookie.");
                return false;
            }
            return $cookie;
        }
        //we need to find click parameter with this name, we can do that only if we know subid
        else if (str_starts_with($macro, "c.")) {
            if (!isset($this->subid) || empty($this->subid)) {
                add_log("macros", "Couldn't get macros $macro value from DB. Subid not set!");
                return false;
            } else {
                $clicks = $db->get_clicks_by_subid($this->subid, true);
                if(count($clicks[0]['params'])==0){
                    add_log("macros",
                        "Couldn't find click macro $macro value. Subid:$this->subid, Params are EMPTY!");
                    return false;
                }
                $p = $clicks[0]['params'];
                $cmacro = substr($macro, 2);
                if (array_key_exists($cmacro, $p)) {
                    return $p[$cmacro];
                } else {
                    add_log(
                    "macros",
                    "Couldn't find click macro $macro value. Subid:$this->subid, Params:" . json_encode($p)
                    );
                    return false;
                }
            }
        } else if ($macro === 'domain') {
            return $_SERVER['HTTP_HOST'];
        } else if (str_starts_with($macro, "hash:")) {
            $toHash = substr($macro, 5);
            $toHashValue = $this->get_macro_value($toHash);
            if ($toHashValue === false) {
                add_log("macros", "Couldn't find  macro $toHash value to hash. Subid:$this->subid");
                return false;
            }
            $hashed = crypt($toHashValue, $this->hashSalt);
            add_log("macros", "Hashing $toHashValue to $hashed");
            return $hashed;
        } else { //some kind of strange macros, we need to log this situation
            add_log("macros", "Couldn't find macros: $macro. Subid:$this->subid");
            return false;
        }
    }
}