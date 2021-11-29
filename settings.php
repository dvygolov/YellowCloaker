<?php
use Noodlehaus\Config;
require_once 'config/ConfigInterface.php';
require_once 'config/AbstractConfig.php';
require_once 'config/Config.php';
require_once 'config/Parser/ParserInterface.php';
require_once 'config/Parser/Json.php';
require_once 'config/ErrorException.php';
require_once 'config/Exception.php';
require_once 'config/Exception/ParseException.php';
require_once 'config/Exception/FileNotFoundException.php';

$conf = Config::load(__DIR__.'/settings.json');

$white_action = $conf->get('white.action','folder');
$white_folder_names = $conf->get('white.folder.names',['white']);
$white_redirect_urls = $conf->get('white.redirect.urls',[]);
$white_redirect_type = $conf->get('white.redirect.type',302);
$white_curl_urls = $conf->get('white.curl.urls',[]);
$white_error_codes = $conf->get('white.error.codes',[404]);
$white_use_domain_specific=$conf->get('white.domainfilter.use',false);
$white_domain_specific= $conf['white.domainfilter.domains'];

$use_js_checks = $conf['white.jschecks.enabled'];
$js_checks = $conf['white.jschecks.events'];
$js_timeout =$conf['white.jschecks.timeout'];
$js_obfuscate = $conf['white.jschecks.obfuscate'];
$js_tzstart = $conf['white.jschecks.tzstart'];
$js_tzend = $conf['white.jschecks.tzend'];

$black_preland_action = $conf['black.prelanding.action'];
$black_preland_folder_names = $conf['black.prelanding.folders'];

$black_land_action = $conf['black.landing.action'];
$black_land_folder_names = $conf['black.landing.folder.names'];
$black_land_redirect_urls = $conf['black.landing.redirect.urls'];
$black_land_redirect_type = $conf['black.landing.redirect.type'];
$black_land_conversion_script = $conf['black.landing.folder.conversions.script'];
$black_land_log_conversions_on_button_click = $conf['black.landing.folder.conversions.logonbuttonclick'];
$black_land_use_custom_thankyou_page = $conf['black.landing.folder.customthankyoupage.use'];
if ($black_land_use_custom_thankyou_page) $black_land_log_conversions_on_button_click=false;
$black_land_thankyou_page_language = $conf['black.landing.folder.customthankyoupage.language'];

$thankyou_upsell=$conf['black.landing.folder.customthankyoupage.upsell.use'];
$thankyou_upsell_imgdir = $conf['black.landing.folder.customthankyoupage.upsell.imgdir'];
$thankyou_upsell_header = $conf['black.landing.folder.customthankyoupage.upsell.header'];
$thankyou_upsell_text = $conf['black.landing.folder.customthankyoupage.upsell.text'];
$thankyou_upsell_url = $conf['black.landing.folder.customthankyoupage.upsell.url'];

$black_jsconnect_action=$conf['black.jsconnect'];

$ya_id= $conf['pixels.ya.id'];
$gtm_id= $conf['pixels.gtm.id'];
$fbpixel_subname = $conf['pixels.fb.subname'];
$fb_use_pageview = $conf['pixels.fb.pageview'];
$fb_use_viewcontent = $conf['pixels.fb.viewcontent.use'];
$fb_view_content_time = $conf['pixels.fb.viewcontent.time'];
$fb_view_content_percent = $conf['pixels.fb.viewcontent.percent'];
$fb_thankyou_event = $conf['pixels.fb.conversion.event'];
$fb_add_button_pixel= $conf['pixels.fb.conversion.fireonbutton'];
$ttpixel_subname = $conf['pixels.tt.subname'];
$tt_use_pageview = $conf['pixels.tt.pageview'];
$tt_use_viewcontent = $conf['pixels.tt.viewcontent.use'];
$tt_view_content_time = $conf['pixels.tt.viewcontent.time'];
$tt_view_content_percent = $conf['pixels.tt.viewcontent.percent'];
$tt_thankyou_event = $conf['pixels.tt.conversion.event'];
$tt_add_button_pixel= $conf['pixels.tt.conversion.fireonbutton'];

$tds_mode = $conf['tds.mode'];
$save_user_flow = $conf['tds.saveuserflow'];

$os_white = $conf['tds.filters.allowed.os'];
$country_white = $conf['tds.filters.allowed.countries'];
$url_should_contain =$conf['tds.filters.allowed.inurl'];
$lang_white = $conf['tds.filters.allowed.languages'];

$ip_black_filename = $conf->get('tds.filters.blocked.ips.filename','');
$ip_black_cidr = $conf->get('tds.filters.blocked.ips.cidrformat',false);
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
$stats_timezone = $conf->get('statistics.timezone','Europe/Moscow');
$stats_sub_names = $conf['statistics.subnames'];

$s2s_postbacks = $conf['postback.s2s'];

$lead_status_name = $conf['postback.lead'];
$purchase_status_name = $conf['postback.purchase'];
$reject_status_name = $conf['postback.reject'];
$trash_status_name = $conf['postback.trash'];

?>