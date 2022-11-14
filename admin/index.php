<?php
//Включение отладочной информации
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
//Конец включения отладочной информации
if (version_compare(phpversion(), '7.2.0', '<')) {
    die("PHP version should be 7.2 or higher! Change your PHP version and return.");
}
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/password.php';

check_password();
require_once __DIR__ . '/db.php';

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

$filter = $_GET['filter'] ?? '';

$dataDir = __DIR__ . "/../logs";
switch ($filter) {
    case '':
        $header = ["Subid", "IP", "Country", "ISP", "Time", "OS", "UA", "Subs", "Preland", "Land"];
        $dataset = get_black_clicks($startdate->getTimestamp(), $enddate->getTimestamp());
        break;
    case 'leads':
        $header = ["Subid", "Time", "Name", "Phone", "Email", "Status", "Preland", "Land", "Fbp", "Fbclid"];
        $dataset = get_leads($startdate->getTimestamp(), $enddate->getTimestamp());
        break;
    case 'blocked':
        $header = ["IP", "Country", "ISP", "Time", "Reason", "OS", "UA", "Subs"];
        $dataset = get_white_clicks($startdate->getTimestamp(), $enddate->getTimestamp());
        break;
}
require_once __DIR__ . '/tableformatter.php';
$tableOutput = create_table($header);
$tableOutput = fill_table($tableOutput, $header, $dataset);
$tableOutput = close_table($tableOutput);
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>
<body>
<?php include "menu.php" ?>
<div class="all-content-wrapper">
    <?php include "header.php" ?>
    <a id="top"></a>
    <?= $tableOutput ?? '' ?>
    <a id="bottom"></a>
</div>
</body>
</html>