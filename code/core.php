<?php

//Language detection
require_once __DIR__ . '/bases/language.php';
//Device/Model/Browser/Platform detection
require_once __DIR__ . '/bases/device/autoload.php';
require_once __DIR__ . '/bases/device/ClientHints.php';
require_once __DIR__ . '/bases/device/DeviceDetector.php';
require_once __DIR__ . '/bases/device/Spyc.php';
//DeviceDetector caching
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiGetCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiDeleteCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiPutCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/Cache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/FlushableCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/ClearableCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/MultiOperationCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/CacheProvider.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/FileCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/PhpFileCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/ApcuCache.php';
require_once __DIR__ . '/bases/device/Cache/Doctrine/ChainCache.php';

require_once __DIR__ . '/bases/device/Cache/CacheInterface.php';
require_once __DIR__ . '/bases/device/Cache/DoctrineBridge.php';
//GEO and referer
require_once __DIR__ . '/bases/iputils.php';
require_once __DIR__ . '/bases/ipcountry.php';
require_once __DIR__ . '/proxyvpn.php';

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Cache\DoctrineBridge;
use DeviceDetector\Parser\Device\AbstractDeviceParser;

function request_device_client_hints(): void
{
    if (headers_sent()) {
        return;
    }

    $headers = [
        'Sec-CH-UA', 'Sec-CH-UA-Arch', 'Sec-CH-UA-Bitness',
        'Sec-CH-UA-Full-Version', 'Sec-CH-UA-Full-Version-List',
        'Sec-CH-UA-Mobile', 'Sec-CH-UA-Platform', 'Sec-CH-UA-Platform-Version',
        'Sec-CH-UA-WoW64', 'Sec-CH-UA-Model', 'Sec-CH-UA-Form-Factors',
    ];
    $headerList = implode(', ', $headers);
    header('Accept-CH: ' . $headerList, true);
    header('Critical-CH: ' . $headerList, true);
    header('Vary: ' . $headerList, true);
}

function create_device_detector_cache(string $cacheDir): DoctrineBridge
{
    $fileCache = new Doctrine\Common\Cache\PhpFileCache($cacheDir);
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        $cache = new Doctrine\Common\Cache\ChainCache([
            new Doctrine\Common\Cache\ApcuCache(),
            $fileCache,
        ]);
        return new DoctrineBridge($cache);
    }

    return new DoctrineBridge($fileCache);
}

class FiltrationCore
{
    public string $block_reason = "";
    public array $matched_filters = [];
    public array $click_params = [];
    private ?Closure $runtimeFilterResolver = null;

    public function __construct(array $prefill = [])
    {
        DebugMethods::start("YWBCoreConstruct");
        $this->click_params = self::get_click_params($prefill);
        DebugMethods::stop("YWBCoreConstruct");
    }

