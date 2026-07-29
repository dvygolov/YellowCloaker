<?php
/**
 * Build deterministic YellowTDS capacity-test data.
 *
 * This intentionally replaces all campaigns in the selected database. Run it
 * only after loadtest/remote/prepare-target.sh has captured an exact snapshot.
 *
 * Example:
 * php setup_matrix.php --confirm=YELLOWTDS_LOADTEST --domain=ywbtest.site \
 *   --campaigns=10 --campaign-match=last --flows=10 --flow-match=last \
 *   --filters=5 --condition=AND --filter-match=late --uniqueness=on \
 *   --uniqueness-scope=off --caps=campaign --cap-checks=5 \
 *   --clicks=100000 --conversions=10000 --cap-state=open
 */

require_once __DIR__ . '/../../code/db/db.php';

if (($argv[1] ?? '') === '--help' || in_array('--help', $argv, true)) {
    echo "See the file header for usage. All options are emitted in the JSON result.\n";
    exit(0);
}

$raw = getopt('', [
    'confirm:', 'domain:', 'campaigns:', 'campaign-match:', 'flows:', 'flow-match:',
    'filters:', 'condition:', 'filter-match:', 'uniqueness:', 'uniqueness-scope:',
    'caps:', 'cap-checks:', 'clicks:', 'conversions:', 'cap-state:',
    'distribution:', 'steps:', 'variants:', 'response:',
]);

if (($raw['confirm'] ?? '') !== 'YELLOWTDS_LOADTEST') {
    fwrite(STDERR, "Refusing destructive setup without --confirm=YELLOWTDS_LOADTEST\n");
    exit(2);
}

function enum_option(array $raw, string $name, array $allowed, string $default): string
{
    $value = strtolower((string)($raw[$name] ?? $default));
    if (!in_array($value, $allowed, true)) {
        throw new InvalidArgumentException("Invalid --{$name}: {$value}");
    }
    return $value;
}

function int_option(array $raw, string $name, int $default, int $min, int $max): int
{
    $value = filter_var($raw[$name] ?? $default, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new InvalidArgumentException("Invalid --{$name}");
    }
    return $value;
}

$cfg = [
    'domain' => (string)($raw['domain'] ?? 'ywbtest.site'),
    'campaigns' => int_option($raw, 'campaigns', 1, 1, 1000),
    'campaign_match' => enum_option($raw, 'campaign-match', ['first', 'last'], 'first'),
    'flows' => int_option($raw, 'flows', 1, 1, 50),
    'flow_match' => enum_option($raw, 'flow-match', ['first', 'last', 'none'], 'first'),
    'filters' => int_option($raw, 'filters', 0, 0, 50),
    'condition' => strtoupper(enum_option($raw, 'condition', ['and', 'or'], 'and')),
    'filter_match' => enum_option($raw, 'filter-match', ['early', 'late'], 'late'),
    'uniqueness' => enum_option($raw, 'uniqueness', ['on', 'off'], 'off'),
    'uniqueness_scope' => enum_option($raw, 'uniqueness-scope', ['off', 'campaign', 'flow'], 'off'),
    'caps' => enum_option($raw, 'caps', ['off', 'campaign', 'flow'], 'off'),
    'cap_checks' => int_option($raw, 'cap-checks', 1, 1, 20),
    'clicks' => int_option($raw, 'clicks', 0, 0, 1000000),
    'conversions' => int_option($raw, 'conversions', 0, 0, 100000),
    'cap_state' => enum_option($raw, 'cap-state', ['open', 'reached'], 'open'),
    'distribution' => enum_option($raw, 'distribution', ['equal', 'weighted', 'thompson'], 'equal'),
    'steps' => int_option($raw, 'steps', 1, 1, 10),
    'variants' => int_option($raw, 'variants', 1, 1, 5),
    'response' => enum_option($raw, 'response', ['redirect', 'html', 'white', 'jsconnect', 'mixed'], 'redirect'),
];

if ($cfg['uniqueness_scope'] !== 'off' && $cfg['uniqueness'] !== 'on') {
    throw new InvalidArgumentException('Uniqueness filtering requires --uniqueness=on');
}

