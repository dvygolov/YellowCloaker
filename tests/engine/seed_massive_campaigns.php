#!/usr/bin/env php
<?php

/**
 * Seeds many campaigns and ~1M clicks for current month.
 *
 * Usage:
 *   php tests/seed_massive_campaigns.php [total_clicks] [campaign_count]
 *
 * Defaults:
 *   total_clicks  = 1000000
 *   campaign_count = 16
 */

$dbPath = __DIR__ . '/../../code/db/clicks.db';
$defaultJsonPath = __DIR__ . '/../../code/db/default.json';

$totalClicks = (int)($argv[1] ?? 1000000);
$campaignCount = (int)($argv[2] ?? 16);

if ($totalClicks < 1000) {
    fwrite(STDERR, "ERROR: total_clicks must be >= 1000\n");
    exit(1);
}
if ($campaignCount < 1 || $campaignCount > 200) {
    fwrite(STDERR, "ERROR: campaign_count must be between 1 and 200\n");
    exit(1);
}
if (!file_exists($dbPath)) {
    fwrite(STDERR, "ERROR: Database not found at $dbPath\n");
    exit(1);
}
if (!file_exists($defaultJsonPath)) {
    fwrite(STDERR, "ERROR: Default settings JSON not found at $defaultJsonPath\n");
    exit(1);
}

$defaultSettings = json_decode((string)file_get_contents($defaultJsonPath), true);
if (!is_array($defaultSettings)) {
    fwrite(STDERR, "ERROR: Failed to decode default.json\n");
    exit(1);
}

$db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
$db->busyTimeout(30000);
$db->exec('PRAGMA journal_mode = WAL');
$db->exec('PRAGMA synchronous = OFF');
$db->exec('PRAGMA temp_store = MEMORY');
$db->exec('PRAGMA cache_size = -200000');
$db->exec('PRAGMA foreign_keys = ON');

$startTs = strtotime(date('Y-m-01 00:00:00'));
$endTs = time();

$osWeights = [
    'Android' => 42,
    'iOS' => 25,
    'Windows' => 19,
    'Mac' => 11,
    'GNU/Linux' => 3,
];

$osVersions = [
    'Android' => ['11', '12', '13', '14'],
    'iOS' => ['16', '17', '17.1', '18'],
    'Windows' => ['10', '11'],
    'Mac' => ['13', '14', '15'],
    'GNU/Linux' => ['5.15', '6.1'],
];

$deviceByOs = [
    'Android' => 'mobile',
    'iOS' => 'mobile',
    'Windows' => 'desktop',
    'Mac' => 'desktop',
    'GNU/Linux' => 'desktop',
];

$brandsByOs = [
    'Android' => ['Samsung', 'Xiaomi', 'Huawei', 'OPPO', 'Realme', 'OnePlus', 'Google'],
    'iOS' => ['Apple'],
    'Windows' => [''],
    'Mac' => ['Apple'],
    'GNU/Linux' => [''],
];

$modelByBrand = [
    'Samsung' => ['Galaxy S24', 'Galaxy S23', 'Galaxy A54', 'Galaxy A15'],
    'Xiaomi' => ['Redmi Note 13', 'Redmi Note 12', 'POCO X6', 'Mi 14'],
    'Huawei' => ['P60', 'Nova 12', 'Mate 50'],
    'OPPO' => ['A98', 'Reno 11', 'Find X6'],
    'Realme' => ['12 Pro', '11 Pro', 'C67'],
    'OnePlus' => ['12', '11', 'Nord 3'],
    'Google' => ['Pixel 8', 'Pixel 8 Pro', 'Pixel 7a'],
    'Apple' => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'MacBook Pro'],
];

$clientWeights = [
    'Chrome' => 39,
    'Safari' => 21,
    'Firefox' => 9,
    'Samsung Browser' => 8,
    'Edge' => 8,
    'Opera' => 6,
    'Yandex Browser' => 6,
    'UC Browser' => 3,
];

