#!/usr/bin/env php
<?php
/**
 * Seed multiple campaigns with varied data profiles.
 *
 * Usage: php tests/seed_campaigns.php
 *
 * Creates ~10 campaigns with different characteristics:
 * - Profitable, break-even, and unprofitable campaigns
 * - Different geos, volumes, conversion rates
 * - Varied cost/payout ratios to produce red and green ROI
 */

$dbPath = __DIR__ . '/../../code/db/clicks.db';
if (!file_exists($dbPath)) {
    echo "ERROR: Database not found at $dbPath\n";
    exit(1);
}

$defaultSettings = file_get_contents(__DIR__ . '/../../code/db/default.json');

// Campaign profiles: name, clickCount, costRange, payoutRange, conversionRate, purchaseRate
$campaignProfiles = [
    [
        'name' => 'FB CIS Nutra',
        'clicks' => 1800,
        'costMin' => 0.80, 'costMax' => 1.80,
        'payoutMin' => 15, 'payoutMax' => 40,
        'convRate' => 0.35, 'purchaseRate' => 0.14,
        'countries' => ['RU' => 40, 'KZ' => 20, 'BY' => 15, 'UA' => 25],
        'flows' => ['Flow CIS' => 60, 'Flow KZ' => 40],
    ],
    [
        'name' => 'Google EU Gambling',
        'clicks' => 2200,
        'costMin' => 1.50, 'costMax' => 3.50,
        'payoutMin' => 30, 'payoutMax' => 80,
        'convRate' => 0.20, 'purchaseRate' => 0.08,
        'countries' => ['DE' => 30, 'FR' => 20, 'IT' => 15, 'ES' => 15, 'PL' => 20],
        'flows' => ['Flow EU Main' => 50, 'Flow EU Test' => 30, 'Flow EU Retarget' => 20],
    ],
    [
        'name' => 'TikTok LATAM Dating',
        'clicks' => 1500,
        'costMin' => 0.30, 'costMax' => 0.80,
        'payoutMin' => 3, 'payoutMax' => 12,
        'convRate' => 0.45, 'purchaseRate' => 0.20,
        'countries' => ['BR' => 40, 'MX' => 25, 'CO' => 15, 'AR' => 20],
        'flows' => ['Flow LATAM' => 70, 'Flow BR Only' => 30],
    ],
    [
        // UNPROFITABLE — high costs, low payouts
        'name' => 'FB US Finance [LOSS]',
        'clicks' => 1200,
        'costMin' => 2.00, 'costMax' => 5.00,
        'payoutMin' => 15, 'payoutMax' => 40,
        'convRate' => 0.10, 'purchaseRate' => 0.03,
        'countries' => ['US' => 70, 'CA' => 30],
        'flows' => ['Flow US' => 100],
    ],
    [
        'name' => 'VK RU E-commerce',
        'clicks' => 2500,
        'costMin' => 0.50, 'costMax' => 1.20,
        'payoutMin' => 8, 'payoutMax' => 25,
        'convRate' => 0.30, 'purchaseRate' => 0.15,
        'countries' => ['RU' => 80, 'BY' => 10, 'KZ' => 10],
        'flows' => ['Flow Main' => 50, 'Flow Promo' => 30, 'Flow Retarget' => 20],
    ],
    [
        // UNPROFITABLE — decent conversions but terrible payouts vs costs
        'name' => 'Google IN Crypto [LOSS]',
        'clicks' => 900,
        'costMin' => 0.80, 'costMax' => 1.80,
        'payoutMin' => 5, 'payoutMax' => 15,
        'convRate' => 0.25, 'purchaseRate' => 0.05,
        'countries' => ['IN' => 60, 'PK' => 20, 'BD' => 20],
        'flows' => ['Flow IN' => 70, 'Flow SA' => 30],
    ],
    [
        'name' => 'MyTarget RU Insurance',
        'clicks' => 1600,
        'costMin' => 0.80, 'costMax' => 1.80,
        'payoutMin' => 20, 'payoutMax' => 50,
        'convRate' => 0.22, 'purchaseRate' => 0.10,
        'countries' => ['RU' => 90, 'KZ' => 10],
        'flows' => ['Flow Insurance' => 100],
    ],
    [
        // BREAK-EVEN — costs ≈ revenue
        'name' => 'Yandex RU Apps [BREAK-EVEN]',
        'clicks' => 1100,
        'costMin' => 0.60, 'costMax' => 1.20,
        'payoutMin' => 5, 'payoutMax' => 12,
        'convRate' => 0.28, 'purchaseRate' => 0.09,
        'countries' => ['RU' => 85, 'BY' => 15],
        'flows' => ['Flow Apps' => 60, 'Flow Games' => 40],
    ],
    [
        // VERY UNPROFITABLE
        'name' => 'FB TR Sweepstakes [LOSS]',
        'clicks' => 700,
        'costMin' => 0.80, 'costMax' => 1.50,
        'payoutMin' => 2, 'payoutMax' => 8,
        'convRate' => 0.15, 'purchaseRate' => 0.02,
        'countries' => ['TR' => 80, 'AZ' => 20],
        'flows' => ['Flow TR' => 100],
    ],
    [
        // PROFITABLE but realistic
        'name' => 'Push DE Supplements',
        'clicks' => 3000,
        'costMin' => 0.80, 'costMax' => 1.80,
        'payoutMin' => 25, 'payoutMax' => 60,
        'convRate' => 0.18, 'purchaseRate' => 0.10,
        'countries' => ['DE' => 50, 'AT' => 25, 'CH' => 25],
        'flows' => ['Flow DACH' => 60, 'Flow DE Only' => 40],
    ],
];

