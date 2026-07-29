<?php
// Include the YellowTDS client library
require_once __DIR__ . '/../../code/phpclient.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP TDS Test - Safe Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .params {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .method {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🟡 YellowTDS PHP Client Test</h1>

        <div class="test-info">
            <h3>Test Information</h3>
            <p><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>Your IP:</strong> <?= $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ?></p>
            <p><strong>User Agent:</strong> <?= $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown' ?></p>
            <p><strong>Referer:</strong> <?= $_SERVER['HTTP_REFERER'] ?? 'Direct visit' ?></p>
        </div>

        <h2>Testing Methods</h2>

        <div class="method">
            <h3>Method 1: Simple Check (Recommended)</h3>
            <p>Use this for most cases - one line of code:</p>
            <div class="params">
// Add the client at the top of your PHP file.<br>
// It registers the YellowTDS check automatically:<br>
require_once __DIR__ . '/phpclient.php';
            </div>
        </div>

        <div class="method">
            <h3>Method 2: Immediate Check</h3>
            <div class="params">
// Run the check immediately when automatic shutdown handling is not desired:<br>
$client = new YellowTDSClient();<br>
$client-&gt;check();
            </div>
        </div>

        <div class="method">
            <h3>Method 3: Manual Processing</h3>
            <div class="params">
// For advanced control over the process:<br>
$client = new YellowTDSClient();<br>
$response = $client-&gt;connect();<br>
$client-&gt;process($response);
            </div>
        </div>

        <h2>Integration Example</h2>
        <p>To integrate into your existing site, add this to the top of your index.php:</p>
        <div class="params">
&lt;?php<br>
require_once __DIR__ . '/phpclient.php';<br>
// The client checks the request automatically at shutdown.<br>
// Your normal page content continues here...<br>
?&gt;
        </div>

        <h2>Current Request Parameters</h2>
        <div class="params">
            <?php
            echo "GET Parameters:\n";
            print_r($_GET);
            echo "\nSERVER Parameters (relevant):\n";
            $relevant_server = [
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
                'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? '',
                'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '',
                'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? '',
                'HTTP_ACCEPT_LANGUAGE' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''
            ];
            print_r($relevant_server);
            ?>
        </div>

        <h2>Test the Connection</h2>
        <p>Click the button below to test the YellowTDS connection:</p>

        <form method="post" style="margin: 20px 0;">
            <input type="hidden" name="test_connection" value="1">
            <button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                Test TDS Connection
            </button>
        </form>

        <?php
        // Test connection when form is submitted
        if (isset($_POST['test_connection'])) {
            echo '<div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;">';
            echo '<h3>🧪 Connection Test Results</h3>';

        }
        ?>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <p><strong>Note:</strong> This is a test page showing the SAFE PAGE content. In a real implementation, if YellowTDS selects an offer funnel, the visitor sees the configured funnel page instead.</p>
            <p><strong>API Key:</strong> Make sure to replace 'your-campaign-api-key-here' with your actual campaign API key.</p>
        </div>
    </div>
</body>
</html>