$clientVersions = [
    'Chrome' => ['119', '120', '121', '122', '123'],
    'Safari' => ['16', '17', '17.1'],
    'Firefox' => ['120', '121', '122', '123'],
    'Samsung Browser' => ['23', '24'],
    'Edge' => ['119', '120', '121', '122'],
    'Opera' => ['104', '105', '106'],
    'Yandex Browser' => ['23', '24', '25'],
    'UC Browser' => ['15', '16'],
];

$ispWeights = [
    'MTS' => 8,
    'Beeline' => 7,
    'Megafon' => 6,
    'Tele2' => 5,
    'Rostelecom' => 6,
    'Comcast' => 6,
    'AT&T' => 5,
    'Verizon' => 5,
    'T-Mobile' => 5,
    'Deutsche Telekom' => 4,
    'Vodafone' => 4,
    'Orange' => 4,
    'Claro' => 3,
    'Jio' => 4,
    'Turkcell' => 3,
    'A1' => 3,
    'Telia' => 2,
    'TIM' => 3,
    'Play' => 2,
    'Kazakhtelecom' => 3,
];

$geoPresets = [
    ['US' => 35, 'CA' => 15, 'GB' => 20, 'AU' => 15, 'NZ' => 15],
    ['DE' => 30, 'FR' => 20, 'IT' => 15, 'ES' => 15, 'NL' => 10, 'PL' => 10],
    ['RU' => 45, 'KZ' => 20, 'BY' => 15, 'UZ' => 10, 'KG' => 10],
    ['BR' => 50, 'MX' => 20, 'CO' => 10, 'AR' => 10, 'CL' => 10],
    ['IN' => 40, 'PK' => 20, 'BD' => 15, 'LK' => 10, 'NP' => 15],
    ['TR' => 50, 'AZ' => 20, 'GE' => 10, 'RO' => 10, 'BG' => 10],
    ['ID' => 35, 'TH' => 20, 'VN' => 20, 'MY' => 15, 'PH' => 10],
    ['PL' => 30, 'CZ' => 20, 'SK' => 15, 'HU' => 20, 'RO' => 15],
];

$campaignNamePool = [
    'FB Nutra Scale',
    'Google Search Hybrid',
    'TikTok Dating Sprint',
    'Push Ecom Broad',
    'Native Quiz Funnel',
    'Social Finance Stream',
    'UAC App Boost',
    'SEO Longtail Capture',
    'Display Retarget Core',
    'Telegram Bot Offer',
    'DSP Multi GEO',
    'Smartlink Warmup',
    'Leadgen Insurance',
    'Sweepstakes Cluster',
    'Edu Subscription Push',
    'Crypto Alert Rotation',
    'Supplements Authority',
    'Skin Care Evergreen',
    'Whitehat Redirect Mix',
    'Performance Lab',
];

$flowTemplates = [
    [
        'name' => 'Flow Main',
        'weight' => 55,
        'steps' => [
            ['pre_quiz', 'pre_article', 'pre_video'],
            ['land_form', 'land_order', 'land_call'],
        ],
        'reach' => [0 => 30, 1 => 70],
    ],
    [
        'name' => 'Flow Retarget',
        'weight' => 30,
        'steps' => [
            ['pre_native', 'pre_news'],
            ['land_fast', 'land_coupon'],
        ],
        'reach' => [0 => 38, 1 => 62],
    ],
    [
        'name' => 'Flow Long',
        'weight' => 15,
        'steps' => [
            ['pre_story', 'pre_review'],
            ['bridge_a', 'bridge_b'],
            ['land_checkout', 'land_short'],
        ],
        'reach' => [0 => 24, 1 => 30, 2 => 46],
    ],
];

$utmSources = ['facebook', 'google', 'tiktok', 'native', 'push', 'telegram', 'yandex'];
$utmMediums = ['cpc', 'cpm', 'display', 'social'];

