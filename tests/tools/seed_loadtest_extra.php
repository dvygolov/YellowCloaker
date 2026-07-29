<?php
/**
 * Seed extra days (Feb 17-20) into the !LoadTest campaign (#38).
 * ~70k clicks per day, matching the existing Feb 16 data profile.
 *
 * Usage: php seed_loadtest_extra.php
 */

$dbPath = __DIR__ . '/../../code/db/clicks.db';
if (!file_exists($dbPath)) {
    die("ERROR: Database not found at $dbPath\n");
}

$db = new SQLite3($dbPath, SQLITE3_OPEN_READWRITE);
$db->busyTimeout(10000);
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('PRAGMA synchronous=NORMAL');

// ── Find !LoadTest campaign ──
$res = $db->query("SELECT id FROM campaigns WHERE name = '!LoadTest'");
$camp = $res->fetchArray(SQLITE3_ASSOC);
if (!$camp) {
    die("ERROR: !LoadTest campaign not found\n");
}
$campId = (int)$camp['id'];
echo "Campaign: #$campId (!LoadTest)\n";

// ── Check existing data ──
$res = $db->query("SELECT date(time, 'unixepoch') as d, count(*) as cnt FROM clicks WHERE campaign_id=$campId GROUP BY d ORDER BY d");
echo "Existing clicks:\n";
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    echo "  {$row['d']}: {$row['cnt']}\n";
}

// ── Configuration matching existing profile ──
$clicksPerDay = 70000;
$flow = 'RU Redirect';
$lands = [
    'https://example.com/offer1',
    'https://example.com/offer2',
    'https://example.com/offer3',
];

// OS distribution matching existing (~28% Android, ~25% Windows, ~21% iOS, ~21% Mac, ~2% iPadOS, ~1.3% GNU/Linux, ~1.3% Ubuntu)
$osWeights = [
    'Android'   => 28,
    'Windows'   => 25,
    'iOS'       => 21,
    'Mac'       => 21,
    'iPadOS'    => 2,
    'GNU/Linux' => 1,
    'Ubuntu'    => 1,
];

$osVersions = [
    'Android'   => ['11', '12', '13', '14'],
    'Windows'   => ['10', '11'],
    'iOS'       => ['16', '17', '17.1', '18'],
    'Mac'       => ['13', '14', '15'],
    'iPadOS'    => ['16', '17'],
    'GNU/Linux' => ['5.15', '6.1'],
    'Ubuntu'    => ['22.04', '24.04'],
];

$deviceMap = [
    'Android'   => 'mobile',
    'Windows'   => 'desktop',
    'iOS'       => 'mobile',
    'Mac'       => 'desktop',
    'iPadOS'    => 'tablet',
    'GNU/Linux' => 'desktop',
    'Ubuntu'    => 'desktop',
];

$brandMap = [
    'Android'   => ['Samsung', 'Xiaomi', 'Huawei', 'OPPO', 'Realme', 'OnePlus', 'Google'],
    'iOS'       => ['Apple'],
    'Mac'       => ['Apple'],
    'iPadOS'    => ['Apple'],
    'Windows'   => [''],
    'GNU/Linux' => [''],
    'Ubuntu'    => [''],
];

$modelMap = [
    'Samsung'  => ['Galaxy S23', 'Galaxy S22', 'Galaxy A54', 'Galaxy A14', 'Galaxy S24'],
    'Xiaomi'   => ['Redmi Note 12', 'Redmi Note 13', 'POCO X5', 'Mi 14'],
    'Huawei'   => ['P60', 'Nova 11', 'Mate 50'],
    'OPPO'     => ['A78', 'Reno 10', 'Find X6'],
    'Realme'   => ['11 Pro', 'C55', 'GT Neo 5'],
    'OnePlus'  => ['11', 'Nord 3', '12'],
    'Google'   => ['Pixel 8', 'Pixel 7a', 'Pixel 8 Pro'],
    'Apple'    => ['iPhone 15', 'iPhone 14', 'iPhone 13', 'iPhone 15 Pro', 'iPad Air', 'MacBook Pro'],
];

$clients = [
    'Chrome'          => 40,
    'Safari'          => 20,
    'Firefox'         => 8,
    'Samsung Browser' => 10,
    'Edge'            => 7,
    'Opera'           => 5,
    'Yandex Browser'  => 8,
    'UC Browser'      => 2,
];

