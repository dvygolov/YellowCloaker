<?php
global $startdate, $enddate, $config;
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/initialization.php';

$filter = $_GET['filter'] ?? '';

$db = new Db();
switch ($filter) {
    case 'leads':
        $header = ["Subid", "Time", "Name", "Phone", "Status", "Preland", "Land"];
        $dataset = $db->get_leads($startdate->getTimestamp(), $enddate->getTimestamp(), $config);
        break;
    case 'blocked':
        $header = ["IP", "Country", "ISP", "Time", "Reason", "OS", "UA", "Subs"];
        $dataset = $db->get_white_clicks($startdate->getTimestamp(), $enddate->getTimestamp(), $config);
        break;
    case 'single':
        $header = ["Subid", "IP", "Country", "ISP", "Time", "OS", "UA", "Subs", "Preland", "Land"];
        $click = $_GET['subid']??'';
        $dataset = $db->get_single_click($click, $config);
        break;
    default:
        $header = ["Subid", "IP", "Country", "ISP", "Time", "OS", "UA", "Subs", "Preland", "Land"];
        $dataset = $db->get_black_clicks($startdate->getTimestamp(), $enddate->getTimestamp(), $config);
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
