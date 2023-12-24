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
$black_land_log_conversions_on_button_click = $conf['black.landing.folder.conversions.logonbuttonclick'];
$black_land_use_custom_thankyou_page = $conf['black.landing.folder.customthankyoupage.use'];
if ($black_land_use_custom_thankyou_page) $black_land_log_conversions_on_button_click = false;
$black_land_thankyou_page_language = $conf['black.landing.folder.customthankyoupage.language'];

$thankyou_upsell = $conf['black.landing.folder.customthankyoupage.upsell.use'];
$thankyou_upsell_imgdir = $conf['black.landing.folder.customthankyoupage.upsell.imgdir'];
$thankyou_upsell_header = $conf['black.landing.folder.customthankyoupage.upsell.header'];
$thankyou_upsell_text = $conf['black.landing.folder.customthankyoupage.upsell.text'];
$thankyou_upsell_url = $conf['black.landing.folder.customthankyoupage.upsell.url'];

$black_jsconnect_action = $conf['black.jsconnect'];

$ya_id = $conf['pixels.ya.id'];
$gtm_id = $conf['pixels.gtm.id'];
$fbpixel_subname = $conf['pixels.fb.subname'];
$fb_use_pageview = $conf['pixels.fb.pageview'];
$fb_use_viewcontent = $conf['pixels.fb.viewcontent.use'];
$fb_view_content_time = $conf['pixels.fb.viewcontent.time'];
$fb_view_content_percent = $conf['pixels.fb.viewcontent.percent'];
$fb_thankyou_event = $conf['pixels.fb.conversion.event'];
$fb_add_button_pixel = $conf['pixels.fb.conversion.fireonbutton'];
$ttpixel_subname = $conf['pixels.tt.subname'];
$tt_use_pageview = $conf['pixels.tt.pageview'];
$tt_use_viewcontent = $conf['pixels.tt.viewcontent.use'];
$tt_view_content_time = $conf['pixels.tt.viewcontent.time'];
$tt_view_content_percent = $conf['pixels.tt.viewcontent.percent'];
$tt_thankyou_event = $conf['pixels.tt.conversion.event'];
$tt_add_button_pixel = $conf['pixels.tt.conversion.fireonbutton'];

$tds_mode = $conf['tds.mode'];
$save_user_flow = $conf['tds.saveuserflow'];

$os_white = $conf['tds.filters.allowed.os'];
$country_white = $conf['tds.filters.allowed.countries'];
$url_should_contain = $conf['tds.filters.allowed.inurl'];
$lang_white = $conf['tds.filters.allowed.languages'];

$ip_black_filename = $conf->get('tds.filters.blocked.ips.filename', '');
$ip_black_cidr = $conf->get('tds.filters.blocked.ips.cidrformat', false);
$tokens_black = $conf['tds.filters.blocked.tokens'];
$ua_black = $conf['tds.filters.blocked.useragents'];
$isp_black = $conf['tds.filters.blocked.isps'];
$block_without_referer = $conf['tds.filters.blocked.referer.empty'];
$referer_stopwords = $conf['tds.filters.blocked.referer.stopwords'];
$block_vpnandtor = $conf['tds.filters.blocked.vpntor'];

$back_button_action = $conf['scripts.back.action'];
$replace_back_address = $conf['scripts.back.value'];
$disable_text_copy = $conf['scripts.disabletextcopy'];
$replace_prelanding = $conf['scripts.prelandingreplace.use'];
$replace_prelanding_address = $conf['scripts.prelandingreplace.url'];
$replace_landing = $conf['scripts.landingreplace.use'];
$replace_landing_address = $conf['scripts.landingreplace.url'];
$black_land_use_phone_mask = $conf['scripts.phonemask.use'];
$black_land_phone_mask = $conf['scripts.phonemask.mask'];
$comebacker = $conf['scripts.comebacker'];
$callbacker = $conf['scripts.callbacker'];
$addedtocart = $conf['scripts.addedtocart'];
$images_lazy_load = $conf['scripts.imageslazyload'];

$sub_ids = $conf['subids'];

$log_password = strval($conf['statistics.password']);
$stats_timezone = $conf->get('statistics.timezone', 'Europe/Moscow');
$stats_sub_names = $conf['statistics.subnames'];

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
 * @throws EmptyDirectoryException
 */
function get_config_name_by_domain($domain): string
{
    if (strpos($domain,":")!==false)
        $domain = explode(":",$domain)[0];
    $conf = Config::load(__DIR__ . '/settings.json');
    foreach ($conf as $c => $v) {
        $conf->setNamespace($c);
        $domains = $conf->get("domains");
        if (in_array($domain, $domains)) return $c; //TODO: make pattern matching for subdomains
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

function save_config($name)
{
    try {
        $conf = Config::load(__DIR__ . '/settings.json');
        $conf->setNamespace($name);
        foreach ($_POST as $key => $value) {
            $confkey = str_replace('_', '.', $key);
            if (is_string($value) && is_array($conf[$confkey])) {
                $value = $value === '' ? [] : explode(',', $value);
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