$clientVersions = [
    'Chrome'          => ['119', '120', '121', '122'],
    'Safari'          => ['16', '17', '17.1'],
    'Firefox'         => ['120', '121', '122'],
    'Samsung Browser' => ['23', '24'],
    'Edge'            => ['119', '120', '121'],
    'Opera'           => ['104', '105'],
    'Yandex Browser'  => ['23', '24'],
    'UC Browser'      => ['15', '16'],
];

$isps = [
    'MTS' => 12, 'Beeline' => 10, 'Megafon' => 8, 'Tele2' => 6,
    'Rostelecom' => 8, 'ER-Telecom' => 6, 'TTK' => 5, 'Yota' => 4,
    'SkyNet' => 3, 'LLC Sip nis' => 5, 'MGTS' => 4, 'NetByNet' => 3,
    'MegaFon-Ural' => 3, 'Enforta' => 2, 'Summa Telecom' => 2,
];

$languages = [
    'en' => 40, 'ru' => 35, 'de' => 5, 'fr' => 5, 'uk' => 5,
    'be' => 3, 'kk' => 3, 'tt' => 2, 'zh' => 2,
];

// Status: match existing — mostly NULL, with some conversions to make stats interesting
// ~60% NULL, ~15% Lead, ~12% Purchase, ~8% Reject, ~5% Trash
$statusWeights = [
    ''         => 60,
    'Lead'     => 15,
    'Purchase' => 12,
    'Reject'   => 8,
    'Trash'    => 5,
];

// Days to seed: Feb 17-20, 2026
$days = [
    '2026-02-17' => mktime(0, 0, 0, 2, 17, 2026),
    '2026-02-18' => mktime(0, 0, 0, 2, 18, 2026),
    '2026-02-19' => mktime(0, 0, 0, 2, 19, 2026),
    '2026-02-20' => mktime(0, 0, 0, 2, 20, 2026),
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

function random_ip_ru(): string {
    // Common Russian IP ranges (first octets)
    $first = [2, 5, 31, 37, 46, 62, 77, 78, 79, 80, 81, 82, 83, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 109, 176, 178, 185, 188, 193, 194, 195, 212, 213, 217];
    return $first[array_rand($first)] . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);
}

function random_ua(string $os, string $client, string $clientVer): string {
    $platform = match($os) {
        'Android' => 'Linux; Android ' . mt_rand(11, 14) . '; Mobile',
        'iOS' => 'iPhone; CPU iPhone OS ' . mt_rand(16, 18) . '_0 like Mac OS X',
        'iPadOS' => 'iPad; CPU OS ' . mt_rand(16, 17) . '_0 like Mac OS X',
        'Windows' => 'Windows NT 10.0; Win64; x64',
        'Mac' => 'Macintosh; Intel Mac OS X 10_15_7',
        'GNU/Linux', 'Ubuntu' => 'X11; Linux x86_64',
        default => 'Unknown',
    };
    return "Mozilla/5.0 ($platform) AppleWebKit/537.36 (KHTML, like Gecko) $client/$clientVer.0.0 Safari/537.36";
}

// ── Prepare statement ──
$stmt = $db->prepare(
    "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, device, brand, model, isp, client, clientver, ua, userid, clickid, flow, path, step, params, status, cost, payout)
     VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :device, :brand, :model, :isp, :client, :clientver, :ua, :userid, :clickid, :flow, :path, :step, :params, :status, :cost, :payout)"
);
$stepStmt = $db->prepare(
    "INSERT INTO click_steps (clickid, step, variant, time) VALUES (:clickid, :step, :variant, :time)"
);

// ── Generate ──
$totalInserted = 0;

