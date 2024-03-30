<?php

use Noodlehaus\Config;
use Noodlehaus\Exception\EmptyDirectoryException;
use Noodlehaus\Writer\Json;

require_once __DIR__ . '/config/ConfigInterface.php';
require_once __DIR__ . '/config/AbstractConfig.php';
require_once __DIR__ . '/config/Config.php';
require_once __DIR__ . '/config/Parser/ParserInterface.php';
require_once __DIR__ . '/config/Parser/Json.php';
require_once __DIR__ . '/config/Writer/AbstractWriter.php';
require_once __DIR__ . '/config/Writer/WriterInterface.php';
require_once __DIR__ . '/config/Writer/Json.php';
require_once __DIR__ . '/config/ErrorException.php';
require_once __DIR__ . '/config/Exception.php';
require_once __DIR__ . '/config/Exception/ParseException.php';
require_once __DIR__ . '/config/Exception/FileNotFoundException.php';

$conf = Config::load(__DIR__ . '/settings.json');

$cur_config = $_GET['config'] ?? get_config_name_by_domain($_SERVER['HTTP_HOST']);
$conf->setNamespace($cur_config);
$domain_names = $conf->get('domains');
$white_action = $conf->get('white.action', 'folder');
$white_folder_names = $conf->get('white.folder.names', ['white']);
$white_redirect_urls = $conf->get('white.redirect.urls', []);
$white_redirect_type = $conf->get('white.redirect.type', 302);
$white_curl_urls = $conf->get('white.curl.urls', []);
$white_error_codes = $conf->get('white.error.codes', [404]);
$white_use_domain_specific = $conf->get('white.domainfilter.use', false);
$white_domain_specific = $conf['white.domainfilter.domains'];

$use_js_checks = $conf['white.jschecks.enabled'];
$js_checks = $conf['white.jschecks.events'];
$js_timeout = $conf['white.jschecks.timeout'];
$js_obfuscate = $conf['white.jschecks.obfuscate'];
$js_tzstart = $conf['white.jschecks.tzstart'];
$js_tzend = $conf['white.jschecks.tzend'];

$black_preland_action = $conf['black.prelanding.action'];
$black_preland_folder_names = $conf['black.prelanding.folders'];

$black_land_action = $conf['black.landing.action'];
$black_land_folder_names = $conf['black.landing.folder.names'];
$black_land_redirect_urls = $conf['black.landing.redirect.urls'];
$black_land_redirect_type = $conf['black.landing.redirect.type'];

$black_land_use_custom_thankyou_page = $conf['black.landing.folder.customthankyoupage.use'];
$black_land_thankyou_page_language = $conf['black.landing.folder.customthankyoupage.language'];
$black_jsconnect_action = $conf['black.jsconnect'];
if ($black_preland_action==='none'&&$black_land_action==='redirect')
    $black_jsconnect_action = 'redirect';
else if ($black_jsconnect_action==='redirect')
    $black_jsconnect_action = 'replace';

$tds_filters = $conf['tds.filters'];
$save_user_flow = $conf['tds.saveuserflow'];
$tds_api_key = $conf['tds.apikey'];

$replace_prelanding = $conf['scripts.prelandingreplace.use'];
$replace_prelanding_address = $conf['scripts.prelandingreplace.url'];
$replace_landing = $conf['scripts.landingreplace.use'];
$replace_landing_address = $conf['scripts.landingreplace.url'];
$images_lazy_load = $conf['scripts.imageslazyload'];

$sub_ids = $conf['subids'];

$admin_password = strval($conf['statistics.password']);
$stats_timezone = $conf->get('statistics.timezone', 'Europe/Moscow');

$s2s_postbacks = $conf['postback.s2s'];
$lead_status_name = $conf['postback.lead'];
$purchase_status_name = $conf['postback.purchase'];
$reject_status_name = $conf['postback.reject'];
$trash_status_name = $conf['postback.trash'];

/**
 * @throws EmptyDirectoryException
 */
function get_all_config_names(): array
{
    $conf = Config::load(__DIR__ . '/settings.json');
    $configs = [];
    foreach ($conf as $c => $v) {
        $configs[] = $c;
    }
    return $configs;
}

/**
 * Checks if the given domain matches a pattern in the domains array.
 * Supports wildcard patterns like '*.domain.com'.
 *
 * @param string $domain The domain to check.
 * @param array $domains The array of domain patterns.
 * @return bool True if there is a match, false otherwise.
 */
function isDomainMatch($domain, $domains) {
    foreach ($domains as $pattern) {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\*', '.*', $pattern); // Replace wildcard with regex equivalent
        if (preg_match("/^$pattern$/i", $domain)) {
            return true;
        }
    }
    return false;
}

/**
 * @throws EmptyDirectoryException
 */
function get_config_name_by_domain($domain): string
{
    if (strpos($domain, ":") !== false)
        $domain = explode(":", $domain)[0];

    $conf = Config::load(__DIR__ . '/settings.json');

    foreach ($conf as $c => $v) {
        $conf->setNamespace($c);
        $domains = $conf->get("domains");
        if (isDomainMatch($domain, $domains)) {
            return $c;
        }
    }

    return "default";
}

/**
 * @throws EmptyDirectoryException
 */
function del_config($name)
{
    $conf = Config::load(__DIR__ . '/settings.json');
    if ($conf->deleteNamespace($name)) {
        $conf->toFile(__DIR__ . '/settings.json', new Json());
        return true;
    }
    return false;
}

function add_config($name)
{
    $conf = Config::load(__DIR__ . '/settings.json');
    if ($conf->addNamespace($name)) {
        $conf->toFile(__DIR__ . '/settings.json', new Json());
        return true;
    }
    return false;
}

function duplicate_config($name, $dupname)
{
    $conf = Config::load(__DIR__ . '/settings.json');
    if ($conf->duplicateNamespace($name,$dupname)) {
        $conf->toFile(__DIR__ . '/settings.json', new Json());
        return true;
    }
    return false;
}

function save_config($name)
{
    try {
        $conf = Config::load(__DIR__ . '/settings.json');
        $conf->setNamespace($name);
        foreach ($_POST as $key => $value) {
            $confkey = str_replace('_', '.', $key);
            if (is_string($value) && is_array($conf[$confkey])) {
                if (str_starts_with($value,'{') || str_starts_with($value,'[')){
                    $value = json_decode($value,true);
                }
                else if ($value === '')
                    $value =  [];
                 else 
                    $value= explode(',', $value);
            } else if ($value === 'false' || $value === 'true') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            $conf[$confkey] = $value;
        }
        $conf->toFile(__DIR__ . '/settings.json', new Json());
        return true;
    } catch (Exception $e) {
        return false;
    }
}