$campaignWeights = [];
for ($i = 0; $i < $campaignCount; $i++) {
    $campaignWeights[] = mt_rand(80, 125);
}

$campaignClicks = distribute_total($totalClicks, $campaignWeights);

$stmtCampaign = $db->prepare('INSERT INTO campaigns (name, settings) VALUES (:name, :settings)');
$stmtClick = $db->prepare(
    'INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, device, brand, model, isp, client, clientver, ua, userid, clickid, flow, path, step, params, status, cost, payout)
     VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :device, :brand, :model, :isp, :client, :clientver, :ua, :userid, :clickid, :flow, :path, :step, :params, :status, :cost, :payout)'
);
$stmtStep = $db->prepare(
    'INSERT INTO click_steps (clickid, step, variant, time) VALUES (:clickid, :step, :variant, :time)'
);

$globalClickCounter = 0;
$totalInserted = 0;

echo "Seeding $campaignCount campaigns and $totalClicks clicks for current month...\n";
echo 'Date range: ' . date('Y-m-d H:i:s', $startTs) . ' .. ' . date('Y-m-d H:i:s', $endTs) . "\n\n";

for ($ci = 0; $ci < $campaignCount; $ci++) {
    $clickTarget = $campaignClicks[$ci];
    $geoWeights = $geoPresets[$ci % count($geoPresets)];
    $campaignName = sprintf(
        'Perf Seed %s #%02d — %s',
        date('Y-m'),
        $ci + 1,
        $campaignNamePool[$ci % count($campaignNamePool)]
    );

    $convRate = mt_rand(130, 320) / 1000.0;
    $purchaseShare = mt_rand(35, 62) / 100.0;
    $rejectShare = mt_rand(12, 28) / 100.0;
    $trashShare = mt_rand(6, 16) / 100.0;
    $leadShare = max(0.0, 1.0 - ($purchaseShare + $rejectShare + $trashShare));

    $costMin = mt_rand(8, 55) / 100.0;
    $costMax = $costMin + mt_rand(15, 140) / 100.0;
    $payoutMin = mt_rand(500, 1500) / 100.0;
    $payoutMax = $payoutMin + mt_rand(700, 6500) / 100.0;

    $flowSettings = build_flow_settings($flowTemplates);
    $settings = $defaultSettings;
    $settings['domains'] = [
        sprintf('seed-%02d.local', $ci + 1),
        sprintf('seed-%02d.example.test', $ci + 1),
    ];
    $settings['saveuserflow'] = false;
    $settings['apikey'] = generate_api_key();
    $settings['black']['flows'] = $flowSettings;

    $settingsJson = json_encode($settings, JSON_UNESCAPED_SLASHES);
    if ($settingsJson === false) {
        fwrite(STDERR, "ERROR: Failed to encode settings JSON for campaign #" . ($ci + 1) . "\n");
        exit(1);
    }

    $stmtCampaign->bindValue(':name', $campaignName, SQLITE3_TEXT);
    $stmtCampaign->bindValue(':settings', $settingsJson, SQLITE3_TEXT);
    if ($stmtCampaign->execute() === false) {
        fwrite(STDERR, "ERROR: Failed to insert campaign: $campaignName\n");
        exit(1);
    }

    $campaignId = (int)$db->lastInsertRowID();
    echo sprintf("[%02d/%02d] #%d %s -> target clicks: %d\n", $ci + 1, $campaignCount, $campaignId, $campaignName, $clickTarget);

    $db->exec('BEGIN IMMEDIATE');

    $userPool = [];
    $maxUsers = max(1000, (int)round($clickTarget * 0.65));

    $insertedHere = 0;
    $purchases = 0;
    $leads = 0;

    for ($k = 0; $k < $clickTarget; $k++) {
        $globalClickCounter++;

        $flowTemplate = pick_flow($flowTemplates);
        $flowName = $flowTemplate['name'];
        $path = [];
        foreach ($flowTemplate['steps'] as $stepVariants) {
            $path[] = $stepVariants[array_rand($stepVariants)];
        }

        $stepReached = (int)weighted_pick($flowTemplate['reach']);
        if ($stepReached > count($path) - 1) {
            $stepReached = count($path) - 1;
        }

        $country = (string)weighted_pick($geoWeights);
        $lang = language_for_country($country);

        $os = (string)weighted_pick($osWeights);
        $osver = $osVersions[$os][array_rand($osVersions[$os])];
        $device = $deviceByOs[$os] ?? 'mobile';
        $brandCandidates = $brandsByOs[$os] ?? [''];
        $brand = $brandCandidates[array_rand($brandCandidates)];
        $modelCandidates = $modelByBrand[$brand] ?? [''];
        $model = $modelCandidates[array_rand($modelCandidates)];

        $client = (string)weighted_pick($clientWeights);
        $clientver = $clientVersions[$client][array_rand($clientVersions[$client])];
        $isp = (string)weighted_pick($ispWeights);
        $ip = random_ip();
        $ua = random_ua($os, $device, $client, $clientver);

        $userid = pick_userid($userPool, $maxUsers, $campaignId);
        $clickid = 'seed_' . base_convert((string)$campaignId, 10, 36) . '_' . base_convert((string)$globalClickCounter, 10, 36);

        $time = random_month_timestamp($startTs, $endTs);
        $cost = random_float($costMin, $costMax, 3);

        $status = null;
        $payout = 0.0;
        $isLastStep = ($stepReached === (count($path) - 1));
        if ($isLastStep && mt_rand(1, 100000) <= (int)round($convRate * 100000)) {
            $status = pick_status($purchaseShare, $leadShare, $rejectShare, $trashShare);
            if ($status === 'Purchase') {
                $payout = random_float($payoutMin, $payoutMax, 2);
                $purchases++;
            } elseif ($status === 'Lead') {
                $leads++;
            }
        }

        $params = [
            'utm_source' => $utmSources[array_rand($utmSources)],
            'utm_medium' => $utmMediums[array_rand($utmMediums)],
            'utm_campaign' => 'seed_' . date('Ym'),
            'creative' => 'cr_' . mt_rand(1, 120),
            'placement' => 'pl_' . mt_rand(1, 30),
            'ext_click_id' => 'ext_' . base_convert((string)$globalClickCounter, 10, 36),
        ];

        $stmtClick->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
        $stmtClick->bindValue(':time', $time, SQLITE3_INTEGER);
        $stmtClick->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmtClick->bindValue(':country', $country, SQLITE3_TEXT);
        $stmtClick->bindValue(':lang', $lang, SQLITE3_TEXT);
        $stmtClick->bindValue(':os', $os, SQLITE3_TEXT);
        $stmtClick->bindValue(':osver', $osver, SQLITE3_TEXT);
        $stmtClick->bindValue(':device', $device, SQLITE3_TEXT);
        $stmtClick->bindValue(':brand', $brand, SQLITE3_TEXT);
        $stmtClick->bindValue(':model', $model, SQLITE3_TEXT);
        $stmtClick->bindValue(':isp', $isp, SQLITE3_TEXT);
        $stmtClick->bindValue(':client', $client, SQLITE3_TEXT);
        $stmtClick->bindValue(':clientver', $clientver, SQLITE3_TEXT);
        $stmtClick->bindValue(':ua', $ua, SQLITE3_TEXT);
        $stmtClick->bindValue(':userid', $userid, SQLITE3_TEXT);
        $stmtClick->bindValue(':clickid', $clickid, SQLITE3_TEXT);
        $stmtClick->bindValue(':flow', $flowName, SQLITE3_TEXT);
        $stmtClick->bindValue(':path', json_encode($path), SQLITE3_TEXT);
        $stmtClick->bindValue(':step', $stepReached, SQLITE3_INTEGER);
        $stmtClick->bindValue(':params', json_encode($params), SQLITE3_TEXT);
        $stmtClick->bindValue(':status', $status, SQLITE3_TEXT);
        $stmtClick->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmtClick->bindValue(':payout', $payout, SQLITE3_FLOAT);
        if ($stmtClick->execute() === false) {
            $db->exec('ROLLBACK');
            fwrite(STDERR, "ERROR: Failed to insert click for campaign $campaignId\n");
            exit(1);
        }

        for ($si = 0; $si <= $stepReached; $si++) {
            $stmtStep->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $stmtStep->bindValue(':step', $si, SQLITE3_INTEGER);
            $stmtStep->bindValue(':variant', $path[$si], SQLITE3_TEXT);
            $stmtStep->bindValue(':time', $time + $si, SQLITE3_INTEGER);
            if ($stmtStep->execute() === false) {
                $db->exec('ROLLBACK');
                fwrite(STDERR, "ERROR: Failed to insert click_step for clickid $clickid\n");
                exit(1);
            }
        }

        $insertedHere++;

        if ($insertedHere % 50000 === 0) {
            echo '  ... ' . $insertedHere . '/' . $clickTarget . "\n";
        }
    }

    $db->exec('COMMIT');
    $totalInserted += $insertedHere;

    echo sprintf("  done: %d clicks, purchases=%d, leads=%d\n\n", $insertedHere, $purchases, $leads);
}

