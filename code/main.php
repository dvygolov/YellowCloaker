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
require_once __DIR__ . '/conversion.php';
require_once __DIR__ . '/experiments.php';

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

    $userid = '';
    $clickid = generate_clickid();
    set_clickid($clickid);

    $flow = $c->black->flows[$flowIndex];
    $steps = $flow->steps;

    if (empty($steps)) {
        return new TdsAction('black', 'die', "No steps defined in flow: " . $flow->name);
    }

    $plannedPath = build_black_path($c, $flow);

    if (!is_valid_planned_path($plannedPath, $steps)) {
        return new TdsAction('black', 'die', "Invalid planned path for flow: " . $flow->name);
    }

    experiment_save_flow_path(
        $c->campaignId,
        $flow->name,
        $plannedPath,
        $c->saveUserFlow
    );
    $step0Mvt = $steps[0]->isFolder()
        ? select_mvt_assignment($c, $flow, 0, $plannedPath[0])
        : [];

    // Record one click per full pass and first entered step.
    if (!$db->add_black_click($userid, $clickid, $clickparams, $plannedPath, $flow->name, $c->campaignId)) {
        return new TdsAction('black', 'die', 'Failed to record click');
    }
    if (!$db->add_click_step($clickid, 0, $plannedPath[0], $step0Mvt)) {
        return new TdsAction('black', 'die', 'Failed to record step entry');
    }

    return render_black_action($c, $flow, $clickparams, $plannedPath, $clickid, $userid);
}

final class NoMatchingFlowException extends RuntimeException
{
}

function black_unique(Campaign $c, FiltrationCore $clkr): ?TdsAction
{
    global $db;

    $settings = $c->uniqueness;
    $incomingUserid = get_userid();
    $identity = UniquenessService::prepareIdentity($settings, $clkr->click_params, $incomingUserid);
    $clickid = generate_clickid();
    $preparedClick = $db->prepare_black_click_for_transaction($clkr->click_params, $c->campaignId);
    $stage = 'begin';
    $flowName = '';
    $filtersNeedUniqueness = flows_use_uniqueness_filter($c->black->flows);
    $preselectedFlowIndex = null;
    $preselectedPath = null;

    try {
        if (!$filtersNeedUniqueness) {
            $stage = 'flow_selection';
            foreach ($c->black->flows as $index => $candidate) {
                $resolver = static function (string $kind, array $filter = []) use ($db, $c, $candidate): bool {
                    return ConversionCapEvaluator::matches($db, $c, $candidate, $kind, $filter);
                };
                if ($clkr->click_matches_filters($candidate->filters, $resolver)) {
                    $preselectedFlowIndex = $index;
                    break;
                }
            }
            if ($preselectedFlowIndex === null) {
                throw new NoMatchingFlowException('No matching flow');
            }

            $preselectedFlow = $c->black->flows[$preselectedFlowIndex];
            $flowName = $preselectedFlow->name;
            $stage = 'path';
            $preselectedPath = build_black_path($c, $preselectedFlow);
            if (!is_valid_planned_path($preselectedPath, $preselectedFlow->steps)) {
                throw new RuntimeException('Invalid planned path for flow: ' . $preselectedFlow->name);
            }
        }

        $result = $db->immediate_transaction(function (SQLite3 $connection) use (
            $c,
            $clkr,
            $settings,
            $identity,
            $clickid,
            $preparedClick,
            $filtersNeedUniqueness,
            $preselectedFlowIndex,
            $preselectedPath,
            &$stage,
            &$flowName,
            $db
        ): array {
            $now = time();
            $ttlCutoff = $now - $settings->ttlHours * 3600;

            $selectedFlowIndex = $preselectedFlowIndex;
            $plannedPath = $preselectedPath;
            $flowUniqueByName = [];
            $campaignUnique = null;

            if ($filtersNeedUniqueness) {
                $stage = 'campaign_unique';
                $campaignUnique = UniquenessService::isUnique(
                    $connection,
                    $c->campaignId,
                    null,
                    $identity,
                    $ttlCutoff
                );

                $stage = 'flow_selection';
                foreach ($c->black->flows as $index => $candidate) {
                    $resolver = function (string $scope, array $filter = []) use (
                        $campaignUnique,
                        $connection,
                        $c,
                        $candidate,
                        $db,
                        $identity,
                        $ttlCutoff,
                        &$flowUniqueByName
                    ): bool {
                        if (in_array($scope, ['conversion_cap_campaign', 'conversion_cap_flow'], true)) {
                            return ConversionCapEvaluator::matches($db, $c, $candidate, $scope, $filter);
                        }
                        if ($scope === 'campaign') {
                            return $campaignUnique;
                        }
                        if (!array_key_exists($candidate->name, $flowUniqueByName)) {
                            $flowUniqueByName[$candidate->name] = UniquenessService::isUnique(
                                $connection,
                                $c->campaignId,
                                $candidate->name,
                                $identity,
                                $ttlCutoff
                            );
                        }
                        return $flowUniqueByName[$candidate->name];
                    };

                    if ($clkr->click_matches_filters($candidate->filters, $resolver)) {
                        $selectedFlowIndex = $index;
                        break;
                    }
                }
            }

            if ($selectedFlowIndex === null) {
                throw new NoMatchingFlowException('No matching flow');
            }

            $flow = $c->black->flows[$selectedFlowIndex];
            $flowName = $flow->name;
            $stage = 'flow_unique';
            if (!$filtersNeedUniqueness) {
                [$campaignUnique, $flowUniqueByName[$flow->name]] = UniquenessService::bothScopesAreUnique(
                    $connection,
                    $c->campaignId,
                    $flow->name,
                    $identity,
                    $ttlCutoff
                );
            } elseif (!array_key_exists($flow->name, $flowUniqueByName)) {
                $flowUniqueByName[$flow->name] = UniquenessService::isUnique(
                    $connection,
                    $c->campaignId,
                    $flow->name,
                    $identity,
                    $ttlCutoff
                );
            }
            $flowUnique = $flowUniqueByName[$flow->name];

            if ($filtersNeedUniqueness) {
                $stage = 'path';
                $plannedPath = build_black_path($c, $flow);
                if (!is_valid_planned_path($plannedPath, $flow->steps)) {
                    throw new RuntimeException('Invalid planned path for flow: ' . $flow->name);
                }
            }

            $uniqueFlags = ($campaignUnique ? 2 : 0) | ($flowUnique ? 1 : 0);

            $stage = 'click_insert';
            $db->insert_black_click_in_transaction(
                $connection,
                $identity->userid,
                $clickid,
                $preparedClick,
                $plannedPath,
                $flow->name,
                $c->campaignId,
                $identity->hash,
                $uniqueFlags,
                $now
            );

            $stage = 'click_step_insert';
            $step0Mvt = $flow->steps[0]->isFolder()
                ? select_mvt_assignment($c, $flow, 0, $plannedPath[0])
                : [];
            $db->insert_click_step_in_transaction(
                $connection,
                $clickid,
                0,
                $plannedPath[0],
                $now,
                $step0Mvt
            );

            return [
                'flow_index' => $selectedFlowIndex,
                'path' => $plannedPath,
                'clickid' => $clickid,
            ];
        });
    } catch (NoMatchingFlowException) {
        return null;
    } catch (Throwable $e) {
        $query = is_array($clkr->click_params['qs'] ?? null) ? $clkr->click_params['qs'] : [];
        $getName = $settings->getParameter;
        $rawGet = array_key_exists($getName, $query) ? $query[$getName] : '[missing]';
        ytds_log('error', 'uniqueness', 'Failed to record click with uniqueness', [
            'stage' => $stage,
            'campaign' => $c->campaignId,
            'flow' => $flowName,
            'method' => $settings->method,
            'sqlite_code' => $db->last_write_error_code(),
            'sqlite_message' => $db->last_write_error_message(),
            'reason' => $e->getMessage(),
            'ip' => (string)($clkr->click_params['ip'] ?? ''),
            'ua' => (string)($clkr->click_params['ua'] ?? ''),
            'userid' => $incomingUserid,
            'get_parameter' => $getName,
            'get_value' => $rawGet,
        ]);
        return new TdsAction('black', 'error', '500');
    }

    $flow = $c->black->flows[$result['flow_index']];
    $clickid = $result['clickid'];
    $plannedPath = $result['path'];
    set_clickid($clickid);
    if ($settings->usesCookie()) {
        set_userid_cookie($identity->userid);
    }
    experiment_save_flow_path(
        $c->campaignId,
        $flow->name,
        $plannedPath,
        $c->saveUserFlow
    );

    return render_black_action(
        $c,
        $flow,
        $clkr->click_params,
        $plannedPath,
        $clickid,
        $identity->userid
    );
}

