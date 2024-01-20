<?php
require_once __DIR__ . '/../debug.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../redirect.php';

if (!check_password(false)) {
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));
    $redirectUrl = '/' . $currentDir . '/login.php';
    header('Location: ' . $redirectUrl);
    exit;
}

date_default_timezone_set($stats_timezone);
$dtz = new DateTimeZone($stats_timezone);
$startdate = isset($_GET['startdate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['startdate'], $dtz) :
    new DateTime("now", $dtz);
$enddate = isset($_GET['enddate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['enddate'], $dtz) :
    new DateTime("now", $dtz);
$startdate->setTime(0, 0, 0);
$enddate->setTime(23, 59, 59);

$date_str = '';
if (isset($_GET['startdate']) && isset($_GET['enddate'])) {
    $startstr = $_GET['startdate'];
    $endstr = $_GET['enddate'];
    $date_str = "&startdate=$startstr&enddate=$endstr";
}

$config = $_REQUEST['config'] ?? "default";