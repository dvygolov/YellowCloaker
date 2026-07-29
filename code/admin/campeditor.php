<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../campaign.php';
require_once __DIR__ . '/../logging.php';
require_once __DIR__ . '/campaignvalidation.php';

$passOk = check_password(false);
if (!$passOk)
    return send_camp_result("Error: password check not passed!",true);

$action = $_REQUEST['action'] ?? '';
$name = $_REQUEST['name']??'';
$name = is_string($name) ? trim($name) : '';
$campId = $_REQUEST['campId']??-1;
add_log('trace','CampEditor action: '.$action.', name: '.$name.', campId: '.$campId);
switch ($action) {
    case 'add':
        $campId = $db->add_campaign($name);
        if ($campId===false)
            return send_camp_result("Error adding new campaign!",true);
        break;
    case 'dup':
        if (empty($name)) {
            return send_camp_result("Error: campaign name can not be empty!", true);
        }
        $clonedId = $db->clone_campaign($campId);
        if ($clonedId===false)
            return send_camp_result("Error duplicating campaign!",true);
        if (!empty($name)) {
            $renRes = $db->rename_campaign((int)$clonedId, $name);
            if ($renRes === false) {
                return send_camp_result("Error renaming cloned campaign!", true);
            }
        }
        break;
    case 'del':
        $delRes = $db->delete_campaign($campId);
        if ($delRes===false)
            return send_camp_result("Error deleting campaign!",true);
        break;
    case 'ren':
        $renRes = $db->rename_campaign($campId, $name);
        if ($renRes===false)
            return send_camp_result("Error renaming campaign!",true);
        break;
    case 'save':
        $s = $db->get_campaign_settings($campId);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            return send_camp_result("Error: invalid JSON body!", true);
        }
        $uniquenessError = normalize_uniqueness_input($input);
        if ($uniquenessError !== null) {
            return send_camp_result($uniquenessError, true);
        }
        $eventError = normalize_event_input($input);
        if ($eventError !== null) {
            return send_camp_result($eventError, true);
        }
        $conversionError = normalize_conversion_input($input);
        if ($conversionError !== null) {
            return send_camp_result($conversionError, true);
        }
        $postbackError = normalize_postback_input($input);
        if ($postbackError !== null) {
            return send_camp_result($postbackError, true);
        }
        $flowError = normalize_flow_input($input, $s);
        if ($flowError !== null) {
            return send_camp_result($flowError, true);
        }
        $s = mergeSettingsRecursive($s, $input);
        if (empty($s['uniqueness']['enabled'])) {
            $affectedFlows = find_uniqueness_rule_flows($s['black']['flows'] ?? []);
            if ($affectedFlows !== []) {
                return send_camp_result(
                    'Remove uniqueness rules before disabling uniqueness counting. Affected flows: '
                    . implode(', ', $affectedFlows),
                    true
                );
            }
        }
        try {
            $saveRes = $db->save_campaign_settings($campId, $s);
        } catch (CampaignDomainException $e) {
            return send_camp_result($e->getMessage(), true);
        }
        if($saveRes===false)
            return send_camp_result("Error saving campaign!",true);
        break;
    default:
        return send_camp_result("Error: wrong action!",true);
}
return send_camp_result("OK");

function send_camp_result($msg,$error=false): void
{
    $res = ["result" => $msg];
    if ($error){
        $res['error']=true;
    }
    header('Content-type: application/json');
    http_response_code(200);
    $json = json_encode($res);
    echo $json;
}

function normalize_item_weights(array &$items): void {
    if ($items === []) {
        return;
    }
    $weights = array_map(
        static fn(array $item): int => max(0, (int)($item['weight'] ?? 0)),
        $items
    );
    $total = array_sum($weights);
    $count = count($weights);
    if ($total <= 0) {
        $base = intdiv(100, $count);
        $remainder = 100 - $base * $count;
        $result = array_fill(0, $count, $base);
        for ($i = 0; $i < $remainder; $i++) $result[$i]++;
        foreach ($items as $index => &$item) {
            $item['weight'] = $result[$index];
        }
        unset($item);
        return;
    }
    if ($total === 100) {
        foreach ($items as $index => &$item) {
            $item['weight'] = $weights[$index];
        }
        unset($item);
        return;
    }
    $exact = array_map(fn($w) => $w / $total * 100, $weights);
    $floored = array_map('floor', $exact);
    $remainders = [];
    for ($i = 0; $i < $count; $i++) {
        $remainders[$i] = $exact[$i] - $floored[$i];
    }
    $diff = 100 - (int)array_sum($floored);
    arsort($remainders);
    foreach (array_keys($remainders) as $idx) {
        if ($diff <= 0) break;
        $floored[$idx]++;
        $diff--;
    }
    foreach ($items as $index => &$item) {
        $item['weight'] = (int)$floored[$index];
    }
    unset($item);
}

