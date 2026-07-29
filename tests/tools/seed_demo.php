<?php
/**
 * Seed a demo campaign with Thompson Sampling data for steps[] model.
 *
 * Run: php seed_demo.php
 */

require_once __DIR__ . '/../../code/db/db.php';

global $db;

function demo_folder(string $name): array
{
    return [
        'name' => $name,
        'loadtype' => 'base',
        'weight' => 0,
        'mvt' => ['enabled' => false, 'tests' => []],
    ];
}

$campId = $db->add_campaign('Thompson Demo');
if ($campId === false) {
    die("Failed to create campaign\n");
}
echo "Created campaign ID: $campId\n";

$settings = $db->get_campaign_settings($campId);
$settings['domains'] = ['thompson-demo.local'];
$settings['black']['flows'] = [
    [
        'name' => 'Flow 1',
        'filters' => (object)[],
        'distribution' => 'thompson',
        'optimize_for' => 'Lead',
        'optimize_mode' => 'funnels',
        'steps' => [
            [
                'action' => 'folder',
                'folders' => array_map('demo_folder', ['pre_quiz', 'pre_article', 'pre_video']),
                'redirect' => ['urls' => [], 'type' => 302],
            ],
            [
                'action' => 'folder',
                'folders' => array_map('demo_folder', ['land_shop', 'land_form', 'land_order', 'land_call']),
                'redirect' => ['urls' => [], 'type' => 302],
            ]
        ]
    ],
    [
        'name' => 'Flow 2',
        'filters' => (object)[],
        'distribution' => 'thompson',
        'optimize_for' => 'Lead',
        'optimize_mode' => 'separate',
        'steps' => [
            [
                'action' => 'folder',
                'folders' => array_map('demo_folder', ['pre_news', 'pre_review']),
                'redirect' => ['urls' => [], 'type' => 302],
            ],
            [
                'action' => 'folder',
                'folders' => array_map('demo_folder', ['land_main', 'land_alt', 'land_lite']),
                'redirect' => ['urls' => [], 'type' => 302],
            ]
        ]
    ]
];

if (!$db->save_campaign_settings($campId, $settings)) {
    die("Failed to save campaign settings\n");
}
echo "Saved campaign settings\n";

$funnelCR = [
    ['pre_quiz', 'land_shop', 0.12, 150],
    ['pre_quiz', 'land_form', 0.08, 120],
    ['pre_quiz', 'land_order', 0.06, 100],
    ['pre_quiz', 'land_call', 0.04, 80],
    ['pre_article', 'land_shop', 0.07, 130],
    ['pre_article', 'land_form', 0.05, 110],
    ['pre_article', 'land_order', 0.03, 90],
    ['pre_article', 'land_call', 0.02, 70],
    ['pre_video', 'land_shop', 0.04, 100],
    ['pre_video', 'land_form', 0.03, 90],
    ['pre_video', 'land_order', 0.02, 80],
    ['pre_video', 'land_call', 0.01, 60],
];

$separatePrelandCR = [
    ['pre_news', 0.09, 200],
    ['pre_review', 0.04, 180],
];

$separateLandCR = [
    ['land_main', 0.11, 250],
    ['land_alt', 0.07, 200],
    ['land_lite', 0.03, 150],
];

$countries = ['US', 'DE', 'GB', 'FR', 'BR', 'RU'];
$oses = ['Android', 'iOS', 'Windows'];
$devices = ['smartphone', 'tablet', 'desktop'];
$clients = ['Chrome', 'Safari', 'Firefox', 'Edge'];
$counter = 0;

function seed_path_clicks($db, int $campId, string $flowName, array $path, float $cr, int $count, array $countries, array $oses, array $devices, array $clients, int &$counter): void
{
    $conversions = (int)round($count * $cr);
    $converted = 0;

    for ($i = 0; $i < $count; $i++) {
        $counter++;
        $userid = 'demo_u_' . $campId . '_' . $counter;
        $clickid = generate_clickid($userid) . substr(md5((string)$counter), 0, 4);

        $data = [
            'ip' => mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254),
            'country' => $countries[array_rand($countries)],
            'lang' => 'en',
            'os' => $oses[array_rand($oses)],
            'osver' => mt_rand(10, 17) . '.' . mt_rand(0, 5),
            'device' => $devices[array_rand($devices)],
            'brand' => 'Brand' . mt_rand(1, 5),
            'model' => 'Model' . mt_rand(1, 10),
            'isp' => 'ISP' . mt_rand(1, 3),
            'client' => $clients[array_rand($clients)],
            'clientver' => mt_rand(90, 120) . '.0',
            'ua' => 'Mozilla/5.0',
            'cpc' => 0,
        ];

        if (!$db->add_black_click($userid, $clickid, $data, $path, $flowName, $campId)) {
            continue;
        }

        foreach ($path as $step => $variant) {
            $db->add_click_step($clickid, $step, $variant);
        }

        $shouldConvert = $converted < $conversions && mt_rand(1, 10000) <= (int)round($cr * 10000);
        if ($shouldConvert) {
            $conversion = $db->record_conversion(
                $clickid,
                $campId,
                'Lead',
                'Lead',
                'site_script',
                null,
                null,
                0.0,
                'USD',
                false,
                false,
                'reject'
            );
            if (!empty($conversion['accepted'])) {
                $converted++;
            }
        }
    }

    $label = implode(' + ', $path);
    echo "  $label: $count clicks, $converted leads\n";
}

echo "\nSeeding Flow 1 (funnels mode):\n";
foreach ($funnelCR as $row) {
    seed_path_clicks($db, $campId, 'Flow 1', [$row[0], $row[1]], $row[2], $row[3], $countries, $oses, $devices, $clients, $counter);
}

echo "\nSeeding Flow 2 (separate mode):\n";
echo "  Step 0 variants:\n";
foreach ($separatePrelandCR as $row) {
    seed_path_clicks($db, $campId, 'Flow 2', [$row[0], 'land_main'], $row[1], $row[2], $countries, $oses, $devices, $clients, $counter);
}

echo "  Step 1 variants:\n";
foreach ($separateLandCR as $row) {
    seed_path_clicks($db, $campId, 'Flow 2', ['pre_news', $row[0]], $row[1], $row[2], $countries, $oses, $devices, $clients, $counter);
}

echo "\nDone! Total clicks seeded: $counter\n";
echo "Open admin panel and inspect Thompson win probabilities.\n";
