<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/initialization.php';

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