    public static function get_click_params(array $prefill = []): array
    {
        request_device_client_hints();
        $a = [];
        $a['ua'] = $prefill['tds_ua'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $a['referer'] = $prefill['tds_ref'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        $lang = $prefill['tds_lang'] ?? $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $a['lang'] = LanguageDetector::detect($lang);

        $clientHints = ClientHints::factory($prefill['tds_client_hints'] ?? $_SERVER);
        $dd = new DeviceDetector($a['ua'], $clientHints);
        $dd->discardBotInformation();

        DebugMethods::start("YWBCoreDeviceDetector");
        $cachePath = get_cache_path('devices');
        $cacheDir = (DIRECTORY_SEPARATOR === '\\' ? preg_match('/^[A-Za-z]:/', $cachePath) : str_starts_with($cachePath, '/'))
            ? $cachePath . '/'
            : __DIR__ . '/' . $cachePath . '/';
        $dd->setCache(create_device_detector_cache($cacheDir));
        $dd->parse();
        $a['bot'] = $dd->isBot() ? 'yes' : 'no';
        $clientInfo = $dd->getClient() ?: [];
        $a['client'] = (string)($clientInfo['name'] ?? '');
        $a['clientver'] = (string)($clientInfo['version'] ?? '');
        DebugMethods::stop("YWBCoreDeviceDetector");

        $osInfo = $dd->getOs() ?: [];
        $a['os'] = (string)($osInfo['name'] ?? '');
        $a['osver'] = (string)($osInfo['version'] ?? '');
        $a['device'] = $dd->getDeviceName();
        $a['brand'] = $dd->getBrandName();
        $a['model'] = $dd->getModel();

        DebugMethods::start("YWBCoreMaxMind");
        $a['ip'] = getip($prefill['tds_ip'] ?? $_SERVER);
        $a['country'] = getcountry($a['ip']);
        $a['isp'] = getisp($a['ip']);
        DebugMethods::stop("YWBCoreMaxMind");

        $a['url'] = $prefill['tds_url'] ?? $_SERVER['REQUEST_URI'];
        //host - is where from the traffic comes
        $a['host'] = $prefill['tds_host'] ?? $_SERVER['HTTP_HOST'];
        //domain is where the traffic goes
        $a['domain'] = $_SERVER['HTTP_HOST'];
        parse_str($prefill['tds_qs'] ?? $_SERVER['QUERY_STRING'] ?? '', $a['qs']);
        return $a;
    }

    private function match_filters(bool $all, array|null $filters): bool
    {
        for ($i = 0; $i < count($filters); $i++) {
            $f = $filters[$i];
            if (!empty($f['condition'])) {//this is a filter group
                $fRes = $this->match_filters($f['condition'] === 'AND', $f['rules']);
            } else {
                $fRes = $this->match_filter($f);
            }
            if ($all && !$fRes) {
                return false;
            }
            if (!$all && $fRes) {
                return true;
            }
        }
        return $all; //if we are here, then for AND all are true and for OR all are false
    }


    private function match_filter(array $filter): bool
    {
        $val = $filter['value'] ?? '';
        $curParamName = $filter['id'];

        $standardParams = [
            'os',
            'osver',
            'device',
            'bot',
            'brand',
            'model',
            'client',
            'clientver',
            'country',
            'lang',
            'useragent',
            'isp',
            'referer',
            'domain',
            'host'
        ];
        if (in_array($curParamName, $standardParams)) {
            $paramValue = $this->click_params[$curParamName];
            $check = $this->operator($val, $filter['operator'], $paramValue);
            if ($check) {
                $this->matched_filters[] = $curParamName;
                return true;
            }
        } else {
            switch ($curParamName) {
                case 'urlparam':
                    if ($this->match_url_param_filter($filter)) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    break;
                case 'vpntor':
                    $vpnDetected = $this->is_proxy_or_vpn($this->click_params['ip']);
                    if ($val === 0 && $vpnDetected) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    if ($val === 1 && !$vpnDetected) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    break;
                case 'ipbase':
                    $inBase = $this->is_ip_in_base($this->click_params['ip'], $val);
                    if ($filter['operator'] === 'in' && $inBase) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    if ($filter['operator'] === 'not_in' && !$inBase) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    break;
                case 'uniqueness':
                    $scope = in_array($val, ['campaign', 'flow'], true) ? $val : 'campaign';
                    $isUnique = $this->runtimeFilterResolver === null
                        ? true
                        : (bool)($this->runtimeFilterResolver)($scope);
                    $matches = match ($filter['operator'] ?? '') {
                        'is_unique' => $isUnique,
                        'is_not_unique' => !$isUnique,
                        default => false,
                    };
                    if ($matches) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    break;
                case 'conversion_cap_campaign':
                case 'conversion_cap_flow':
                    $matches = $this->runtimeFilterResolver === null
                        ? true
                        : (bool)($this->runtimeFilterResolver)($curParamName, $filter);
                    if ($matches) {
                        $this->matched_filters[] = $curParamName;
                        return true;
                    }
                    break;
                default:
                    die("No operator defined for '$curParamName' check!");
            }
        }
        return false;
    }

    private function operator(string $val, string $operator, string $paramValue): bool
    {
        $check = true;
        switch ($operator) {
            case 'param_in':
            case 'in':
                $values = $this->split_filter_values($val);
                $check = $this->in_arrayi($paramValue, $values);
                break;
            case 'param_not_in':
            case 'not_in':
                $values = $this->split_filter_values($val);
                $check = !$this->in_arrayi($paramValue, $values);
                break;
            case 'contains':
                $values = $this->split_filter_values($val);
                $contains = false;
                foreach ($values as $value) {
                    if (empty($value))
                        continue;
                    if (stripos($paramValue, $value) !== false) {
                        $contains = true;
                        break;
                    }
                }
                if (!$contains) {
                    $check = false;
                }
                break;
            case 'not_contains':
                $values = $this->split_filter_values($val);
                $contains = false;
                foreach ($values as $value) {
                    if (empty($value)) {
                        continue;
                    }
                    if (stripos($paramValue, $value) !== false) {
                        $contains = true;
                        break;
                    }
                }
                if ($contains) {
                    $check = false;
                }
                break;
            case 'less_or_equal':
                $check = version_compare($paramValue, $val, '<=');
                break;
            case 'greater_or_equal':
                $check = version_compare($paramValue, $val, '>=');
                break;
            case 'equal':
                $check = strtolower($paramValue) === strtolower($val);
                break;
            case 'not_equal':
                $check = strtolower($paramValue) !== strtolower($val);
                break;
            default:
                die("Operator $operator is not defined!");
        }
        return $check;
    }

    private function in_arrayi(string $needle, array $haystack): bool
    {
        foreach ($haystack as $item) {
            if (strcasecmp($needle, $item) === 0) {
                return true;
            }
        }
        return false;
    }

    private function split_filter_values(string $val): array
    {
        return array_map('trim', explode(',', $val));
    }

    private function match_url_param_filter(array $filter): bool
    {
        $val = $filter['value'] ?? '';
        $operator = $filter['operator'] ?? '';
        $clickQS = $this->click_params['qs'];
        $pName = is_array($val) ? (string) ($val[0] ?? '') : (string) $val;
        $paramExists = $pName !== '' && array_key_exists($pName, $clickQS);

        if ($operator === 'param_exists') {
            return $paramExists;
        }

        if ($operator === 'param_not_exists') {
            return !$paramExists;
        }

        if (!$paramExists) {
            return $operator === 'param_not_in';
        }

        $pValues = is_array($val) ? (string) ($val[1] ?? '') : '';
        return $this->operator($pValues, $operator, (string) $clickQS[$pName]);
    }

    public function click_matches_filters(array $filters, ?callable $runtimeFilterResolver = null): bool
    {
        try {
            DebugMethods::start("YWBCoreCheck");
            $this->matched_filters = [];
            $this->block_reason = '';
            $this->runtimeFilterResolver = $runtimeFilterResolver === null
                ? null
                : Closure::fromCallable($runtimeFilterResolver);

            if (
                empty($filters) ||
                !array_key_exists('rules', $filters) ||
                !is_array($filters['rules']) ||
                empty($filters['rules'])
            ) {
                $this->block_reason = 'no-filters';
                return true;
            }
            
            $allShouldMatch = $filters['condition'] === 'AND';
            $result = $this->match_filters($allShouldMatch, $filters['rules']);
            $this->block_reason = implode(', ', array_unique($this->matched_filters));
            return $result;
        } finally {
            $this->runtimeFilterResolver = null;
            DebugMethods::stop("YWBCoreCheck");
        }
    }

    private function is_proxy_or_vpn($ip): bool
    {
        return ProxyVpnDetector::isProxyOrVpn((string)$ip);
    }

    private function is_ip_in_base($ip, $baseFileName): bool
    {
        $base_full_path = __DIR__ . "/bases/" . $baseFileName;
        if (!file_exists($base_full_path)) {
            return false;
        }
        $cidr = file($base_full_path, FILE_IGNORE_NEW_LINES);
        return IpUtils::checkIp($ip, $cidr);
    }
}
