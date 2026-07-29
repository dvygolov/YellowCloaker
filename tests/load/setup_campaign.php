<?php
/**
 * Setup a test campaign for load testing.
 * Creates a campaign with multiple flows, diverse filters, and various white/black configurations.
 *
 * Usage: php tests/load/setup_campaign.php [port] [uniqueness]
 *   port — PHP dev server port (default: 8080)
 *   uniqueness — on or off (default: off)
 *
 * The campaign will be configured for localhost:<port> domain.
 */

require_once __DIR__ . '/../../code/db/db.php';
require_once __DIR__ . '/../../code/campaign.php';

global $db;

$port = $argv[1] ?? '8080';
$uniquenessEnabled = strtolower((string)($argv[2] ?? 'off')) === 'on';
$domain = "localhost:$port";

echo "=== YellowTDS Load Test Setup ===\n\n";

// Check if a loadtest campaign already exists
$campaigns = $db->get_campaigns(0, PHP_INT_MAX, ['clicks']);
$existingId = null;
foreach ($campaigns as $c) {
    if ($c['name'] === 'LoadTest Campaign') {
        $existingId = $c['id'];
        break;
    }
}

if ($existingId) {
    echo "Found existing LoadTest campaign (ID: $existingId). Updating settings...\n";
    $campId = $existingId;
} else {
    $campId = $db->add_campaign('LoadTest Campaign', false);
    if ($campId === false) {
        die("ERROR: Failed to create campaign\n");
    }
    echo "Created campaign ID: $campId\n";
}

// ── Build campaign settings ──

$settings = $db->get_campaign_settings($campId);

$settings['domains'] = [$domain, "127.0.0.1:$port"];
$settings['saveuserflow'] = false;
$settings['uniqueness'] = [
    'enabled' => $uniquenessEnabled,
    'method' => 'cookie_ip_ua',
    'ttl_hours' => 24,
    'get_parameter' => '',
];

// ── White settings ──
// Filter: block traffic from RU, BG, SG (these go to white)
// Also block known bot UAs via the built-in bot detection
$settings['white'] = [
    'action' => 'error',
    'folders' => [],
    'redirect' => [
        'type' => 302,
        'urls' => ['https://google.com']
    ],
    'curls' => [],
    'errorcodes' => ['200'],
    'domainfilter' => [
        'use' => false,
        'domains' => []
    ],
    'loadmode' => [],
    'filters' => [
        'condition' => 'OR',
        'rules' => [
            // Rule 1: Block specific countries
            // NOTE: localhost debug IP resolves to RU, so we exclude RU from this filter
            // to allow black clicks through during local testing
            [
                'id' => 'country',
                'field' => 'country',
                'type' => 'string',
                'input' => 'text',
                'operator' => 'in',
                'value' => 'CN,KP,IR'
            ],
            // Rule 2: Block bot user agents (click_params key is 'ua', but filter id must match standardParams key 'useragent')
            // NOTE: 'useragent' key doesn't exist in click_params (it's 'ua'), so this filter
            // triggers a PHP warning and never matches. Using 'client' (browser name) instead.
            [
                'id' => 'client',
                'field' => 'client',
                'type' => 'string',
                'input' => 'text',
                'operator' => 'contains',
                'value' => 'bot,crawl,spider'
            ],
            // Rule 3: Block specific ISPs (datacenter ranges)
            [
                'id' => 'isp',
                'field' => 'isp',
                'type' => 'string',
                'input' => 'text',
                'operator' => 'contains',
                'value' => 'Google,Amazon,Microsoft,DigitalOcean,Hetzner'
            ],
        ],
        'valid' => true
    ]
];

// ── Black settings ──
// JS bot detection OFF by default (separate scenario tests it)
// Multiple flows with different filter configurations

