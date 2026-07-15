<?php

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';
require_once __DIR__ . '/requestfunc.php';
require_once __DIR__ . '/actions.php';

function traficback(array $clickParams): TdsAction
{
    global $db;
    $db->add_trafficback_click($clickParams);
    $cs = $db->get_common_settings();
    $mp = new MacrosProcessor(null, $clickParams);
    $tbUrl = $mp->replace_url_macros($cs['trafficBackUrl']);

    return empty($tbUrl) ?
        new TdsAction('traficback', 'die', 'NO CAMPAIGN FOR THIS DOMAIN AND TRAFFICBACK NOT SET!') :
        new TdsAction('traficback', 'redirect', $tbUrl);
}

function jscheck(Campaign $c): TdsAction
{
    $page = load_content_with_include('js/jscheck.html');

    $detectJs = file_get_contents(__DIR__ . '/js/detect.js');
    $detectJs = str_replace('{DEBUG}', DebugMethods::on() ? 'true' : 'false', $detectJs);
    $detectJs = str_replace('{DOMAIN}', get_tds_path(), $detectJs);

    $jbd = $c->black->jsBotDetection;
    $js_checks_str = implode('", "', $jbd->events);
    $detectJs = str_replace('{JSCHECKS}', $js_checks_str, $detectJs);
    $detectJs = str_replace('{JSTIMEOUT}', $jbd->timeout, $detectJs);
    $detectJs = str_replace('{JSTZMIN}', $jbd->tzMin, $detectJs);
    $detectJs = str_replace('{JSTZMAX}', $jbd->tzMax, $detectJs);

    if (!DebugMethods::on()) {
        $hunter = new HunterObfuscator($detectJs);
        $detectJs = $hunter->Obfuscate();
    }
    $needle = '<body>';
    $page = insert_after_tag($page, $needle, "<script>{$detectJs}</script>");

    $jscheckui = file_get_contents(__DIR__ . '/js/jscheckui.js');
    if (!DebugMethods::on()) {
        $hunter = new HunterObfuscator($jscheckui);
        $jscheckui = $hunter->Obfuscate();
    }
    $page = insert_after_tag($page, $needle, "<script>{$jscheckui}</script>");

    session_write('jscheck_pending', time());
    return new TdsAction('jscheck', 'html', $page);
}

function white(Campaign $c): TdsAction
{
    $ws = $c->white;
    $action = $ws->action;
    $error_codes = $ws->errorCodes;
    $folder_names = $ws->folderNames;
    $curl_urls = $ws->curlUrls;
    $redirect_urls = $ws->redirectUrls;
    $redirect_type = $ws->redirectType;
    /** @var WhiteSettings|DomainWhiteSettings $loadModeSource */
    $loadModeSource = $ws;

    if ($ws->domainFilterEnabled) {
        $httpHost = $_SERVER['HTTP_HOST'] ?? '';
        $curdomain = $httpHost;
        if (str_ends_with($curdomain, ':' . $_SERVER['SERVER_PORT'])) {
            $curdomain = substr($curdomain, 0, -strlen(':' . $_SERVER['SERVER_PORT']));
        }
        foreach ($ws->domainSpecific as $dws) {
            if ($dws->domain !== $httpHost && $dws->domain !== $curdomain) {
                continue;
            }
            $action = $dws->action;
            $error_codes = $dws->errorCodes;
            $folder_names = $dws->folderNames;
            $curl_urls = $dws->curlUrls;
            $redirect_urls = $dws->redirectUrls;
            $redirect_type = $dws->redirectType;
            $loadModeSource = $dws;
            break;
        }
    }

    $abtest = new AbTest($c);
    switch ($action) {
        case 'error':
            $curcode = $abtest->select_item($error_codes, 'white', false);
            session_write('white', $curcode[0]);
            return new TdsAction('white', 'error', $curcode[0]);
        case 'folder':
            $curfolder = $abtest->select_item($folder_names, 'white', true);
            session_write('white', $curfolder[0]);
            return new TdsAction('white', 'html', load_white_content($curfolder[0], $loadModeSource->getLoadMode($curfolder[0])));
        case 'curl':
            $cururl = $abtest->select_item($curl_urls, 'white', false);
            session_write('white', $cururl[0]);
            return new TdsAction('white', 'html', load_white_curl($cururl[0], $loadModeSource->getLoadMode($cururl[0])));
        case 'redirect':
            $cururl = $abtest->select_item($redirect_urls, 'white', false);
            session_write('white', $cururl[0]);
            return new TdsAction('white', 'redirect', $cururl[0], $redirect_type);
        default:
            return new TdsAction('white', 'error', 404);
    }
}