if ($cfg['response'] === 'html') {
    $htmlDir = __DIR__ . '/../../code/' . trim(get_cache_path('landings'), '/\\') . '/loadtest-html';
    if (!is_dir($htmlDir) && !mkdir($htmlDir, 0755, true) && !is_dir($htmlDir)) {
        throw new RuntimeException('Failed to create benchmark HTML folder');
    }
    file_put_contents(
        $htmlDir . '/index.html',
        "<!doctype html><meta charset=\"utf-8\"><title>YellowTDS benchmark</title><p>ok</p>\n"
    );
}

global $db;
$databasePath = realpath(__DIR__ . '/../../code/db/clicks.db');
if ($databasePath === false) {
    throw new RuntimeException('Database path was not found');
}
$totalClicks = max($cfg['clicks'], $cfg['conversions']);
$existingCampaigns = $db->get_campaigns_list();
$historyReusable = count($existingCampaigns) === $cfg['campaigns'];
foreach ($existingCampaigns as $index => $campaign) {
    if (($campaign['name'] ?? '') !== sprintf('LT %04d', $index)) {
        $historyReusable = false;
        break;
    }
}
if ($historyReusable) {
    $probe = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
    $probe->busyTimeout(30000);
    $historyClicks = (int)$probe->querySingle("SELECT COUNT(*) FROM clicks WHERE clickid LIKE 'lt-h-%'");
    $historyConversions = (int)$probe->querySingle('SELECT COUNT(*) FROM conversions');
    $historyFlows = (int)$probe->querySingle("SELECT COUNT(DISTINCT flow) FROM clicks WHERE clickid LIKE 'lt-h-%'");
    $expectedFlows = $totalClicks === 0 ? 0 : min($cfg['flows'], $totalClicks);
    $historyReusable = $historyClicks === $totalClicks
        && $historyConversions === $cfg['conversions']
        && $historyFlows === $expectedFlows;
    if ($historyReusable) {
        $probe->exec('PRAGMA foreign_keys=ON');
        $probe->exec('BEGIN IMMEDIATE');
        $probe->exec("DELETE FROM clicks WHERE clickid NOT LIKE 'lt-h-%'");
        $probe->exec('COMMIT');
    }
    $probe->close();
}

$campaignIds = [];
if ($historyReusable) {
    $campaignIds = array_map(static fn(array $campaign): int => (int)$campaign['id'], $existingCampaigns);
} else {
    foreach ($existingCampaigns as $campaign) {
        if (!$db->delete_campaign((int)$campaign['id'], false)) {
            throw new RuntimeException('Failed to remove campaign ' . $campaign['id']);
        }
    }
    for ($campaignIndex = 0; $campaignIndex < $cfg['campaigns']; $campaignIndex++) {
        $campaignId = $db->add_campaign(sprintf('LT %04d', $campaignIndex), false);
        if (!is_int($campaignId)) {
            throw new RuntimeException('Failed to add benchmark campaign');
        }
        $campaignIds[] = $campaignId;
    }
}

function simple_rule(bool $matches): array
{
    return [
        'id' => 'lang', 'field' => 'lang', 'type' => 'string', 'input' => 'text',
        'operator' => 'equal', 'value' => $matches ? 'en' : 'zz',
    ];
}

function flow_rules(array $cfg, bool $flowMatches): array
{
    $rules = [];
    $count = $cfg['filters'];
    if ($count > 0) {
        for ($i = 0; $i < $count; $i++) {
            if ($cfg['condition'] === 'AND') {
                $matches = $flowMatches || $i !== ($cfg['filter_match'] === 'early' ? 0 : $count - 1);
            } else {
                $matches = $flowMatches && $i === ($cfg['filter_match'] === 'early' ? 0 : $count - 1);
            }
            $rules[] = simple_rule($matches);
        }
    } elseif (!$flowMatches) {
        // Preserve flow-position semantics when the measured flow itself has 0 filters.
        $rules[] = simple_rule(false);
    }

    if ($cfg['uniqueness_scope'] !== 'off') {
        $rules[] = [
            'id' => 'uniqueness', 'field' => 'uniqueness', 'type' => 'uniqueness',
            'input' => 'uniqueness', 'operator' => 'is_unique',
            'value' => $cfg['uniqueness_scope'],
        ];
    }

    if ($cfg['caps'] !== 'off') {
        $limit = $cfg['cap_state'] === 'reached' ? $cfg['conversions'] : $cfg['conversions'] + 1;
        for ($i = 0; $i < $cfg['cap_checks']; $i++) {
            $rules[] = [
                'id' => 'conversion_cap_' . $cfg['caps'],
                'field' => 'conversion_cap_' . $cfg['caps'],
                'type' => 'conversion_cap', 'input' => 'conversion_cap',
                'operator' => 'less',
                'value' => ['statuses' => ['Lead'], 'limit' => $limit],
            ];
        }
    }

    return $rules === [] ? [] : [
        'condition' => $cfg['condition'], 'rules' => $rules, 'valid' => true,
    ];
}