// ── Shared distributions ──

$oses = ['Android' => 45, 'iOS' => 25, 'Windows' => 20, 'macOS' => 8, 'Linux' => 2];
$osVersions = [
    'Android' => [11.0, 12.0, 13.0, 14.0],
    'iOS' => [15.0, 16.0, 17.0, 17.1],
    'Windows' => [10.0, 11.0],
    'macOS' => [13.0, 14.0],
    'Linux' => [5.15, 6.1],
];
$devices = [
    'Android' => ['mobile' => 85, 'tablet' => 15],
    'iOS' => ['mobile' => 80, 'tablet' => 20],
    'Windows' => ['desktop' => 95, 'tablet' => 5],
    'macOS' => ['desktop' => 100],
    'Linux' => ['desktop' => 100],
];
$brands = [
    'Android' => ['Samsung' => 35, 'Xiaomi' => 25, 'Huawei' => 15, 'OPPO' => 10, 'Realme' => 8, 'OnePlus' => 7],
    'iOS' => ['Apple' => 100],
    'Windows' => ['' => 100],
    'macOS' => ['Apple' => 100],
    'Linux' => ['' => 100],
];
$models = [
    'Samsung' => ['Galaxy S23', 'Galaxy S22', 'Galaxy A54', 'Galaxy A14'],
    'Xiaomi' => ['Redmi Note 12', 'POCO X5', 'Mi 13'],
    'Huawei' => ['P60', 'Nova 11'],
    'OPPO' => ['A78', 'Reno 10'],
    'Realme' => ['11 Pro', 'C55'],
    'OnePlus' => ['11', 'Nord 3'],
    'Apple' => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'iPad Air'],
];
$clients = ['Chrome' => 40, 'Safari' => 20, 'Firefox' => 8, 'Samsung Browser' => 10, 'Edge' => 7, 'Opera' => 5, 'Yandex Browser' => 8, 'UC Browser' => 2];
$clientVersions = [
    'Chrome' => [119.0, 120.0, 121.0, 122.0],
    'Safari' => [16.0, 17.0, 17.1],
    'Firefox' => [120.0, 121.0],
    'Samsung Browser' => [23.0, 24.0],
    'Edge' => [119.0, 120.0],
    'Opera' => [104.0, 105.0],
    'Yandex Browser' => [23.0, 24.0],
    'UC Browser' => [15.0, 16.0],
];
$isps = [
    'MTS' => 10, 'Beeline' => 8, 'Megafon' => 7, 'Tele2' => 5,
    'Comcast' => 6, 'AT&T' => 5, 'Verizon' => 4, 'T-Mobile' => 4,
    'Deutsche Telekom' => 4, 'Vodafone' => 5, 'Orange' => 4,
    'Rostelecom' => 4, 'Claro' => 4, 'Jio' => 4, 'Turkcell' => 4,
    'Play' => 3, 'TIM' => 3, 'Kazakhtelecom' => 3,
];
$languages = ['ru' => 25, 'en' => 25, 'de' => 8, 'fr' => 5, 'pt' => 7, 'tr' => 5, 'pl' => 4, 'it' => 3, 'es' => 5, 'hi' => 5, 'kk' => 4, 'ar' => 4];
$prelands = ['pre_quiz', 'pre_article', 'pre_video', 'pre_news', 'pre_review'];
$landings = ['land_order', 'land_form', 'land_call', 'land_shop'];
$utmSources = ['facebook', 'google', 'tiktok', 'vk', 'mytarget', 'yandex', 'push'];
$utmMediums = ['cpc', 'cpm', 'social', 'display'];
$utmCampaigns = ['camp_001', 'camp_002', 'camp_003', 'camp_winter', 'camp_spring'];
$blockReasons = ['Bot detected', 'IP blacklisted', 'Country not allowed', 'Referer mismatch', 'User-Agent blocked', 'Proxy detected'];