function flows_use_uniqueness_filter(array $flows): bool
{
    foreach ($flows as $flow) {
        if (rules_use_uniqueness_filter($flow->filters ?? [])) {
            return true;
        }
    }
    return false;
}

function rules_use_uniqueness_filter(mixed $node): bool
{
    if (!is_array($node)) {
        return false;
    }
    if (($node['id'] ?? null) === 'uniqueness' || ($node['field'] ?? null) === 'uniqueness') {
        return true;
    }
    foreach ($node as $value) {
        if (is_array($value) && rules_use_uniqueness_filter($value)) {
            return true;
        }
    }
    return false;
}

function build_black_path(Campaign $c, FlowSettings $flow): array
{
    $steps = $flow->steps;
    if (empty($steps)) {
        return [];
    }

    $abtest = new AbTest($c);
    $isThompson = $flow->distribution === 'thompson';
    $plannedPath = experiment_session_flow_path(
        $c->campaignId,
        $flow->name,
        $steps
    );
    if ($plannedPath === [] && $c->saveUserFlow) {
        $plannedPath = experiment_sticky_flow_path(
            $c->campaignId,
            $flow->name,
            $steps
        );
    }

    if (!empty($plannedPath)) {
        return $plannedPath;
    }

    if ($isThompson && $flow->optimize_mode === 'funnels') {
        $allStepItems = [];
        foreach ($steps as $step) {
            $allStepItems[] = $step->getItems();
        }
        return $abtest->select_thompson_funnel_multi($allStepItems, $flow->name, $flow->optimize_for);
    }

    foreach ($steps as $stepIndex => $step) {
        $items = $step->getItems();
        if (empty($items)) {
            return [];
        }

        if ($isThompson) {
            $chosen = $abtest->select_thompson_variant(
                $items,
                $stepIndex,
                $flow->name,
                $flow->optimize_for
            );
        } else {
            $result = $abtest->select_distributed(
                $items,
                "step_$stepIndex",
                $step->isFolder(),
                $flow->distribution,
                $step->getWeights()
            );
            $chosen = $result[0];
        }
        $plannedPath[] = $chosen;
    }
    return $plannedPath;
}

function render_black_action(
    Campaign $c,
    FlowSettings $flow,
    array $clickparams,
    array $plannedPath,
    string $clickid,
    string $userid
): TdsAction {
    $steps = $flow->steps;
    $step0 = $steps[0];
    $chosenVariant = $plannedPath[0];

    if ($step0->isRedirect()) {
        $url = $step0->getRedirectUrlByLabel($chosenVariant);
        $mp = new MacrosProcessor($c, $clickparams, $clickid, $userid);
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