function make_steps(array $cfg): array
{
    $steps = [];
    for ($step = 0; $step < $cfg['steps']; $step++) {
        $urls = [];
        for ($variant = 0; $variant < $cfg['variants']; $variant++) {
            $label = "s{$step}-v{$variant}";
            $urls[] = [
                'url' => "https://example.com/{$label}",
                'label' => $label,
                'weight' => $cfg['distribution'] === 'weighted' ? ($variant + 1) : 0,
            ];
        }
        $html = $cfg['response'] === 'html';
        $steps[] = [
            'action' => $html ? 'folder' : 'redirect',
            'folders' => $html ? [[
                'name' => 'loadtest-html',
                'loadtype' => 'base',
                'weight' => $cfg['distribution'] === 'weighted' ? 100 : 0,
                'mvt' => ['enabled' => false, 'tests' => []],
            ]] : [],
            'redirect' => ['urls' => $html ? [] : $urls, 'type' => 302],
        ];
    }
    return $steps;
}

$targetIndex = $cfg['campaign_match'] === 'first' ? 0 : $cfg['campaigns'] - 1;
$targetCampaignId = 0;
for ($campaignIndex = 0; $campaignIndex < $cfg['campaigns']; $campaignIndex++) {
    $campaignId = $campaignIds[$campaignIndex];
    $settings = $db->get_campaign_settings($campaignId);
    $isTarget = $campaignIndex === $targetIndex;
    $settings['domains'] = [$isTarget ? $cfg['domain'] : "lt-{$campaignIndex}.invalid"];
    $settings['saveuserflow'] = false;
    $settings['uniqueness'] = [
        'enabled' => $cfg['uniqueness'] === 'on', 'method' => 'get',
        'ttl_hours' => 24, 'get_parameter' => 'uid',
    ];
    $settings['white']['filters'] = [
        'condition' => 'AND', 'valid' => true,
        'rules' => [[
            'id' => 'urlparam', 'field' => 'urlparam', 'type' => 'string', 'input' => 'text',
            'operator' => 'param_in', 'value' => ['white', '1'],
        ]],
    ];
    $settings['white']['action'] = 'error';
    $settings['white']['errorcodes'] = ['200'];
    $settings['black']['jsconnect'] = 'redirect';
    $settings['black']['jsbotdetection']['enabled'] = false;
    $settings['black']['flows'] = [];

    for ($flowIndex = 0; $flowIndex < $cfg['flows']; $flowIndex++) {
        $flowMatches = match ($cfg['flow_match']) {
            'first' => $flowIndex === 0,
            'last' => $flowIndex === $cfg['flows'] - 1,
            default => false,
        };
        $settings['black']['flows'][] = [
            'name' => "Flow {$flowIndex}",
            'filters' => flow_rules($cfg, $flowMatches),
            'distribution' => $cfg['distribution'],
            'optimize_for' => 'Lead', 'optimize_mode' => 'funnels',
            'steps' => make_steps($cfg),
        ];
    }

    if (!$db->save_campaign_settings($campaignId, $settings, false, false)) {
        throw new RuntimeException("Failed to save campaign {$campaignId}");
    }
    if ($isTarget) {
        $targetCampaignId = $campaignId;
    }
}

if (!$db->rebuild_runtime_cache()) {
    throw new RuntimeException('Failed to rebuild runtime campaign cache');
}

