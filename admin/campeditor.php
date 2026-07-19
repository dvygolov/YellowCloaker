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
        if (isset($input['black']['flows']) && is_array($input['black']['flows'])) {
            foreach ($input['black']['flows'] as &$flow) {
                foreach (($flow['steps'] ?? []) as &$step) {
                    normalize_step_weights($step);
                }
                unset($step);
            }
            unset($flow);
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
        $saveRes = $db->save_campaign_settings($campId, $s);
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

function normalize_step_weights(array &$step): void {
    $weights = $step['weights'] ?? [];
    if (empty($weights)) return;
    $total = array_sum($weights);
    $count = count($weights);
    if ($total <= 0) {
        $base = intdiv(100, $count);
        $remainder = 100 - $base * $count;
        $result = array_fill(0, $count, $base);
        for ($i = 0; $i < $remainder; $i++) $result[$i]++;
        $step['weights'] = $result;
        return;
    }
    if ($total === 100) return;
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
    $step['weights'] = array_map('intval', $floored);
}

function mergeSettingsRecursive($current, $incoming) {
    if (!is_array($incoming)) {
        if ($incoming === 'false' || $incoming === 'true') {
            return filter_var($incoming, FILTER_VALIDATE_BOOLEAN);
        }
        return $incoming;
    }

    if (!is_array($current) || array_is_list($incoming)) {
        return compactListRecursive($incoming);
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
