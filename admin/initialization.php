<?php
require_once __DIR__ . '/../debug.php';

if (version_compare(phpversion(), '7.2.0', '<')) {
    die("PHP version should be 7.2 or higher! Change your PHP version and return.");
}
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/password.php';

check_password();

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

$password = $_REQUEST['password'];
$config = $_REQUEST['config'] ?? "default";