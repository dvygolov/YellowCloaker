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
        $click = $_GET['subid'] ?? '';
        $dataset = $db->get_single_click($click, $config);
        break;
    default:
        $header = ["Subid", "IP", "Country", "ISP", "Time", "OS", "UA", "Subs", "Preland", "Land"];
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
        let table = new Tabulator('#clicks', {
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 25,
            paginationSizeSelector: [25, 50, 100, 200, 500],
            paginationCounter: "rows",
            height: "100%",
            data: tableData,
            autoColumns: true,
            autoColumnsDefinitions: {
                _id: {
                    title: "#",
                    formatter: "rownum"
                },
                config: {
                    visible: false
                },
                fbp: {
                    visible: false
                },
                fbclid: {
                    visible: false
                },
                reason: {
                    visible: window.location.href.includes("filter=blocked")
                },
                subid: {
                    formatter: "link",
                    formatterParams: {
                        urlPrefix: "index.php?filter=single&subid="
                    }
                }
            },
        });
    </script>
</body>

</html>
