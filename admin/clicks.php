<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/initialization.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/campinit.php';

global $startdate, $enddate, $campId;
$filter = $_GET['filter'] ?? '';
$db = new Db();
switch ($filter) {
    case 'leads':
        $dataset = $db->get_leads($startdate->getTimestamp(), $enddate->getTimestamp(), $campId);
        break;
    case 'blocked':
        $dataset = $db->get_white_clicks($startdate->getTimestamp(), $enddate->getTimestamp(), $campId);
        break;
    case 'single':
        $clickId = $_GET['subid'] ?? '';
        $dataset = $db->get_clicks_by_subid($clickId);
        break;
    default:
        $dataset = $db->get_black_clicks($startdate->getTimestamp(), $enddate->getTimestamp(), $campId);
        break;
}
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>

<body>
    <?php include "header.php" ?>
    <div class="all-content-wrapper">
        <div id="clicks"></div>
    </div>
    <script>
        let tableData = <?= json_encode($dataset); ?>;
        let tableColumns = <?= get_clicks_columns($filter, $stats_timezone); ?>;
        let table = new Tabulator('#clicks', {
            layout: "fitColumns",
            columns: tableColumns,
            pagination: "local",
            paginationSize: 50,
            paginationSizeSelector: [25, 50, 100, 200, 500],
            paginationCounter: "rows",
            height: "100%",
            data: tableData,
            columnDefaults:{
                tooltip:true,
            }
        });
    </script>
</body>

</html>