function weighted_random(array $items): string {
    $total = array_sum($items);
    $r = mt_rand(1, $total);
    $cum = 0;
    foreach ($items as $item => $w) {
        $cum += $w;
        if ($r <= $cum) return (string)$item;
    }
    return (string)array_key_first($items);
}
function random_ip(): string { return mt_rand(1,223).'.'.mt_rand(0,255).'.'.mt_rand(0,255).'.'.mt_rand(1,254); }
function random_ua(string $os, string $client, float $cv): string {
    $p = match($os) {
        'Android' => 'Linux; Android '.mt_rand(11,14).'; Mobile',
        'iOS' => 'iPhone; CPU iPhone OS '.mt_rand(15,17).'_0 like Mac OS X',
        'Windows' => 'Windows NT 10.0; Win64; x64',
        'macOS' => 'Macintosh; Intel Mac OS X 10_15_7',
        default => 'X11; Linux x86_64',
    };
    return "Mozilla/5.0 ($p) AppleWebKit/537.36 (KHTML, like Gecko) $client/$cv Safari/537.36";
}
function random_subid(): string { return bin2hex(random_bytes(8)); }
function random_string(int $n): string { return substr(bin2hex(random_bytes($n)), 0, $n); }

// ── Main ──

echo "Opening database: $dbPath\n";
$db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
$db->busyTimeout(5000);

$now = time();
$thirtyDaysAgo = $now - 30 * 86400;

$stmtClick = $db->prepare(
    "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, device, brand, model, isp, client, clientver, ua, subid, preland, land, flow, params, lpclick, status, cost, payout)
     VALUES (:cid, :time, :ip, :country, :lang, :os, :osver, :device, :brand, :model, :isp, :client, :clientver, :ua, :subid, :preland, :land, :flow, :params, :lpclick, :status, :cost, :payout)"
);
$stmtBlock = $db->prepare(
    "INSERT INTO blocked (campaign_id, time, ip, country, lang, os, osver, device, brand, model, isp, client, clientver, ua, params, reason)
     VALUES (:cid, :time, :ip, :country, :lang, :os, :osver, :device, :brand, :model, :isp, :client, :clientver, :ua, :params, :reason)"
);

$totalClicks = 0;
$totalBlocked = 0;

