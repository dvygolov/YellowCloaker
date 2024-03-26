<?php
global $startdate, $enddate, $config, $stats_timezone;
require_once __DIR__ . '/initialization.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/../settings.php';

?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>

<body>
    <?php include "menu.php" ?>
    <div class="all-content-wrapper">
        <?php include "header.php" ?>
        <?=show_stats($startdate,$enddate,$stats_timezone,$config);?>
    </div>
</body>

</html>