$sqlite = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE);
$sqlite->busyTimeout(30000);
$sqlite->exec('PRAGMA foreign_keys=ON');
$sqlite->exec('PRAGMA synchronous=OFF');
if (!$historyReusable) {
    $sqlite->exec('BEGIN IMMEDIATE');

    $clickStmt = $sqlite->prepare(
    'INSERT INTO clicks (campaign_id,time,ip,country,lang,os,osver,device,brand,model,isp,client,clientver,ua,userid,unique_hash,unique_flags,clickid,flow,path,params,status) '
    . 'VALUES (:campaign,:time,:ip,:country,:lang,:os,:osver,:device,:brand,:model,:isp,:client,:clientver,:ua,:userid,:hash,3,:clickid,:flow,:path,:params,:status)'
    );
    $conversionStmt = $sqlite->prepare(
    'INSERT INTO conversions (clickid,campaign_id,flow,time,status,raw_status,source,tid,tid_parameter,payout,currency,is_initial,changes_status,status_occurrence) '
    . 'VALUES (:clickid,:campaign,:flow,:time,"Lead","lead","postback",:tid,"tid",0,"USD",1,1,1)'
);
    $now = time();
    for ($i = 0; $i < $totalClicks; $i++) {
    $clickid = sprintf('lt-h-%08d', $i);
    $flow = 'Flow ' . ($i % $cfg['flows']);
    $clickStmt->reset();
    $clickStmt->bindValue(':campaign', $targetCampaignId, SQLITE3_INTEGER);
    $clickStmt->bindValue(':time', $now - ($i % 3600), SQLITE3_INTEGER);
    $clickStmt->bindValue(':ip', '198.51.100.' . (($i % 250) + 1), SQLITE3_TEXT);
    foreach (['country' => 'DE', 'lang' => 'en', 'os' => 'GNU/Linux', 'device' => 'desktop', 'brand' => '', 'model' => '', 'isp' => 'LoadTest', 'client' => 'Chrome', 'ua' => 'YellowTDS-history'] as $key => $value) {
        $clickStmt->bindValue(':' . $key, $value, SQLITE3_TEXT);
    }
    $clickStmt->bindValue(':osver', '1', SQLITE3_TEXT);
    $clickStmt->bindValue(':clientver', '1', SQLITE3_TEXT);
    $clickStmt->bindValue(':userid', 'lt-user-' . $i, SQLITE3_TEXT);
    $clickStmt->bindValue(':hash', hash('xxh128', 'lt-history-' . $i, true), SQLITE3_BLOB);
    $clickStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
    $clickStmt->bindValue(':flow', $flow, SQLITE3_TEXT);
    $clickStmt->bindValue(':path', '["s0-v0"]', SQLITE3_TEXT);
    $clickStmt->bindValue(':params', '{}', SQLITE3_TEXT);
    $clickStmt->bindValue(':status', $i < $cfg['conversions'] ? 'Lead' : '', SQLITE3_TEXT);
    if ($clickStmt->execute() === false) {
        throw new RuntimeException('Click seed failed at row ' . $i . ': ' . $sqlite->lastErrorMsg());
    }
    if ($i < $cfg['conversions']) {
        $conversionStmt->reset();
        $conversionStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
        $conversionStmt->bindValue(':campaign', $targetCampaignId, SQLITE3_INTEGER);
        $conversionStmt->bindValue(':flow', $flow, SQLITE3_TEXT);
        $conversionStmt->bindValue(':time', $now - ($i % 3600), SQLITE3_INTEGER);
        $conversionStmt->bindValue(':tid', 'lt-tid-' . $i, SQLITE3_TEXT);
        if ($conversionStmt->execute() === false) {
            throw new RuntimeException('Conversion seed failed at row ' . $i . ': ' . $sqlite->lastErrorMsg());
        }
    }
    if ($i > 0 && $i % 50000 === 0) {
        fwrite(STDERR, "Seeded {$i}/{$totalClicks}\n");
    }
    }
    $sqlite->exec('COMMIT');
    $sqlite->exec('ANALYZE');
}
$sqlite->exec('PRAGMA wal_checkpoint(TRUNCATE)');
$quickCheck = $sqlite->querySingle('PRAGMA quick_check');
$sqlite->close();

$cfg['target_campaign_id'] = $targetCampaignId;
$cfg['seeded_clicks'] = $totalClicks;
$cfg['seeded_conversions'] = $cfg['conversions'];
$cfg['history_reused'] = $historyReusable;
$cfg['quick_check'] = $quickCheck;
$cfg['generated_at'] = gmdate(DATE_ATOM);
echo json_encode($cfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
