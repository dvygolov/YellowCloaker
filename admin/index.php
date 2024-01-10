<?php
global $startdate, $enddate, $config;
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/initialization.php';
require_once __DIR__ . '/tablecolumns.php';

$filter = $_GET['filter'] ?? '';
$db = new Db();
switch ($filter) {
    case 'leads':
        $dataset = $db->get_leads($startdate->getTimestamp(), $enddate->getTimestamp(), $config);
        break;
    case 'blocked':
        $dataset = $db->get_white_clicks($startdate->getTimestamp(), $enddate->getTimestamp(), $config);
        break;
    case 'single':
        $click = $_GET['subid'] ?? '';
        $dataset = $db->get_single_click($click, $config);
        break;
    default:
        $dataset = $db->get_black_clicks($startdate->getTimestamp(), $enddate->getTimestamp(), $config);
        break;
}
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>

<body>
    <?php include "menu.php" ?>
    <div class="all-content-wrapper">
        <?php include "header.php" ?>
        <div id="clicks"></div>
    </div>
    <script>
        let tableData = <?= json_encode($dataset) ?>;
        let tableColumns = <?=get_columns($filter) ?>;
        let table = new Tabulator('#clicks', {
            layout: "fitColumns",
            columns: tableColumns,
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [25, 50, 100, 200, 500],
            paginationCounter: "rows",
            height: "100%",
            data: tableData,
        });
    </script>
</body>

</html>