function normalize_flow_input(array &$input, array $current): ?string
{
    if (!array_key_exists('black', $input)) {
        return null;
    }
    if (!is_array($input['black'])) {
        return 'Black settings must be an object.';
    }
    if (!array_key_exists('flows', $input['black'])) {
        return null;
    }
    $flows = &$input['black']['flows'];
    if (!is_array($flows) || !array_is_list($flows)) {
        return 'Flows must be a list.';
    }
    $currentFlows = is_array($current['black']['flows'] ?? null)
        ? $current['black']['flows']
        : [];

    foreach ($flows as $flowIndex => &$flow) {
        if (!is_array($flow)) {
            return 'Flow #' . ($flowIndex + 1) . ' must be an object.';
        }
        $steps = &$flow['steps'];
        if (!is_array($steps) || !array_is_list($steps)) {
            return 'Flow #' . ($flowIndex + 1) . ' steps must be a list.';
        }
        foreach ($steps as $stepIndex => &$step) {
            if (!is_array($step)) {
                return 'Step #' . ($stepIndex + 1) . ' must be an object.';
            }
            $action = (string)($step['action'] ?? 'folder');
            if (!in_array($action, ['folder', 'redirect'], true)) {
                return 'Invalid action in Step #' . ($stepIndex + 1) . '.';
            }
            $step['action'] = $action;

            if ($action === 'folder') {
                $folders = &$step['folders'];
                if (!is_array($folders) || !array_is_list($folders)) {
                    return 'Folders in Step #' . ($stepIndex + 1) . ' must be a list.';
                }
                $seenNames = [];
                foreach ($folders as $folderIndex => &$folder) {
                    if (!is_array($folder)) {
                        return 'Folder #' . ($folderIndex + 1) . ' must be an object.';
                    }
                    $name = trim((string)($folder['name'] ?? ''));
                    if ($name === '') {
                        return 'Folder name cannot be empty.';
                    }
                    if (isset($seenNames[$name])) {
                        return "Folder '{$name}' cannot be added twice to the same Step.";
                    }
                    $seenNames[$name] = true;
                    $folder['name'] = $name;
                    $folder['loadtype'] = in_array(
                        (string)($folder['loadtype'] ?? 'base'),
                        ['base', 'direct'],
                        true
                    ) ? (string)$folder['loadtype'] : 'base';
                    $folder['weight'] = max(0, (int)($folder['weight'] ?? 0));

                    $currentFolder = null;
                    foreach (
                        ($currentFlows[$flowIndex]['steps'][$stepIndex]['folders'] ?? [])
                        as $candidate
                    ) {
                        if (is_array($candidate) && ($candidate['name'] ?? '') === $name) {
                            $currentFolder = $candidate;
                            break;
                        }
                    }
                    $mvtError = normalize_mvt_input(
                        $folder['mvt'],
                        is_array($currentFolder['mvt'] ?? null)
                            ? $currentFolder['mvt']
                            : [],
                        $name
                    );
                    if ($mvtError !== null) {
                        return $mvtError;
                    }
                }
                unset($folder);
                normalize_item_weights($folders);
                $step['redirect'] = [
                    'urls' => [],
                    'type' => (int)($step['redirect']['type'] ?? 302),
                ];
            } else {
                $redirect = &$step['redirect'];
                if (!is_array($redirect)) {
                    return 'Redirect settings must be an object.';
                }
                $urls = &$redirect['urls'];
                if (!is_array($urls) || !array_is_list($urls)) {
                    return 'Redirect URLs must be a list.';
                }
                foreach ($urls as $urlIndex => &$url) {
                    if (!is_array($url)) {
                        return 'Redirect #' . ($urlIndex + 1) . ' must be an object.';
                    }
                    $rawUrl = trim((string)($url['url'] ?? ''));
                    if (filter_var($rawUrl, FILTER_VALIDATE_URL) === false) {
                        return 'Redirect #' . ($urlIndex + 1) . ' must be a valid URL.';
                    }
                    $host = (string)(parse_url($rawUrl, PHP_URL_HOST) ?: 'redirect');
                    $url = [
                        'url' => $rawUrl,
                        'label' => preg_replace('/^www\./', '', $host),
                        'weight' => max(0, (int)($url['weight'] ?? 0)),
                    ];
                }
                unset($url);
                normalize_item_weights($urls);
                $redirect['type'] = in_array(
                    (int)($redirect['type'] ?? 302),
                    [301, 302, 303, 307],
                    true
                ) ? (int)$redirect['type'] : 302;
                $step['folders'] = [];
            }
        }
        unset($step);
    }
    unset($flow);
    return null;
}

