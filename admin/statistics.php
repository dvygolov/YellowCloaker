<?php
require_once __DIR__ . '/passwordcheck.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../campaign.php';
require_once __DIR__ . '/campinit.php';

global $campId,$startdate, $enddate;

$db=new Db();
$s = $db->get_campaign_settings($campId);
$c = new Campaign($campId, $s);
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>

<body>
    <?php include "header.php" ?>
    <div class="all-content-wrapper">
        <?=show_stats($startdate,$enddate,$c->statistics);?>
    </div>
</body>

</html>