$db->close();

echo "========================================\n";
echo "Seeding finished successfully.\n";
echo "Campaigns added: $campaignCount\n";
echo "Clicks inserted: $totalInserted\n";
echo "Month: " . date('Y-m') . "\n";

function distribute_total(int $total, array $weights): array
{
    $sum = array_sum($weights);
    $parts = [];
    $acc = 0;
    foreach ($weights as $i => $w) {
        if ($i === count($weights) - 1) {
            $parts[] = $total - $acc;
            break;
        }
        $v = (int)floor($total * ($w / $sum));
        $parts[] = $v;
        $acc += $v;
    }
    return $parts;
}

function weighted_pick(array $weights)
{
    $total = array_sum($weights);
    $r = mt_rand(1, $total);
    $cum = 0;
    foreach ($weights as $key => $w) {
        $cum += $w;
        if ($r <= $cum) {
            return $key;
        }
    }
    return array_key_first($weights);
}

function pick_flow(array $flowTemplates): array
{
    $weights = [];
    foreach ($flowTemplates as $i => $f) {
        $weights[$i] = $f['weight'];
    }
    $idx = (int)weighted_pick($weights);
    return $flowTemplates[$idx];
}

function build_flow_settings(array $flowTemplates): array
{
    $result = [];
    foreach ($flowTemplates as $flow) {
        $steps = [];
        foreach ($flow['steps'] as $variants) {
            $weights = equal_weights(count($variants));
            $folders = [];
            foreach ($variants as $variantIndex => $variant) {
                $folders[] = [
                    'name' => $variant,
                    'loadtype' => 'base',
                    'weight' => $weights[$variantIndex],
                    'mvt' => ['enabled' => false, 'tests' => []],
                ];
            }
            $steps[] = [
                'action' => 'folder',
                'folders' => $folders,
                'redirect' => ['urls' => [], 'type' => 302],
            ];
        }

        $result[] = [
            'name' => $flow['name'],
            'filters' => (object)[],
            'distribution' => 'weighted',
            'optimize_for' => 'Lead',
            'optimize_mode' => 'funnels',
            'steps' => $steps,
        ];
    }
    return $result;
}