foreach ($campaignProfiles as $pi => $profile) {
    // Create campaign
    $db->exec("INSERT INTO campaigns (name, settings) VALUES ('" . SQLite3::escapeString($profile['name']) . "', '" . SQLite3::escapeString($defaultSettings) . "')");
    $campId = $db->lastInsertRowID();
    echo "\nCampaign #{$campId}: {$profile['name']} ({$profile['clicks']} clicks)\n";

    $count = $profile['clicks'];
    $subidPool = [];
    $poolSize = (int)($count * 0.8);
    for ($j = 0; $j < $poolSize; $j++) $subidPool[] = random_subid();

    $inserted = 0;
    $blocked = 0;
    $revenue = 0;
    $costs = 0;

    $db->exec('BEGIN TRANSACTION');

    for ($i = 0; $i < $count; $i++) {
        $dayOffset = pow(mt_rand(0, 1000) / 1000, 0.7) * 30;
        $clickTime = $thirtyDaysAgo + (int)($dayOffset * 86400) + mt_rand(0, 86399);

        $country = weighted_random($profile['countries']);
        $lang = weighted_random($languages);
        $os = weighted_random($oses);
        $osVer = $osVersions[$os][array_rand($osVersions[$os])];
        $deviceType = weighted_random($devices[$os]);
        $brand = weighted_random($brands[$os]);
        $model = ($brand && isset($models[$brand])) ? $models[$brand][array_rand($models[$brand])] : '';
        $client = weighted_random($clients);
        $clientVer = $clientVersions[$client][array_rand($clientVersions[$client])];
        $isp = weighted_random($isps);
        $ip = random_ip();
        $ua = random_ua($os, $client, $clientVer);

        // ~12% blocked
        if (mt_rand(1, 100) <= 12) {
            $params = json_encode(['utm_source' => $utmSources[array_rand($utmSources)]]);
            $stmtBlock->bindValue(':cid', $campId, SQLITE3_INTEGER);
            $stmtBlock->bindValue(':time', $clickTime, SQLITE3_INTEGER);
            $stmtBlock->bindValue(':ip', $ip, SQLITE3_TEXT);
            $stmtBlock->bindValue(':country', $country, SQLITE3_TEXT);
            $stmtBlock->bindValue(':lang', $lang, SQLITE3_TEXT);
            $stmtBlock->bindValue(':os', $os, SQLITE3_TEXT);
            $stmtBlock->bindValue(':osver', $osVer, SQLITE3_FLOAT);
            $stmtBlock->bindValue(':device', $deviceType, SQLITE3_TEXT);
            $stmtBlock->bindValue(':brand', $brand, SQLITE3_TEXT);
            $stmtBlock->bindValue(':model', $model, SQLITE3_TEXT);
            $stmtBlock->bindValue(':isp', $isp, SQLITE3_TEXT);
            $stmtBlock->bindValue(':client', $client, SQLITE3_TEXT);
            $stmtBlock->bindValue(':clientver', $clientVer, SQLITE3_FLOAT);
            $stmtBlock->bindValue(':ua', $ua, SQLITE3_TEXT);
            $stmtBlock->bindValue(':params', $params, SQLITE3_TEXT);
            $stmtBlock->bindValue(':reason', $blockReasons[array_rand($blockReasons)], SQLITE3_TEXT);
            $stmtBlock->execute();
            $blocked++;
            continue;
        }

        $subid = mt_rand(1, 100) <= 20 ? $subidPool[mt_rand(0, count($subidPool) - 1)] : random_subid();
        $flow = weighted_random($profile['flows']);
        $preland = $prelands[array_rand($prelands)];
        $land = $landings[array_rand($landings)];
        $lpclick = mt_rand(1, 100) <= 40 ? 1 : 0;

        // Status based on profile rates
        $roll = mt_rand(1, 1000) / 1000;
        if ($roll < $profile['purchaseRate']) {
            $status = 'Purchase';
        } elseif ($roll < $profile['convRate']) {
            // Distribute remaining conversions among Lead, Reject, Trash
            $subRoll = mt_rand(1, 100);
            if ($subRoll <= 55) $status = 'Lead';
            elseif ($subRoll <= 85) $status = 'Reject';
            else $status = 'Trash';
        } else {
            $status = null;
        }

        $cost = round(mt_rand((int)($profile['costMin'] * 1000), (int)($profile['costMax'] * 1000)) / 1000, 3);
        $payout = 0;
        if ($status === 'Purchase') {
            $payout = round(mt_rand((int)($profile['payoutMin'] * 100), (int)($profile['payoutMax'] * 100)) / 100, 2);
        }

        $costs += $cost;
        $revenue += $payout;

        $paramsArr = [
            'utm_source' => $utmSources[array_rand($utmSources)],
            'utm_medium' => $utmMediums[array_rand($utmMediums)],
            'utm_campaign' => $utmCampaigns[array_rand($utmCampaigns)],
            'clickid' => random_string(16),
        ];
        if (mt_rand(1, 100) <= 30) $paramsArr['fbclid'] = random_string(24);
        $params = json_encode($paramsArr);

        $stmtClick->bindValue(':cid', $campId, SQLITE3_INTEGER);
        $stmtClick->bindValue(':time', $clickTime, SQLITE3_INTEGER);
        $stmtClick->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmtClick->bindValue(':country', $country, SQLITE3_TEXT);
        $stmtClick->bindValue(':lang', $lang, SQLITE3_TEXT);
        $stmtClick->bindValue(':os', $os, SQLITE3_TEXT);
        $stmtClick->bindValue(':osver', $osVer, SQLITE3_FLOAT);
        $stmtClick->bindValue(':device', $deviceType, SQLITE3_TEXT);
        $stmtClick->bindValue(':brand', $brand, SQLITE3_TEXT);
        $stmtClick->bindValue(':model', $model, SQLITE3_TEXT);
        $stmtClick->bindValue(':isp', $isp, SQLITE3_TEXT);
        $stmtClick->bindValue(':client', $client, SQLITE3_TEXT);
        $stmtClick->bindValue(':clientver', $clientVer, SQLITE3_FLOAT);
        $stmtClick->bindValue(':ua', $ua, SQLITE3_TEXT);
        $stmtClick->bindValue(':subid', $subid, SQLITE3_TEXT);
        $stmtClick->bindValue(':preland', $preland, SQLITE3_TEXT);
        $stmtClick->bindValue(':land', $land, SQLITE3_TEXT);
        $stmtClick->bindValue(':flow', $flow, SQLITE3_TEXT);
        $stmtClick->bindValue(':params', $params, SQLITE3_TEXT);
        $stmtClick->bindValue(':lpclick', $lpclick, SQLITE3_INTEGER);
        $stmtClick->bindValue(':status', $status, SQLITE3_TEXT);
        $stmtClick->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmtClick->bindValue(':payout', $payout, SQLITE3_FLOAT);
        $stmtClick->execute();
        $inserted++;
    }

    $db->exec('COMMIT');

    $profit = $revenue - $costs;
    $roi = $costs > 0 ? round(($revenue - $costs) / $costs * 100, 1) : 0;
    $profitColor = $profit >= 0 ? 'GREEN' : 'RED';

    echo "  Allowed: $inserted | Blocked: $blocked\n";
    echo "  Revenue: " . round($revenue, 2) . " | Costs: " . round($costs, 2) . " | Profit: " . round($profit, 2) . " ($profitColor) | ROI: {$roi}%\n";

    $totalClicks += $inserted;
    $totalBlocked += $blocked;
}

$db->close();

echo "\n=== TOTAL ===\n";
echo "Campaigns created: " . count($campaignProfiles) . "\n";
echo "Total clicks: $totalClicks\n";
echo "Total blocked: $totalBlocked\n";
echo "\nDone! Open admin to see all campaigns.\n";
