<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/redirect') {
    header('Location: /headers', true, 302);
    exit;
}
if ($path === '/headers') {
    header('X-YWB-Test: captured');
    header('Set-Cookie: one=1', false);
    header('Set-Cookie: two=2', false);
    echo 'headers';
    exit;
}
if ($path === '/error') {
    http_response_code(503);
    echo 'unavailable';
    exit;
}

header('Content-Type: application/json');
echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'body' => file_get_contents('php://input'),
    'testHeader' => $_SERVER['HTTP_X_TEST'] ?? '',
]);
