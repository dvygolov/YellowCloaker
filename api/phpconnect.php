<?php
require_once __DIR__ . '/../logging.php';

if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'YellowTDS') === false) {
    ytds_log('warning', 'phpconnect-api', 'Attempt to access API with invalid user-agent', ['ip' => ytds_log_request_ip()]);
    http_response_code(404);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ytds_log('warning', 'phpconnect-api', 'Non-POST request', ['ip' => ytds_log_request_ip()]);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ytds_log('warning', 'phpconnect-api', 'Invalid JSON input: ' . json_last_error_msg(), ['ip' => ytds_log_request_ip()]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

if (!isset($data['api_key']) || empty($data['api_key'])) {
    ytds_log('warning', 'phpconnect-api', 'No API key provided', ['ip' => ytds_log_request_ip()]);
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../tds.php';
require_once __DIR__ . '/../actions.php';

try {
    $action = Tds::getPhpAction($data['api_key'], $data);
    $action->perform();
} catch (Exception $e) {
    ytds_log('error', 'phpconnect-api', $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'success' => false,
        'action' => 'white',
    ]);
}