foreach ($days as $dayLabel => $dayStart) {
    $dayEnd = $dayStart + 86399;

    echo "\nSeeding $dayLabel (~$clicksPerDay clicks)...\n";
    $db->exec('BEGIN TRANSACTION');

    $dayInserted = 0;
    $statusCounts = ['' => 0, 'Lead' => 0, 'Purchase' => 0, 'Reject' => 0, 'Trash' => 0];

    for ($i = 0; $i < $clicksPerDay; $i++) {
        // Spread time across the day with realistic traffic pattern (more during daytime Moscow time)
        // Peak hours: 10:00-22:00 MSK = 07:00-19:00 UTC
        $hourBias = mt_rand(0, 100);
        if ($hourBias < 70) {
            // Peak hours (07:00 - 19:00 UTC) = seconds 25200..68400
            $secondOfDay = mt_rand(25200, 68400);
        } elseif ($hourBias < 90) {
            // Evening/morning (19:00-23:59 UTC) = 68400..86399
            $secondOfDay = mt_rand(68400, 86399);
        } else {
            // Night (00:00-07:00 UTC) = 0..25200
            $secondOfDay = mt_rand(0, 25200);
        }
        $clickTime = $dayStart + $secondOfDay;

        $os = weighted_random($osWeights);
        $osVer = $osVersions[$os][array_rand($osVersions[$os])];
        $device = $deviceMap[$os];
        $brands = $brandMap[$os];
        $brand = $brands[array_rand($brands)];
        $model = '';
        if ($brand && isset($modelMap[$brand])) {
            $model = $modelMap[$brand][array_rand($modelMap[$brand])];
        }
        $client = weighted_random($clients);
        $clientVer = $clientVersions[$client][array_rand($clientVersions[$client])];
        $isp = weighted_random($isps);
        $ip = random_ip_ru();
        $lang = weighted_random($languages);
        $ua = random_ua($os, $client, $clientVer);
        $userid = uniqid('lt_u_', true);
        $clickid = uniqid('lt_c_', true);
        $land = $lands[array_rand($lands)];
        $path = json_encode([$land]);

        $status = weighted_random($statusWeights);
        if ($status === '') $status = null;
        $statusCounts[$status ?? '']++;

        // Cost: ~$0.50 - $1.50 (matching avg ~$1.00)
        $cost = round(mt_rand(50, 150) / 100, 2);

        // Payout: only for Purchase ($10-$80)
        $payout = 0;
        if ($status === 'Purchase') {
            $payout = round(mt_rand(1000, 8000) / 100, 2);
        }

        $params = '[]';

        $stmt->bindValue(':campaign_id', $campId, SQLITE3_INTEGER);
        $stmt->bindValue(':time', $clickTime, SQLITE3_INTEGER);
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':country', 'RU', SQLITE3_TEXT);
        $stmt->bindValue(':lang', $lang, SQLITE3_TEXT);
        $stmt->bindValue(':os', $os, SQLITE3_TEXT);
        $stmt->bindValue(':osver', $osVer, SQLITE3_TEXT);
        $stmt->bindValue(':device', $device, SQLITE3_TEXT);
        $stmt->bindValue(':brand', $brand, SQLITE3_TEXT);
        $stmt->bindValue(':model', $model, SQLITE3_TEXT);
        $stmt->bindValue(':isp', $isp, SQLITE3_TEXT);
        $stmt->bindValue(':client', $client, SQLITE3_TEXT);
        $stmt->bindValue(':clientver', $clientVer, SQLITE3_TEXT);
        $stmt->bindValue(':ua', $ua, SQLITE3_TEXT);
        $stmt->bindValue(':userid', $userid, SQLITE3_TEXT);
        $stmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
        $stmt->bindValue(':flow', $flow, SQLITE3_TEXT);
        $stmt->bindValue(':path', $path, SQLITE3_TEXT);
        $stmt->bindValue(':step', 0, SQLITE3_INTEGER);
        $stmt->bindValue(':params', $params, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmt->bindValue(':payout', $payout, SQLITE3_FLOAT);
        $stmt->execute();

        $stepStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
        $stepStmt->bindValue(':step', 0, SQLITE3_INTEGER);
        $stepStmt->bindValue(':variant', $land, SQLITE3_TEXT);
        $stepStmt->bindValue(':time', $clickTime, SQLITE3_INTEGER);
        $stepStmt->execute();
        $dayInserted++;

        if ($dayInserted % 10000 === 0) {
            echo "  ... $dayInserted / $clicksPerDay\n";
        }
    }

    $db->exec('COMMIT');
    $totalInserted += $dayInserted;

    echo "  Done: $dayInserted clicks\n";
    echo "  Statuses: NULL={$statusCounts['']}, Lead={$statusCounts['Lead']}, Purchase={$statusCounts['Purchase']}, Reject={$statusCounts['Reject']}, Trash={$statusCounts['Trash']}\n";
}

echo "\n=== Total inserted: $totalInserted clicks ===\n";

// Final verification
$res = $db->query("SELECT date(time, 'unixepoch') as d, count(*) as cnt FROM clicks WHERE campaign_id=$campId GROUP BY d ORDER BY d");
echo "\nFinal clicks per day:\n";
$total = 0;
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    echo "  {$row['d']}: {$row['cnt']}\n";
    $total += $row['cnt'];
}
echo "Grand total: $total\n";

$db->close();
echo "\nDone!\n";
