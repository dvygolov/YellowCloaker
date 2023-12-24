<?php
require_once __DIR__ . '/filtersettings.php';
require_once __DIR__ . '/bases/browser/DetectorInterface.php';
require_once __DIR__ . '/bases/browser/UserAgent.php';
require_once __DIR__ . '/bases/browser/Os.php';
require_once __DIR__ . '/bases/browser/OsDetector.php';
require_once __DIR__ . '/bases/browser/AcceptLanguage.php';
require_once __DIR__ . '/bases/browser/Language.php';
require_once __DIR__ . '/bases/browser/LanguageDetector.php';
require_once __DIR__ . '/bases/iputils.php';
require_once __DIR__ . '/bases/ipcountry.php';

use Sinergi\BrowserDetector\Os;
use Sinergi\BrowserDetector\Language;

class Cloaker
{
    var FilterSettings $s;
    var array $block_reason = [];
    var array $click_params = [];

    public function __construct(FilterSettings $s)
    {
        $this->s = $s;
        $a = [];
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $a['referer'] = $_SERVER['HTTP_REFERER'];
        } else if (!empty($_COOKIE['referer'])) {
            $a['referer'] = $_COOKIE['referer'];
        } else {
            $a['referer'] = '';
        }

        $lang = new Language();
        $a['lang'] = $lang->getLanguage();
        $os = new Os();
        $a['os'] = $os->getName();
        $a['ip'] = getip();
        $a['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $a['country'] = getcountry($a['ip']);
        $a['isp'] = getisp($a['ip']);
        $this->click_params = $a;
    }

    public function is_bad_click(): bool
    {
        $this->block_reason = [];

        if ($this->has_bad_ua($this->click_params['ua'])) {
            $this->block_reason[] = 'ua';
            return true;
        }

        if ($this->has_bad_os($this->click_params['os'])) {
            $this->block_reason[] = 'os';
            return true;
        }

        if ($this->has_bad_language($this->click_params['lang'])) {
            $buf = strtoupper($this->click_params['lang']);
            $this->block_reason[] = 'language:' . $buf;
            return true;
        }

        if ($this->has_no_referer($this->click_params['referer'])) {
            $this->block_reason[] = 'noreferer';
            return true;
        }

        if ($this->has_bad_referer($this->click_params['referer'], $stop)) {
            $this->block_reason[] = 'refstop:' . $stop;
            return true;
        }

        if ($this->has_bad_tokens_in_url($_SERVER['REQUEST_URI'], $token)) {
            $this->block_reason[] = 'token:' . $token;
            return true;
        }

        if ($this->does_not_have_in_url($_SERVER['REQUEST_URI'], $should)) {
            $this->block_reason[] = 'url:' . $should;
            return true;
        }

        if ($this->has_bad_isp($this->click_params['isp'])) {
            $this->block_reason[] = 'isp';
            return true;
        }

        if ($this->has_bad_country($this->click_params['country'])) {
            $this->block_reason[] = 'country';
            return true;
        }

        if ($this->is_proxy_or_vpn($this->click_params['ip'])) {
            $this->block_reason[] = 'vnp&tor';
            return true;
        }

        if ($this->is_bot_by_mainbase($this->click_params['ip'])) {
            $this->block_reason[] = 'ipbase';
            return true;
        } else if ($this->is_bot_by_custombase($this->click_params['ip'])) {
            $this->block_reason[] = 'ipblack';
            return true;
        }

        return false;
    }

    private function is_proxy_or_vpn($ip): bool
    {
        if (!$this->s->block_vpnandtor) return false;
        $url = 'https://blackbox.ipinfo.app/lookup/';
        $res = file_get_contents($url . $ip);

        if (!is_string($res) || !strpos($http_response_header[0], "200")) {
            return false;
        }

        return $res === 'Y';
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

    private function does_not_have_in_url($uri, &$should): bool
    {
        if (empty($this->s->url_should_contain)) return false;
        foreach ($this->s->url_should_contain as $should) {
            if (empty($should)) continue;
            if (stripos($uri, $should) === false) return true;
        }
        return false;
    }

}