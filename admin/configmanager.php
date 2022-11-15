<?php
require_once __DIR__ . '/password.php';
check_password();
$action = $_REQUEST['action'] ?? 'none';
$config = $_REQUEST['config'];
switch ($action) {
    case 'add':
        break;
    case 'del':
        break;
    default:
        break;
}
