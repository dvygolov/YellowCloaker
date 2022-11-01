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

$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

$dataDir = __DIR__ . "/../logs";
switch ($filter) {
    case '':
        $header = ["Subid", "IP", "Country", "ISP", "Time", "OS", "UA", "QueryString", "Preland", "Land"];
        $dataset = get_black_clicks($startdate->getTimestamp(), $enddate->getTimestamp());
        break;
    case 'leads':
        $header = ["Subid", "Time", "Name", "Phone", "Email", "Status", "Preland", "Land", "Fbp", "Fbclid"];
        $dataset = get_leads($startdate->getTimestamp(), $enddate->getTimestamp());
        break;
    case 'blocked':
        $header = ["IP", "Country", "ISP", "Time", "Reason", "OS", "UA", "QueryString"];
        $dataset = get_white_clicks($startdate->getTimestamp(), $enddate->getTimestamp());
        break;
}

//Open the table tag
$tableOutput = "<TABLE class='table w-auto table-striped'>";
//Print the table header
$tableOutput .= "<thead class='thead-dark'>";
$tableOutput .= "<TR>";
$tableOutput .= "<TH scope='col'>Row</TH>";
foreach ($header as $field) {
    $tableOutput .= "<TH scope='col'>" . $field . "</TH>";
} //Add the columns
$tableOutput .= "</TR></thead><tbody>";
$countLines = 0;
foreach ($dataset as $line) {
    $countLines++;
    $tableOutput .= "<TR><TD>" . $countLines . "</TD>";
    $i = 0;
    switch ($filter) {
        case '':
            $tableOutput .= "<TD><a name='" . $line['subid'] . "'>" . $line['subid'] . "</a></TD>";
            $tableOutput .= "<TD>" . $line['ip'] . "</TD>";
            $tableOutput .= "<TD>" . $line['country'] . "</TD>";
            $tableOutput .= "<TD>" . $line['isp'] . "</TD>";
            $tableOutput .= "<TD>" . date('Y-m-d H:i:s', $line['time']) . "</TD>";
            $tableOutput .= "<TD>" . $line['os'] . "</TD>";
            $tableOutput .= "<TD>" . $line['ua'] . "</TD>";
            $tableOutput .= "<TD>" . http_build_query($line['subs']) . "</TD>";
            $tableOutput .= "<TD>" . $line['preland'] . "</TD>";
            $tableOutput .= "<TD>" . $line['land'] . "</TD>";
            break;
        case 'blocked':
            $tableOutput .= "<TD>" . $line['ip'] . "</TD>";
            $tableOutput .= "<TD>" . $line['country'] . "</TD>";
            $tableOutput .= "<TD>" . $line['isp'] . "</TD>";
            $tableOutput .= "<TD>" . date('Y-m-d H:i:s', $line['time']) . "</TD>";
            $tableOutput .= "<TD>" . implode(',', $line['reason']) . "</TD>";
            $tableOutput .= "<TD>" . $line['os'] . "</TD>";
            $tableOutput .= "<TD>" . $line['ua'] . "</TD>";
            $tableOutput .= "<TD>" . http_build_query($line['subs']) . "</TD>";
            break;
        case 'leads':
            $tableOutput .= "<TD><a href='index.php?password=" . $_GET['password'] . ($date_str !== '' ? $date_str : '') . "#" . $line['subid'] . "'>" . $line['subid'] . "</a></TD>";
            $tableOutput .= "<TD>" . date('Y-m-d H:i:s', $line['time']) . "</TD>";
            $tableOutput .= "<TD>" . $line['name'] . "</TD>";
            $tableOutput .= "<TD>" . $line['phone'] . "</TD>";
            $tableOutput .= "<TD>" . (empty($line['email']) ? 'no' : $line['email']) . "</TD>";
            $tableOutput .= "<TD>" . $line['status'] . "</TD>";
            $tableOutput .= "<TD>" . $line['preland'] . "</TD>";
            $tableOutput .= "<TD>" . $line['land'] . "</TD>";
            $tableOutput .= "<TD>" . $line['fbp'] . "</TD>";
            $tableOutput .= "<TD>" . $line['fbclid'] . "</TD>";
            break;
    }
    $tableOutput .= "</TR>";
}

$tableOutput .= "</tbody></TABLE>";
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>
<body>
<?php include "menu.php" ?>
<div class="all-content-wrapper">
    <?php include "header.php" ?>
    <a name="top"></a>
    <?= isset($tableOutput) ? $tableOutput : '' ?>
    <a name="bottom"></a>
</div>
</body>
</html>