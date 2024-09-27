<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../redirect.php';

if (!check_password(false)) {
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    $redirectUrl = '/' . $currentDir . '/login.php';
    redirect($redirectUrl);
    exit;
}