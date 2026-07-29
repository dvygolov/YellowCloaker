#!/usr/bin/env php
<?php
/**
 * Seed the real database with realistic test data for manual testing.
 *
 * Usage: php tests/seed_test_data.php [count]
 *   count — number of clicks to generate (default: 2000)
 *
 * Generates clicks spread over the last 30 days with realistic distributions:
 * - Multiple countries, OS, devices, browsers, ISPs
 * - Multiple flows, prelands, landings
 * - Realistic conversion funnel: ~60% no status, ~15% Lead, ~12% Purchase, ~8% Reject, ~5% Trash
 * - LP click rate ~40%
 * - Costs 0.02-0.15 per click, payouts 5-50 per conversion
 * - Subid duplicates ~20% (returning visitors)
 */

$dbPath = __DIR__ . '/../../code/db/clicks.db';

if (!file_exists($dbPath)) {
    echo "ERROR: Database not found at $dbPath\n";
    exit(1);
}

$count = (int)($argv[1] ?? 2000);

// ── Distributions ──

$countries = [
    'RU' => 25, 'KZ' => 10, 'UA' => 8, 'BY' => 5,
    'US' => 12, 'DE' => 8, 'FR' => 5, 'BR' => 7,
    'IN' => 6, 'TR' => 5, 'PL' => 4, 'IT' => 3, 'ES' => 2,
];

$languages = [
    'ru' => 30, 'en' => 25, 'de' => 8, 'fr' => 5, 'pt' => 7,
    'tr' => 5, 'pl' => 4, 'it' => 3, 'es' => 3, 'kk' => 5, 'hi' => 5,
];

$oses = [
    'Android' => 45, 'iOS' => 25, 'Windows' => 20, 'macOS' => 8, 'Linux' => 2,
];

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
    'Samsung' => ['Galaxy S23', 'Galaxy S22', 'Galaxy A54', 'Galaxy A14', 'Galaxy S21'],
    'Xiaomi' => ['Redmi Note 12', 'Redmi Note 11', 'POCO X5', 'Mi 13', 'Redmi 12'],
    'Huawei' => ['P60', 'Nova 11', 'Mate 50'],
    'OPPO' => ['A78', 'Reno 10', 'Find X6'],
    'Realme' => ['11 Pro', 'C55', 'GT Neo 5'],
    'OnePlus' => ['11', 'Nord 3', '12'],
    'Apple' => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'iPhone 15 Pro', 'iPad Air'],
];

$clients = [
    'Chrome' => 40, 'Safari' => 20, 'Firefox' => 8, 'Samsung Browser' => 10,
    'Edge' => 7, 'Opera' => 5, 'Yandex Browser' => 8, 'UC Browser' => 2,
];

$clientVersions = [
    'Chrome' => [119.0, 120.0, 121.0, 122.0],
    'Safari' => [16.0, 17.0, 17.1],
    'Firefox' => [120.0, 121.0, 122.0],
    'Samsung Browser' => [23.0, 24.0],
    'Edge' => [119.0, 120.0, 121.0],
    'Opera' => [104.0, 105.0],
    'Yandex Browser' => [23.0, 24.0],
    'UC Browser' => [15.0, 16.0],
];

$isps = [
    'MTS' => 12, 'Beeline' => 10, 'Megafon' => 8, 'Tele2' => 6,
    'Comcast' => 8, 'AT&T' => 6, 'Verizon' => 5, 'T-Mobile' => 5,
    'Deutsche Telekom' => 4, 'Vodafone' => 5, 'Orange' => 4,
    'Rostelecom' => 5, 'Kazakhtelecom' => 3, 'Claro' => 4,
    'Jio' => 5, 'Turkcell' => 4, 'Play' => 3, 'TIM' => 3,
];

$flows = [
    'Flow 1' => 40,
    'Flow 2' => 30,
    'Flow 3' => 20,
    'Flow CIS' => 10,
];

$prelands = ['pre_quiz', 'pre_article', 'pre_video', 'pre_news', 'pre_review'];
$landings = ['land_order', 'land_form', 'land_call', 'land_shop'];

// Status distribution: null=60%, Lead=15%, Purchase=12%, Reject=8%, Trash=5%
$statuses = [
    null => 60, 'Lead' => 15, 'Purchase' => 12, 'Reject' => 8, 'Trash' => 5,
];

$subParams = [
    'utm_source' => ['facebook', 'google', 'tiktok', 'vk', 'mytarget', 'yandex'],
    'utm_medium' => ['cpc', 'cpm', 'social', 'display'],
    'utm_campaign' => ['camp_001', 'camp_002', 'camp_003', 'camp_winter', 'camp_spring'],
    'clickid' => null, // will be generated
    'fbclid' => null,
];

