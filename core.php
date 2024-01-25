<?php
require_once __DIR__ . '/bases/browser/DetectorInterface.php';
require_once __DIR__ . '/bases/browser/UserAgent.php';
require_once __DIR__ . '/bases/browser/Os.php';
require_once __DIR__ . '/bases/browser/OsDetector.php';
require_once __DIR__ . '/bases/browser/AcceptLanguage.php';
require_once __DIR__ . '/bases/browser/Language.php';
require_once __DIR__ . '/bases/browser/LanguageDetector.php';
require_once __DIR__ . '/bases/browser/Referer.php';
require_once __DIR__ . '/bases/iputils.php';
require_once __DIR__ . '/bases/ipcountry.php';

use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\Language;

class Cloaker
{
    var array $s;
    var string $block_reason = "";
    var array $click_params = [];

    public function __construct(array $s)
    {
        DebugMethods::start();
        $this->s = $s;
        $this->click_params = Cloaker::get_click_params();
        DebugMethods::stop("YWBCoreConstruct");
    }

    public static function get_click_params(): array
    {
        $a = [];
        $a['referer'] = get_referer();
        $lang = new Language();
        $a['lang'] = $lang->getLanguage();
        $os = new Os();
        $a['os'] = $os->getName();
        $a['ip'] = getip();
        $a['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $a['country'] = getcountry($a['ip']);
        $a['isp'] = getisp($a['ip']);
        return $a;
    }

    private function match_all_filters(bool $all, array $filters):bool
    {
        for($i=0;$i<count($filters);$i++){
            if (!empty($filters[$i]['condition']))
                $fRes = $this->match_all_filters($filters[$i]['condition']==='AND',$filters[$i]['rules']);
            else
                $fRes = $this->match_filter($filters[$i]);
            if ($all && !$fRes) return false;
            if (!$all && $fRes) return true;
        }
        return true;
    }
    private function match_filter(array $filter):bool
    {
        $val = $filter['value'];

        switch ($filter['id']){
            case 'os':
                $oses = explode(',',$val);
                if ($filter['operator']==='in')
                    return in_array($this->click_params['os'],$oses);
                else 
                    return !in_array($this->click_params['os'],$oses);
            case 'country':
                $countries = explode(',',$val);
                if ($filter['operator']==='in')
                    return in_array($this->click_params['country'],$countries);
                else 
                    return !in_array($this->click_params['country'],$countries);
            case 'language':
                $langs = explode(',',$val);
                if ($filter['operator']==='in')
                    return in_array($this->click_params['lang'],$langs);
                else 
                    return !in_array($this->click_params['lang'],$langs);
            case 'useragent':
                break;
            case 'isp':
                break;
            case 'url':
                break;
            case 'referer':
                break;
            case 'vpntor':
                break;
            case 'ipbase':
                break;
        }
    }



    public function is_bad_click(): bool
    {
        return $this->match_all_filters($this->s['condition']==='AND', $this->s['rules']);

        try {
            DebugMethods::start();
            $this->block_reason = "";

            if ($this->has_bad_ua($this->click_params['ua'])) {
                $this->block_reason = 'ua';
                return true;
            }

            if ($this->has_bad_os($this->click_params['os'])) {
                $this->block_reason = 'os';
                return true;
            }

            if ($this->has_bad_language($this->click_params['lang'])) {
                $buf = strtoupper($this->click_params['lang']);
                $this->block_reason = 'lang:' . $buf;
                return true;
            }

            if ($this->has_no_referer($this->click_params['referer'])) {
                $this->block_reason = 'noreferer';
                return true;
            }

            if ($this->has_bad_referer($this->click_params['referer'], $stop)) {
                $this->block_reason = 'badref:' . $stop;
                return true;
            }

            if ($this->has_bad_tokens_in_url($_SERVER['REQUEST_URI'], $token)) {
                $this->block_reason = 'badurl:' . $token;
                return true;
            }

            if ($this->does_not_have_in_url($_SERVER['REQUEST_URI'])) {
                $this->block_reason = 'notinurl';
                return true;
            }

            if ($this->has_bad_isp($this->click_params['isp'])) {
                $this->block_reason = 'isp';
                return true;
            }

            if ($this->has_bad_country($this->click_params['country'])) {
                $this->block_reason = 'country';
                return true;
            }

            if ($this->is_proxy_or_vpn($this->click_params['ip'])) {
                $this->block_reason = 'vnp&tor';
                return true;
            }

            if ($this->is_bot_by_mainbase($this->click_params['ip'])) {
                $this->block_reason = 'ipbase';
                return true;
            } else if ($this->is_bot_by_custombase($this->click_params['ip'])) {
                $this->block_reason = 'custbase';
                return true;
            }

            return false;
        }
        finally {
            DebugMethods::stop("YWBCoreCheck");
        }
    }

    private function is_proxy_or_vpn($ip): bool
    {
        if (!$this->s->block_vpnandtor) return false;
        $url = 'https://blackbox.ipinfo.app/lookup/';
        $res = file_get_contents($url . $ip);

        if (!is_string($res) || !strpos($http_response_header[0], "200")) {
            return false;
        }

        if ($res === 'Y') return true;

        $ipintel = $this->is_bad_by_ipintel($ip);
        return ($ipintel === null ? false : $ipintel);
    }

    private function is_bad_by_ipintel($ip): ?bool
    {
        $contactEmail = "support@" . $_SERVER['HTTP_HOST'];
        $timeout = 5; //by default, wait no longer than 5 secs for a response
        $banOnProbability = 0.99;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_URL, "http://check.getipintel.net/check.php?ip=$ip&contact=$contactEmail&flags=m");
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response > $banOnProbability) {
            return true;
        } else {
            if ($response < 0 || strcmp($response, "") == 0) {
                return null;
            }
            return false;
        }
    }

    private function is_bot_by_mainbase($ip): bool
    {
        $base_full_path = __DIR__ . "/bases/bots.txt";
        if (!file_exists($base_full_path)) return false;
        $cidr = file($base_full_path, FILE_IGNORE_NEW_LINES);
        return IpUtils::checkIp($ip, $cidr);
    }

    private function is_bot_by_custombase($ip): bool
    {
        $base_file_name = $this->s->ip_black_filename;
        if (empty($base_file_name)) return false;
        $base_full_path = __DIR__ . "/bases/" . $this->s->ip_black_filename;
        if (!file_exists($base_full_path)) return false;
        if ($this->s->ip_black_cidr) {
            $cbf = file($base_full_path, FILE_IGNORE_NEW_LINES);
            return IpUtils::checkIp($ip, $cbf);
        } else
            return (strpos(file_get_contents($base_full_path), $ip) !== false);
    }

    private function has_bad_isp($isp): bool
    {
        if (empty($this->isp_black)) return false;
        foreach ($this->isp_black as $isp_black_single)
            if (!empty(stristr($isp, $isp_black_single))) return true;
        return false;
    }

    private function has_bad_ua($ua): bool
    {
        if (count($this->s->ua_black) == 0) return false;
        foreach ($this->s->ua_black as $ua_black_single)
            if (!empty(stristr($ua, $ua_black_single))) return true;
        return false;
    }

    private function has_bad_os($os): bool
    {
        if (empty($this->s->os_white)) return false;
        return !in_array($os, $this->s->os_white);
    }

    private function has_bad_country($country): bool
    {
        if (empty($this->s->country_white)) return false;
        if (in_array('WW', $this->s->country_white)) return false;
        return !in_array($country, $this->s->country_white);
    }

    private function has_bad_language($lang): bool
    {
        if (empty($this->s->lang_white)) return false;
        if (in_array('any', $this->s->lang_white)) return false;
        return !in_array($lang, $this->s->lang_white);
    }

    private function has_no_referer($ref): bool
    {
        if (!$this->s->block_without_referer) return false;
        return empty($ref);
    }

    private function has_bad_referer($ref, &$stop): bool
    {
        if (empty($this->s->referer_stopwords)) return false;
        if (empty($ref)) return false;
        foreach ($this->s->referer_stopwords as $stop) {
            if (empty($stop)) continue;
            if (stripos($ref, $stop) !== false)
                return true;
        }
        return false;
    }

    private function has_bad_tokens_in_url($uri, &$token): bool
    {
        if (empty($this->s->tokens_black)) return false;
        foreach ($this->s->tokens_black as $token) {
            if (empty($token)) continue;
            if (stripos($uri, $token) !== false) return true;
        }
        return false;
    }

    private function does_not_have_in_url($uri): bool
    {
        if (empty($this->s->url_should_contain)) return false;
        foreach ($this->s->url_should_contain as $should) {
            if (empty($should)) continue;
            if (stripos($uri, $should) !== false) return false;
        }
        return true;
    }
}