function equal_weights(int $count): array
{
    if ($count <= 0) {
        return [];
    }
    $base = intdiv(100, $count);
    $remainder = 100 - ($base * $count);
    $weights = array_fill(0, $count, $base);
    for ($i = 0; $i < $remainder; $i++) {
        $weights[$i]++;
    }
    return $weights;
}

function generate_api_key(): string
{
    return sprintf(
        '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535)
    );
}

function language_for_country(string $country): string
{
    $map = [
        'RU' => 'ru', 'KZ' => 'kk', 'BY' => 'ru', 'UZ' => 'uz', 'KG' => 'ky',
        'US' => 'en', 'CA' => 'en', 'GB' => 'en', 'AU' => 'en', 'NZ' => 'en',
        'DE' => 'de', 'AT' => 'de', 'CH' => 'de', 'FR' => 'fr',
        'IT' => 'it', 'ES' => 'es', 'MX' => 'es', 'AR' => 'es', 'CL' => 'es',
        'BR' => 'pt', 'PL' => 'pl', 'TR' => 'tr', 'AZ' => 'az',
        'IN' => 'hi', 'PK' => 'ur', 'BD' => 'bn', 'LK' => 'si', 'NP' => 'ne',
        'ID' => 'id', 'TH' => 'th', 'VN' => 'vi', 'MY' => 'ms', 'PH' => 'en',
        'NL' => 'nl', 'CZ' => 'cs', 'SK' => 'sk', 'HU' => 'hu', 'RO' => 'ro', 'BG' => 'bg',
    ];
    return $map[$country] ?? 'en';
}

