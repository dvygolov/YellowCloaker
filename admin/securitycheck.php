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
    add_log('warning', $accessError);
    if ($cloSettings['debug'] === true) {
        echo $accessError;
    } else {
        http_response_code(404);
    }
    die();
}
if (!check_password(false)) {
    $loginPath = get_admin_base_url() . "login.php";
    if (!str_contains($loginPath, $_SERVER['PHP_SELF'])) {
        redirect($loginPath);
        exit();
    }
}