// ── Helpers ──

function weighted_random(array $items): string {
    $total = array_sum($items);
    $r = mt_rand(1, $total);
    $cumulative = 0;
    foreach ($items as $item => $weight) {
        $cumulative += $weight;
        if ($r <= $cumulative) return (string)$item;
    }
    return (string)array_key_first($items);
}

function random_ip(): string {
    return mt_rand(1, 223) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);
}

function random_ua(string $os, string $client, float $clientVer): string {
    $platform = match($os) {
        'Android' => 'Linux; Android ' . mt_rand(11, 14) . '; Mobile',
        'iOS' => 'iPhone; CPU iPhone OS ' . mt_rand(15, 17) . '_0 like Mac OS X',
        'Windows' => 'Windows NT 10.0; Win64; x64',
        'macOS' => 'Macintosh; Intel Mac OS X 10_15_7',
        'Linux' => 'X11; Linux x86_64',
        default => 'Unknown',
    };
    return "Mozilla/5.0 ($platform) AppleWebKit/537.36 (KHTML, like Gecko) $client/$clientVer Safari/537.36";
}

function random_subid(): string {
    return bin2hex(random_bytes(8));
}

function random_string(int $len): string {
    return substr(bin2hex(random_bytes($len)), 0, $len);
}

// ── Generate ──

echo "Opening database: $dbPath\n";
$db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
$db->busyTimeout(5000);

// Check which campaigns exist
$res = $db->query("SELECT id, name FROM campaigns");
$campaigns = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $campaigns[] = $row;
}

if (empty($campaigns)) {
    echo "No campaigns found. Creating a test campaign...\n";
    $defaultSettings = file_get_contents(__DIR__ . '/../../code/db/default.json');
    $db->exec("INSERT INTO campaigns (name, settings) VALUES ('Test Campaign', '" . SQLite3::escapeString($defaultSettings) . "')");
    $campaigns = [['id' => $db->lastInsertRowID(), 'name' => 'Test Campaign']];
}

echo "Found campaigns:\n";
foreach ($campaigns as $c) {
    echo "  #{$c['id']}: {$c['name']}\n";
}

// Use first campaign
$campId = $campaigns[0]['id'];
echo "\nSeeding $count clicks into campaign #{$campId}...\n";

$now = time();
$thirtyDaysAgo = $now - 30 * 86400;

// Pre-generate a pool of subids, ~20% will be reused
$subidPool = [];
$poolSize = (int)($count * 0.8);
for ($i = 0; $i < $poolSize; $i++) {
    $subidPool[] = random_subid();
}

$stmt = $db->prepare(
    "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, device, brand, model, isp, client, clientver, ua, subid, preland, land, flow, params, lpclick, status, cost, payout)
     VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :device, :brand, :model, :isp, :client, :clientver, :ua, :subid, :preland, :land, :flow, :params, :lpclick, :status, :cost, :payout)"
);

$stmtBlocked = $db->prepare(
    "INSERT INTO blocked (campaign_id, time, ip, country, lang, os, osver, device, brand, model, isp, client, clientver, ua, params, reason)
     VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :device, :brand, :model, :isp, :client, :clientver, :ua, :params, :reason)"
);

$blockReasons = ['Bot detected', 'IP blacklisted', 'Country not allowed', 'Referer mismatch', 'User-Agent blocked', 'Proxy detected'];

$db->exec('BEGIN TRANSACTION');

$inserted = 0;
$blocked = 0;
$statusCounts = ['null' => 0, 'Lead' => 0, 'Purchase' => 0, 'Reject' => 0, 'Trash' => 0];

