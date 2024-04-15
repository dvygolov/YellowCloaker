<?php
//Language detection
require_once __DIR__ . '/bases/lang/AcceptLanguage.php';
require_once __DIR__ . '/bases/lang/Language.php';
require_once __DIR__ . '/bases/lang/LanguageDetector.php';
//Device/Model/Browser/Platform detection
require_once __DIR__ . '/bases/device/autoload.php';
require_once __DIR__ . '/bases/device/Spyc.php';
//GEO and referer
require_once __DIR__ . '/bases/referer.php';
require_once __DIR__ . '/bases/iputils.php';
require_once __DIR__ . '/bases/ipcountry.php';

use Sinergi\BrowserDetector\Language;
use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;

class Cloaker
{
    var array $s;
    var string $block_reason = "";
    var array $click_params = [];

    public function __construct(array $s)
    {
        ClientHints::requestClientHints();
        DebugMethods::start();
        $this->s = $s;
        $this->click_params = Cloaker::get_click_params();
        DebugMethods::stop("YWBCoreConstruct");
    }

    public static function get_click_params(): array
    {
        $a = [];
        $a['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $a['referer'] = get_referer();
        $lang = new Language();
        $a['lang'] = $lang->getLanguage();

        $clientHints = ClientHints::factory($_SERVER); 
        $dd = new DeviceDetector($a['ua'], $clientHints);
        $dd->parse();

        $clientInfo = $dd->getClient();
        $a['client'] = $clientInfo['name'];
        $a['clientver'] = $clientInfo['version'];

        $osInfo = $dd->getOs();
        $a['os'] = $osInfo['name'];
        $a['osver'] = $osInfo['version'];
        $a['device'] = $dd->getDeviceName();
        $a['brand'] = $dd->getBrandName();
        $a['model'] = $dd->getModel();

        $a['ip'] = getip();
        $a['country'] = getcountry($a['ip']);
        $a['isp'] = getisp($a['ip']);
        $a['url'] = $_SERVER['REQUEST_URI'];
        return $a;
    }

    private function match_filters(bool $all, array|null $filters): bool
    {
        for ($i = 0; $i < count($filters); $i++) {
            $f = $filters[$i];
            if (!empty($f['condition'])) //this is a filter group
                $fRes = $this->match_filters($f['condition'] === 'AND', $f['rules']);
            else
                $fRes = $this->match_filter($f);
            if ($all && !$fRes)
                return false;
            if (!$all && $fRes)
                return true;
        }
        return $all; //if we are here, then for AND all are true and for OR all are false
    }


    private function match_filter(array $filter): bool
    {
        $val = $filter['value'] ?? '';

        switch ($filter['id']) {
            case 'os':
                return $this->operator($val, $filter['operator'], 'os');
            case 'country':
                return $this->operator($val, $filter['operator'], 'country');
            case 'language':
                return $this->operator($val, $filter['operator'], 'lang');
            case 'useragent':
                return $this->operator($val, $filter['operator'], 'ua');
            case 'isp':
                return $this->operator($val, $filter['operator'], 'isp');
            case 'url':
                return $this->operator($val, $filter['operator'], 'url');
            case 'referer':
                return $this->operator($val, $filter['operator'], 'referer');
            case 'vpntor':
                $vpnDetected = $this->is_proxy_or_vpn($this->click_params['ip']);
                if ($val === 0 && $vpnDetected)
                    return true;
                if ($val === 1 && !$vpnDetected)
                    return true;
                $this->block_reason = $filter['id'];
                return false;
            case 'ipbase':
                $inBase = $this->is_ip_in_base($this->click_params['ip'], $val);
                if ($filter['operator'] === 'contains' && $inBase)
                    return true;
                if ($filter['operator'] === 'not_contains' && !$inBase)
                    return true;
                $this->block_reason = $filter['id'];
                return false;
        }
        return true;
    }

    public function is_bad_click(): bool
    {
        try {
            DebugMethods::start();
            if (!array_key_exists('rules', $this->s))
                return false;
            return !$this->match_filters($this->s['condition'] === 'AND', $this->s['rules']);
        } finally {
            DebugMethods::stop("YWBCoreCheck");
        }
    }

    private function is_proxy_or_vpn($ip): bool
    {
        $url = 'https://blackbox.ipinfo.app/lookup/';
        $res = file_get_contents($url . $ip);

        if (!is_string($res) || !strpos($http_response_header[0], "200")) {
            return false;
        }

        if ($res === 'Y')
            return true;

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

    private function is_ip_in_base($ip, $baseFileName): bool
    {
        $base_full_path = __DIR__ . "/bases/" . $baseFileName;
        if (!file_exists($base_full_path))
            return false;
        $cidr = file($base_full_path, FILE_IGNORE_NEW_LINES);
        return IpUtils::checkIp($ip, $cidr);
    }

    private function operator(string $val, string $operator, string $param): bool
    {
        $check = true;
        switch ($operator) {
            case 'in':
                $values = explode(',', $val);
                $check = $this->in_arrayi($this->click_params[$param], $values);
                break;
            case 'not_in':
                $values = explode(',', $val);
                $check = !$this->in_arrayi($this->click_params[$param], $values);
                break;
            case 'contains':
                $values = explode(',', $val);
                $contains = false;
                foreach ($values as $value) {
                    if (empty($value))
                        continue;
                    if (stripos($this->click_params[$param], $value) !== false) {
                        $contains = true;
                        break;
                    }
                }
                if (!$contains)
                    $check = false;
                break;
            case 'not_contains':
                $values = explode(',', $val);
                $contains = false;
                foreach ($values as $value) {
                    if (empty($value))
                        continue;
                    if (stripos($this->click_params[$param], $value) !== false) {
                        $contains = true;
                        break;
                    }
                }
                if ($contains)
                    $check = false;
                break;
            case 'not_equal':
                $check = strtolower($this->click_params[$param]) !== strtolower($val);
                break;
            default:
                throw new Exception("No operator $operator defined for $param check!");
        }
        if (!$check) {
            $this->block_reason = $param;
            return false;
        }
        return true;
    }

    private function in_arrayi($needle, $haystack)
    {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }
}