$settings['black'] = [
    'jsconnect' => 'redirect',
    'jsbotdetection' => [
        'enabled' => false,
        'events' => ['pointerdown', 'touchstart', 'audiocontext', 'timezone'],
        'timeout' => 5000,
        'timezone' => ['min' => -12, 'max' => 12]
    ],
    'flows' => [
        // Flow 1: RU traffic (debug IP resolves to RU), redirect landing (fastest black path)
        [
            'name' => 'RU Redirect',
            'filters' => [
                'condition' => 'AND',
                'rules' => [
                    [
                        'id' => 'country',
                        'field' => 'country',
                        'type' => 'string',
                        'input' => 'text',
                        'operator' => 'in',
                        'value' => 'RU'
                    ]
                ],
                'valid' => true
            ],
            'distribution' => 'equal',
            'optimize_for' => 'Lead',
            'optimize_mode' => 'funnels',
            'steps' => [
                [
                    'action' => 'redirect',
                    'folders' => [],
                    'redirect' => [
                        'urls' => [
                            ['url' => 'https://example.com/offer1', 'label' => 'offer1', 'weight' => 0],
                            ['url' => 'https://example.com/offer2', 'label' => 'offer2', 'weight' => 0],
                            ['url' => 'https://example.com/offer3', 'label' => 'offer3', 'weight' => 0]
                        ],
                        'type' => 302
                    ],
                ]
            ]
        ],
        // Flow 2: DE+GB traffic, redirect with multiple URLs (tests A/B distribution)
        [
            'name' => 'EU Redirect',
            'filters' => [
                'condition' => 'AND',
                'rules' => [
                    [
                        'id' => 'country',
                        'field' => 'country',
                        'type' => 'string',
                        'input' => 'text',
                        'operator' => 'in',
                        'value' => 'DE,GB,FR,IT,ES,PL'
                    ]
                ],
                'valid' => true
            ],
            'distribution' => 'equal',
            'optimize_for' => 'Lead',
            'optimize_mode' => 'funnels',
            'steps' => [
                [
                    'action' => 'redirect',
                    'folders' => [],
                    'redirect' => [
                        'urls' => [
                            ['url' => 'https://example.com/eu-offer1', 'label' => 'eu-offer1', 'weight' => 0],
                            ['url' => 'https://example.com/eu-offer2', 'label' => 'eu-offer2', 'weight' => 0]
                        ],
                        'type' => 302
                    ],
                ]
            ]
        ],
        // Flow 3: Catch-all (no filters), redirect landing
        [
            'name' => 'Catch All',
            'filters' => (object)[],
            'distribution' => 'equal',
            'optimize_for' => 'Lead',
            'optimize_mode' => 'funnels',
            'steps' => [
                [
                    'action' => 'redirect',
                    'folders' => [],
                    'redirect' => [
                        'urls' => [
                            ['url' => 'https://example.com/global-offer', 'label' => 'global-offer', 'weight' => 100]
                        ],
                        'type' => 302
                    ],
                ]
            ]
        ]
    ]
];

// ── Scripts ──
$settings['scripts'] = [
    'backfix' => ['use' => false, 'urls' => []],
    'prelandingreplace' => ['use' => false, 'url' => ''],
    'landingreplace' => ['use' => false, 'url' => ''],
    'imageslazyload' => false
];

// ── Postback ──
$settings['postback'] = [
    'events' => [
        'lead' => 'Lead',
        'purchase' => 'Purchase',
        'reject' => 'Reject',
        'trash' => 'Trash'
    ],
    's2s' => []
];

// ── Statistics ──
$settings['statistics'] = [
    'timezone' => 'Europe/Moscow',
    'allowed' => [],
    'leads' => [],
    'blocked' => [],
    'tables' => [
        [
            'name' => 'Date',
            'columns' => [
                ['field' => 'date', 'width' => -1],
                ['field' => 'clicks', 'width' => -1],
                ['field' => 'uniques', 'width' => -1],
            ],
            'groupby' => ['date'],
            'filters' => [],
            'orderby' => []
        ]
    ]
];

$saved = $db->save_campaign_settings($campId, $settings, false, false);
if (!$saved) {
    die("ERROR: Failed to save campaign settings\n");
}
if (!$db->rebuild_runtime_cache()) {
    die("ERROR: Failed to rebuild runtime campaign cache\n");
}

echo "Campaign configured for domain: $domain\n";
echo "White filters: country IN (CN,KP,IR) OR bot UA OR datacenter ISP\n";
echo "Black flows:\n";
echo "  1. 'RU Redirect' — country=RU, 3 redirect URLs\n";
echo "  2. 'EU Redirect' — country=DE,GB,FR,IT,ES,PL, 2 redirect URLs\n";
echo "  3. 'Catch All'   — no filters, 1 redirect URL\n";
echo "JS Bot Detection: DISABLED (enable via separate scenario)\n";
echo 'Uniqueness counting: ' . ($uniquenessEnabled ? 'ENABLED' : 'DISABLED') . "\n";
echo "\nSetup complete! Campaign ID: $campId\n";
echo "\nTo start the server:\n";
echo "  php -S localhost:$port -t code/\n";
echo "\nTo run load tests:\n";
echo "  k6 run tests/load/k6/scenarios/mixed.js\n";
