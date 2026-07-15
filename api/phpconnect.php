<?php
require_once __DIR__ . '/../logging.php';

if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'YellowTDS') === false) {
    add_error_log('PhpAPI: Attempt to access API with invalid user-agent', true);
    http_response_code(404);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    add_error_log('PhpAPI: Not a POST request', true);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    add_error_log('PhpAPI: Invalid JSON input ' . json_last_error_msg() . ': ' . $input, true);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

if (!isset($data['api_key']) || empty($data['api_key'])) {
    add_error_log('PhpAPI: No API key provided', true);
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
    error_log('YellowTDS API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'success' => false,
        'action' => 'white',
    ]);
}