function normalize_mvt_input(mixed &$raw, array $current, string $folderName): ?string
{
    if (!is_array($raw)) {
        $raw = ['enabled' => false, 'tests' => []];
    }
    $raw['enabled'] = filter_var($raw['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $tests = &$raw['tests'];
    if (!is_array($tests) || !array_is_list($tests)) {
        return "MVT tests for '{$folderName}' must be a list.";
    }
    $currentTests = is_array($current['tests'] ?? null) ? $current['tests'] : [];
    if (count($tests) < count($currentTests)) {
        return "Existing MVT tests for '{$folderName}' cannot be removed.";
    }

    foreach ($tests as $testIndex => &$test) {
        if (!is_array($test)) {
            return 'TEST' . ($testIndex + 1) . " for '{$folderName}' must be an object.";
        }
        $test['active'] = filter_var($test['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $values = &$test['values'];
        if (!is_array($values) || !array_is_list($values)) {
            return 'TEST' . ($testIndex + 1) . ' Values must be a list.';
        }
        $currentTest = is_array($currentTests[$testIndex] ?? null)
            ? $currentTests[$testIndex]
            : null;
        if ($currentTest !== null) {
            if (empty($currentTest['active']) && $test['active']) {
                return 'Archived TEST' . ($testIndex + 1) . ' cannot be restored.';
            }
            $currentValues = is_array($currentTest['values'] ?? null)
                ? $currentTest['values']
                : [];
            if (count($values) < count($currentValues)) {
                return 'Existing Values in TEST' . ($testIndex + 1) . ' cannot be removed.';
            }
        } else {
            $currentValues = [];
        }

        $activeValueCount = 0;
        foreach ($values as $valueIndex => &$value) {
            if (!is_array($value)) {
                return 'Value ' . MvtSettings::valueCode($valueIndex)
                    . ' in TEST' . ($testIndex + 1) . ' must be an object.';
            }
            $value['active'] = filter_var($value['active'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $value['value'] = (string)($value['value'] ?? '');
            if ($value['active']) {
                $activeValueCount++;
            }
            $currentValue = is_array($currentValues[$valueIndex] ?? null)
                ? $currentValues[$valueIndex]
                : null;
            if ($currentValue === null) {
                continue;
            }
            if ((string)($currentValue['value'] ?? '') !== $value['value']) {
                return 'Saved Value ' . MvtSettings::valueCode($valueIndex)
                    . ' in TEST' . ($testIndex + 1) . ' cannot be edited.';
            }
            if (empty($currentValue['active']) && $value['active']) {
                return 'Archived Value ' . MvtSettings::valueCode($valueIndex)
                    . ' in TEST' . ($testIndex + 1) . ' cannot be restored.';
            }
        }
        unset($value);
        if ($raw['enabled'] && $test['active'] && $activeValueCount === 0) {
            return 'TEST' . ($testIndex + 1) . ' must have at least one active Value.';
        }
    }
    unset($test);
    return null;
}

function mergeSettingsRecursive($current, $incoming) {
    if (!is_array($incoming)) {
        if ($incoming === 'false' || $incoming === 'true') {
            return filter_var($incoming, FILTER_VALIDATE_BOOLEAN);
        }
        return $incoming;
    }

    if (array_is_list($incoming)) {
        return compactListRecursive($incoming);
    }

    if (!is_array($current) || array_is_list($current)) {
        $current = [];
    }

    foreach ($incoming as $key => $value) {
        $current[$key] = mergeSettingsRecursive($current[$key] ?? null, $value);
    }

    return $current;
}

function compactListRecursive(array $list): array {
    $result = [];
    foreach ($list as $value) {
        if (is_array($value)) {
            $result[] = array_is_list($value)
                ? compactListRecursive($value)
                : mergeSettingsRecursive([], $value);
            continue;
        }

        if ($value === 'false' || $value === 'true') {
            $result[] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            continue;
        }

        $result[] = $value;
    }

    return $result;
}
