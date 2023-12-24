<?php

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/logging.php';

$subid = get_subid();
$name = $_POST['name'] ?? null;
$phone = $_POST['phone'] ?? null;
if (empty($name) || empty($phone)) {
    http_response_code(500);
    die("Name or Phone not set!");
}
$db = new Db();
$is_duplicate = $db->lead_is_duplicate($subid, $phone);
if ($is_duplicate === false)
    $db->add_lead($subid, $name, $phone, $cur_config);