function random_ip(): string
{
    return mt_rand(2, 223) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);
}

function random_ua(string $os, string $device, string $client, string $clientVer): string
{
    $platform = match ($os) {
        'Android' => 'Linux; Android ' . mt_rand(11, 14) . '; ' . ($device === 'mobile' ? 'Mobile' : 'Tablet'),
        'iOS' => 'iPhone; CPU iPhone OS ' . mt_rand(16, 18) . '_0 like Mac OS X',
        'Windows' => 'Windows NT 10.0; Win64; x64',
        'Mac' => 'Macintosh; Intel Mac OS X 10_15_7',
        default => 'X11; Linux x86_64',
    };

    return 'Mozilla/5.0 (' . $platform . ') AppleWebKit/537.36 (KHTML, like Gecko) ' . $client . '/' . $clientVer . '.0 Safari/537.36';
}

function pick_userid(array &$pool, int $maxUsers, int $campaignId): string
{
    $count = count($pool);
    $mustCreate = $count === 0;
    $canCreate = $count < $maxUsers;
    $createNow = $mustCreate || ($canCreate && mt_rand(1, 100) <= 65);

    if ($createNow) {
        $uid = 'u_' . base_convert((string)$campaignId, 10, 36) . '_' . base_convert((string)($count + 1), 10, 36);
        $pool[] = $uid;
        return $uid;
    }

    return $pool[array_rand($pool)];
}

function random_month_timestamp(int $startTs, int $endTs): int
{
    // Bias to recent data, but still across whole month.
    $span = max(1, $endTs - $startTs);
    $u = mt_rand(0, 1000000) / 1000000;
    $biased = pow($u, 0.7);
    return $startTs + (int)floor($span * $biased);
}

function pick_status(float $purchaseShare, float $leadShare, float $rejectShare, float $trashShare): string
{
    $r = mt_rand(1, 100000) / 100000.0;
    if ($r < $purchaseShare) {
        return 'Purchase';
    }
    $r -= $purchaseShare;
    if ($r < $leadShare) {
        return 'Lead';
    }
    $r -= $leadShare;
    if ($r < $rejectShare) {
        return 'Reject';
    }
    return 'Trash';
}

function random_float(float $min, float $max, int $precision): float
{
    $scale = 10 ** $precision;
    $imin = (int)round($min * $scale);
    $imax = (int)round($max * $scale);
    return mt_rand(min($imin, $imax), max($imin, $imax)) / $scale;
}
