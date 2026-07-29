<?php

require_once __DIR__ . '/settings.php';

global $cloSettings;
$adminPath = trim((string)($cloSettings['adminPath'] ?? ''), "/ \t\n\r\0\x0B");
if (preg_match('/^[A-Za-z0-9_-]{1,64}$/', $adminPath) !== 1) {
    http_response_code(404);
    exit('Not Found');
}

$accessControlFile = __DIR__ . DIRECTORY_SEPARATOR . $adminPath . DIRECTORY_SEPARATOR . 'accesscontrol.php';
if (!is_file($accessControlFile)) {
    http_response_code(404);
    exit('Not Found');
}
require_once $accessControlFile;

$redirectPath = get_admin_shortcut_redirect($_SERVER, $cloSettings);

if ($redirectPath === null) {
    http_response_code(404);
    exit('Not Found');
}

header('Location: ' . $redirectPath, true, 302);
exit();
