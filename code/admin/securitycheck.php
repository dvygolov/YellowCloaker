<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../logging.php';
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/accesscontrol.php';
require_once __DIR__ . '/../redirect.php';
require_once __DIR__ . '/../paths.php';

global $cloSettings;
$accessError = get_admin_access_error($_SERVER, $cloSettings);
if ($accessError !== null) {
    $debug = ($cloSettings['debug'] ?? false) === true;
    $response = get_admin_access_denial_response($accessError, $debug);

    if (!$debug) {
        @ini_set('display_errors', '0');
    }

    @add_log('warning', $accessError);
    http_response_code($response['status']);
    header('Content-Type: text/plain; charset=utf-8');
    echo $response['body'];
    die();
}
if (!check_password(false)) {
    $loginPath = get_admin_base_url() . "login.php";
    if (!str_contains($loginPath, $_SERVER['PHP_SELF'])) {
        redirect($loginPath);
        exit();
    }
}
