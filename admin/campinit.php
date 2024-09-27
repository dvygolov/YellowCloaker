<?php
$campId = $_REQUEST['campId']??-1;
if ($campId===-1) die("Campaign Id not found in URL!");
//TODO:move getting campaign settings here
date_default_timezone_set($cloSettings['timezone']);
$dtz = new DateTimeZone($cloSettings['timezone']);

$startdate = isset($_GET['startdate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['startdate'], $dtz) :
    new DateTime("now", $dtz);
$enddate = isset($_GET['enddate']) ?
    DateTime::createFromFormat('d.m.y', $_GET['enddate'], $dtz) :
    new DateTime("now", $dtz);
$startdate->setTime(0, 0, 0);
$enddate->setTime(23, 59, 59);

?>