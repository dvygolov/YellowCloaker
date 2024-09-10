<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/core.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/main.php';

$db = new Db();
$dbCamp = $db->get_campaign_by_domain($_SERVER['HTTP_HOST']);
if ($dbCamp===null)
    die("NO CAMPAIGN FOR THIS DOMAIN!");
//TODO create a trafficback campaign option

$c = new Campaign($dbCamp['id'],$dbCamp['settings']);
$cloaker = new Cloaker($c->filters);

if ($c->white->jsChecks->enabled) {
    white(true);
} else if ($cloaker->is_bad_click()) { 
    $db->add_white_click($cloaker->click_params, $cloaker->block_reason, $c->campaignId);
    white(false);
} else
    black($cloaker->click_params);