function black(Campaign $c, int $flowIndex, array $clickparams): TdsAction
{
    global $db;

    $userid = set_userid();
    $clickid = generate_clickid($userid);
    set_clickid($clickid);

    $flow = $c->black->flows[$flowIndex];
    $steps = $flow->steps;

    if (empty($steps)) {
        return new TdsAction('black', 'die', "No steps defined in flow: " . $flow->name);
    }

    $abtest = new AbTest($c);
    $isThompson = $flow->distribution === 'thompson';

    $plannedPath = [];
    if ($c->saveUserFlow) {
        $plannedPath = get_saved_flow_path($c->campaignId, $flow->name, $steps);
    }

    if (empty($plannedPath)) {
        // Select variant for each step -> build planned path
        if ($isThompson && $flow->optimize_mode === 'funnels') {
            $allStepItems = [];
            foreach ($steps as $step) {
                $allStepItems[] = $step->getItems();
            }
            $plannedPath = $abtest->select_thompson_funnel_multi($allStepItems, $flow->name, $flow->optimize_for);
        } else {
            foreach ($steps as $si => $step) {
                $items = $step->getItems();
                if (empty($items)) {
                    add_error_log("No items found for step $si in flow {$flow->name}, campaign {$c->campaignId}!", false, true);
                }

                if ($isThompson) {
                    $chosen = $abtest->select_thompson_variant($items, $si, $flow->name, $flow->optimize_for);
                } else {
                    $isFolder = $step->isFolder();
                    $res = $abtest->select_distributed($items, "step_$si", $isFolder, $flow->distribution, $step->weights);
                    $chosen = $res[0];
                }
                $plannedPath[] = $chosen;
            }
        }
    }

    if (!is_valid_planned_path($plannedPath, $steps)) {
        return new TdsAction('black', 'die', "Invalid planned path for flow: " . $flow->name);
    }

    if ($c->saveUserFlow) {
        save_flow_path($c->campaignId, $flow->name, $plannedPath);
    }

    // Record one click per full pass and first entered step.
    if (!$db->add_black_click($userid, $clickid, $clickparams, $plannedPath, $flow->name, $c->campaignId)) {
        return new TdsAction('black', 'die', 'Failed to record click');
    }
    if (!$db->add_click_step($clickid, 0, $plannedPath[0])) {
        return new TdsAction('black', 'die', 'Failed to record step entry');
    }

    // Serve step 0 content
    $step0 = $steps[0];
    $chosenVariant = $plannedPath[0];

    if ($step0->isRedirect()) {
        $url = $step0->getRedirectUrlByLabel($chosenVariant);
        $mp = new MacrosProcessor($c, $clickparams);
        $url = $mp->replace_url_macros($url);
        return new TdsAction('black', 'redirect', $url, $step0->redirectType);
    }

    if ($step0->isDirectLoad($chosenVariant)) {
        $dlUrl = get_directload_step_url($clickid, 0);
        return new TdsAction('black', 'redirect', $dlUrl, 302);
    }

    $html = load_step($c, $flow, 0, $chosenVariant, $clickid, false);
    return new TdsAction('black', 'html', $html);
}

function get_saved_flow_path(int $campId, string $flowName, array $steps): array
{
    $state = get_saved_paths_state();
    $saved = $state[(string)$campId][$flowName] ?? [];
    if (!is_array($saved) || !is_valid_planned_path($saved, $steps)) {
        return [];
    }
    return array_values($saved);
}

function save_flow_path(int $campId, string $flowName, array $path): void
{
    $state = get_saved_paths_state();
    $campKey = (string)$campId;
    if (!isset($state[$campKey]) || !is_array($state[$campKey])) {
        $state[$campKey] = [];
    }
    $state[$campKey][$flowName] = array_values($path);
    $json = json_encode($state);
    if ($json !== false) {
        set_cookie('saved_paths', base64_encode($json));
    }
}

function get_saved_paths_state(): array
{
    $raw = get_cookie('saved_paths');
    if (empty($raw)) {
        return [];
    }
    $decodedRaw = base64_decode($raw, true);
    if ($decodedRaw === false) {
        $decodedRaw = $raw;
    }
    $decoded = json_decode($decodedRaw, true);
    return is_array($decoded) ? $decoded : [];
}

function is_valid_planned_path(array $path, array $steps): bool
{
    if (count($path) !== count($steps)) {
        return false;
    }

    foreach ($steps as $idx => $step) {
        $variant = $path[$idx] ?? null;
        if (!is_string($variant) || $variant === '') {
            return false;
        }

        $items = $step->getItems();
        if (!in_array($variant, $items, true)) {
            return false;
        }
    }

    return true;
}
