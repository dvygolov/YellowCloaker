<?php
//Debug info start
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Debug info end

if (version_compare(phpversion(), '7.2.0', '<')) {
    die("PHP version should be 7.2 or higher! Change your PHP version and return.");
}
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/password.php';

check_password();

date_default_timezone_set($stats_timezone);
$startdate = isset($_GET['startdate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['startdate'], new DateTimeZone($stats_timezone)) :
    new DateTime("now", new DateTimeZone($stats_timezone));
$enddate = isset($_GET['enddate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['enddate'], new DateTimeZone($stats_timezone)) :
    new DateTime("now", new DateTimeZone($stats_timezone));
$startdate->setTime(0, 0, 0);
$enddate->setTime(23, 59, 59);

$date_str = '';
if (isset($_GET['startdate']) && isset($_GET['enddate'])) {
    $startstr = $_GET['startdate'];
    $endstr = $_GET['enddate'];
    $date_str = "&startdate={$startstr}&enddate={$endstr}";
}

$password = $_REQUEST['password'];
$config = $_REQUEST['config'] ?? "default";