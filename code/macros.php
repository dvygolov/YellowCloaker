<?php
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/logging.php';

class MacrosProcessor
{
    private ?string $clickid;
    private ?string $userid;
    private ?array $clickParams;
    public function __construct(?Campaign $campaign = null, ?array $clickParams = null, ?string $clickid = null, ?string $userid = null)
    {
        $this->clickid = $clickid ?? get_clickid();
        $this->userid = $userid ?? get_userid();
        $this->clickParams = $clickParams;
    }

    public function replace_html_macros($html): string
    {
        $html = preg_replace('/\{clickid\}/', $this->clickid ?? '', $html);
        $html = preg_replace('/\{userid\}/', $this->userid ?? '', $html);

        $px = get_cookie('px');
        $html = preg_replace('/\{px\}/', $px, $html);
        return $html;
    }

    public function replace_url_macros($url): string
    {
        if (empty($url)) return "";
        $url_components = parse_url($url);
        if ($url_components === false) {
            return $url;
        }
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

        // Rebuild the URL (supports both absolute and relative URLs)
        $new_url = '';
        if (isset($url_components['scheme'])) {
            $new_url .= $url_components['scheme'] . '://';
        }
        if (isset($url_components['user'])) {
            $new_url .= $url_components['user'];
            if (isset($url_components['pass'])) {
                $new_url .= ':' . $url_components['pass'];
            }
            $new_url .= '@';
        }
        if (isset($url_components['host'])) {
            $new_url .= $url_components['host'];
        }
        if (isset($url_components['port'])) {
            $new_url .= ':' . $url_components['port'];
        }
        if (isset($url_components['path'])) {
            $new_url .= $url_components['path'];
        }
        if ($new_query) {
            $new_url .= '?' . $new_query;
        }
        if (isset($url_components['fragment'])) {
            $new_url .= '#' . $url_components['fragment'];
        }

        return $new_url === '' ? $url : $new_url;
    }

    private function get_macro_value($macro): string|bool
    {
        global $db;
        
        return match(true) {
            $macro === 'clickid' => $this->clickid ?? false,
            $macro === 'userid' => $this->userid ?? false,
            $macro === 'domain' => $_SERVER['HTTP_HOST'],
            $macro === 'time' => time(),
            
            // Click parameter names
            in_array($macro, ['ip', 'country', 'lang', 'os', 'osver', 'client', 'clientver', 'device', 'brand', 'model', 'isp', 'ua', 'status']) => 
                $this->getClickParam($macro),
            
            // Custom click parameters (c.*)
            str_starts_with($macro, 'c.') => 
                $this->getCustomClickParam($macro),
            
            // Hash macros (hash:*)
            str_starts_with($macro, 'hash:') => 
                $this->getHashedMacro($macro),
            
            // Random macros (random:*)
            str_starts_with($macro, 'random:') => 
                $this->getRandomMacro($macro),
            
            // Unknown macro
            default => $this->logUnknownMacro($macro)
        };
    }
    
    private function getClickParam($macro): string|bool
    {
        if (!empty($this->clickParams) && array_key_exists($macro, $this->clickParams))
            return (string)$this->clickParams[$macro];
        if (!empty($this->clickid)) {
            global $db;
            $click = $db->get_click_by_clickid($this->clickid);
            return $click[$macro] ?? false;
        }
        add_log("macros", "Couldn't get macros $macro value. Clickparams and clickid not set!");
        return false;
    }
    
    private function getCustomClickParam($macro): string|bool
    {
        if (!empty($this->clickParams)) {
            $cmacro = substr($macro, 2);
            if (array_key_exists($cmacro, $this->clickParams)) {
                return (string)$this->clickParams[$cmacro];
            }
            if (isset($this->clickParams['params']) && is_array($this->clickParams['params']) && array_key_exists($cmacro, $this->clickParams['params'])) {
                return (string)$this->clickParams['params'][$cmacro];
            }
        }

        if (empty($this->clickid)) {
            add_log("macros", "Couldn't get macros $macro value from DB. Clickid not set!");
            return false;
        }

        global $db;
        $click = $db->get_click_by_clickid($this->clickid);
        if (empty($click['params']) || count($click['params']) == 0) {
            add_log("macros", "Couldn't find click macro $macro value. Clickid:{$this->clickid}, Params are EMPTY!");
            return false;
        }
        
        $p = $click['params'];
        $cmacro = substr($macro, 2);
        if (array_key_exists($cmacro, $p)) {
            return $p[$cmacro];
        }
        
        add_log("macros", "Couldn't find click macro $macro value. Clickid:{$this->clickid}, Params:" . json_encode($p));
        return false;
    }
    
    private function getHashedMacro($macro): string|bool
    {
        $toHash = substr($macro, 5);
        $toHashValue = $this->get_macro_value($toHash);
        if ($toHashValue === false) {
            add_log("macros", "Couldn't find macro $toHash value to hash. Clickid:{$this->clickid}");
            return false;
        }
        $hashed = md5($toHashValue);
        add_log("macros", "Hashing $toHashValue to $hashed");
        return $hashed;
    }
    
    private function getRandomMacro($macro): int
    {
        $range = explode('-', substr($macro, 7));
        $selected = rand($range[0], $range[1]);
        add_log("macros", "Got random $selected from range " . implode('-', $range));
        return $selected;
    }
    
    private function logUnknownMacro($macro): bool
    {
        add_log("macros", "Couldn't find macros: $macro. Clickid:{$this->clickid}");
        return false;
    }
}