for ($i = 0; $i < $count; $i++) {
    // Time: spread over 30 days, slightly more recent clicks
    $dayOffset = pow(mt_rand(0, 1000) / 1000, 0.7) * 30; // bias toward recent
    $clickTime = $thirtyDaysAgo + (int)($dayOffset * 86400) + mt_rand(0, 86399);

    $country = weighted_random($countries);
    $lang = weighted_random($languages);
    $os = weighted_random($oses);
    $osVer = $osVersions[$os][array_rand($osVersions[$os])];
    $deviceType = weighted_random($devices[$os]);
    $brand = weighted_random($brands[$os]);
    $model = '';
    if ($brand && isset($models[$brand])) {
        $model = $models[$brand][array_rand($models[$brand])];
    }
    $client = weighted_random($clients);
    $clientVer = $clientVersions[$client][array_rand($clientVersions[$client])];
    $isp = weighted_random($isps);
    $ip = random_ip();
    $ua = random_ua($os, $client, $clientVer);

    // ~15% of traffic gets blocked
    if (mt_rand(1, 100) <= 15) {
        $params = json_encode([
            'utm_source' => $subParams['utm_source'][array_rand($subParams['utm_source'])],
        ]);
        $stmtBlocked->bindValue(':campaign_id', $campId, SQLITE3_INTEGER);
        $stmtBlocked->bindValue(':time', $clickTime, SQLITE3_INTEGER);
        $stmtBlocked->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':country', $country, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':lang', $lang, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':os', $os, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':osver', $osVer, SQLITE3_FLOAT);
        $stmtBlocked->bindValue(':device', $deviceType, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':brand', $brand, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':model', $model, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':isp', $isp, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':client', $client, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':clientver', $clientVer, SQLITE3_FLOAT);
        $stmtBlocked->bindValue(':ua', $ua, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':params', $params, SQLITE3_TEXT);
        $stmtBlocked->bindValue(':reason', $blockReasons[array_rand($blockReasons)], SQLITE3_TEXT);
        $stmtBlocked->execute();
        $blocked++;
        continue;
    }

    // Subid: 80% unique, 20% reuse from pool
    $subid = mt_rand(1, 100) <= 20
        ? $subidPool[mt_rand(0, count($subidPool) - 1)]
        : random_subid();

    $flow = weighted_random($flows);
    $preland = $prelands[array_rand($prelands)];
    $land = $landings[array_rand($landings)];
    $lpclick = mt_rand(1, 100) <= 40 ? 1 : 0;
    $status = weighted_random($statuses);
    if ($status === '') $status = null;

    $statusKey = $status ?? 'null';
    $statusCounts[$statusKey]++;

    // Cost per click: 0.02 - 0.15
    $cost = round(mt_rand(2, 15) / 100, 2);

    // Payout: only for Purchase
    $payout = 0;
    if ($status === 'Purchase') {
        $payout = round(mt_rand(500, 5000) / 100, 2); // 5.00 - 50.00
    }

    // Params
    $paramsArr = [
        'utm_source' => $subParams['utm_source'][array_rand($subParams['utm_source'])],
        'utm_medium' => $subParams['utm_medium'][array_rand($subParams['utm_medium'])],
        'utm_campaign' => $subParams['utm_campaign'][array_rand($subParams['utm_campaign'])],
        'clickid' => random_string(16),
    ];
    if (mt_rand(1, 100) <= 30) {
        $paramsArr['fbclid'] = random_string(24);
    }
    $params = json_encode($paramsArr);

    $stmt->bindValue(':campaign_id', $campId, SQLITE3_INTEGER);
    $stmt->bindValue(':time', $clickTime, SQLITE3_INTEGER);
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->bindValue(':country', $country, SQLITE3_TEXT);
    $stmt->bindValue(':lang', $lang, SQLITE3_TEXT);
    $stmt->bindValue(':os', $os, SQLITE3_TEXT);
    $stmt->bindValue(':osver', $osVer, SQLITE3_FLOAT);
    $stmt->bindValue(':device', $deviceType, SQLITE3_TEXT);
    $stmt->bindValue(':brand', $brand, SQLITE3_TEXT);
    $stmt->bindValue(':model', $model, SQLITE3_TEXT);
    $stmt->bindValue(':isp', $isp, SQLITE3_TEXT);
    $stmt->bindValue(':client', $client, SQLITE3_TEXT);
    $stmt->bindValue(':clientver', $clientVer, SQLITE3_FLOAT);
    $stmt->bindValue(':ua', $ua, SQLITE3_TEXT);
    $stmt->bindValue(':subid', $subid, SQLITE3_TEXT);
    $stmt->bindValue(':preland', $preland, SQLITE3_TEXT);
    $stmt->bindValue(':land', $land, SQLITE3_TEXT);
    $stmt->bindValue(':flow', $flow, SQLITE3_TEXT);
    $stmt->bindValue(':params', $params, SQLITE3_TEXT);
    $stmt->bindValue(':lpclick', $lpclick, SQLITE3_INTEGER);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
    $stmt->bindValue(':payout', $payout, SQLITE3_FLOAT);
    $stmt->execute();
    $inserted++;

    if ($i % 500 === 0 && $i > 0) {
        echo "  ... $i / $count\n";
    }
}

$db->exec('COMMIT');
$db->close();

echo "\nDone!\n";
echo "  Allowed clicks inserted: $inserted\n";
echo "  Blocked clicks inserted: $blocked\n";
echo "  Status breakdown:\n";
foreach ($statusCounts as $s => $c) {
    echo "    $s: $c\n";
}
echo "\nData spans last 30 days. Open admin → Statistics to test.\n";
