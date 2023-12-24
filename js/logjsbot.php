<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../core.php';
require_once __DIR__ . '/../db.php';

$fs = new FilterSettings(
    $os_white, $country_white, $lang_white, $tokens_black,
    $url_should_contain, $ua_black, $ip_black_filename,
    $ip_black_cidr, $block_without_referer, $referer_stopwords,
    $block_vpnandtor, $isp_black);
$cloaker = new Cloaker($fs);
//Добавляем, по какому из js-событий мы поймали бота
$reason = $_GET['reason'] ?? 'js_tests';
$cloaker->block_reason[] = $reason;
$db = new Db();
$db->add_white_click($cloaker->click_params, $cloaker->block_reason, $cur_config);