<?php

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/experiments.php';

$clickid = (string)($_GET['clickid'] ?? '');
$stepRaw = (string)($_GET['step'] ?? '');

if ($clickid === '' || $stepRaw === '' || !preg_match('/^\d+$/', $stepRaw)) {
    die('INVALID NEXT CONTEXT!');
}

$currentStep = (int)$stepRaw;

global $db;
$click = $db->get_click_by_clickid($clickid);
if (empty($click)) {
    die('CLICK NOT FOUND!');
}

set_clickid($clickid);

$campId = (int)$click['campaign_id'];
$settings = $db->get_campaign_settings($campId);
$c = new Campaign($campId, $settings);

$flowName = (string)($click['flow'] ?? '');
$flow = null;
foreach ($c->black->flows as $f) {
    if ($f->name === $flowName) {
        $flow = $f;
        break;
    }
}
if ($flow === null) {
    die('FLOW NOT FOUND!');
}

$steps = $flow->steps;
$maxReachedStep = (int)($click['step'] ?? 0);
if ($currentStep > $maxReachedStep) {
    die('INVALID STEP CONTEXT!');
}

$nextStep = $currentStep + 1;
if ($nextStep >= count($steps)) {
    die('NO MORE STEPS IN FUNNEL!');
}

$stepSettings = $steps[$nextStep];
$validItems = $stepSettings->getItems();

if ($maxReachedStep >= $nextStep) {
    $chosenVariant = $db->get_click_step_variant($clickid, $nextStep);
    if ($chosenVariant === null) {
        die('RECORDED STEP NOT FOUND!');
    }
} else {
    $plannedPath = $click['path'] ?? [];
    if (!is_array($plannedPath) || empty($plannedPath)) {
        die('EMPTY PLANNED PATH!');
    }
    if (!isset($plannedPath[$nextStep])) {
        die('NO VARIANT PLANNED FOR STEP ' . $nextStep);
    }

    $chosenVariant = $plannedPath[$nextStep];
    if (!in_array($chosenVariant, $validItems, true)) {
        die('PLANNED VARIANT NO LONGER AVAILABLE FOR STEP ' . $nextStep);
    }
    $mvtAssignment = $stepSettings->isFolder()
        ? select_mvt_assignment($c, $flow, $nextStep, $chosenVariant)
        : [];
    if (!$db->add_click_step($clickid, $nextStep, $chosenVariant, $mvtAssignment)) {
        die('FAILED TO RECORD STEP ENTRY!');
    }
    // If two requests advanced the same click concurrently, the row that
    // actually won is authoritative for both rendered HTML and event identity.
    $chosenVariant = $db->get_click_step_variant($clickid, $nextStep);
    if ($chosenVariant === null) {
        die('RECORDED STEP NOT FOUND AFTER INSERT!');
    }
}

if (!in_array($chosenVariant, $validItems, true)) {
    die('RECORDED VARIANT NO LONGER AVAILABLE FOR STEP ' . $nextStep);
}

if ($stepSettings->isRedirect()) {
    $url = $stepSettings->getRedirectUrlByLabel($chosenVariant);
    $mp = new MacrosProcessor($c, null, $clickid, $click['userid'] ?? null);
    $url = $mp->replace_url_macros($url);
    redirect($url, $stepSettings->redirectType, false);
    return;
}

if ($stepSettings->isDirectLoad($chosenVariant)) {
    redirect(get_directload_step_url($clickid, $nextStep), 302, false);
    return;
}

echo load_step($c, $flow, $nextStep, $chosenVariant, $clickid, false);
