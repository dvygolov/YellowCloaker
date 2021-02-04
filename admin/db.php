<?php
require_once __DIR__."/../db/SleekDB.php";
require_once __DIR__."/../db/Store.php";
require_once __DIR__."/../db/QueryBuilder.php";
require_once __DIR__."/../db/Query.php";
require_once __DIR__."/../db/Cache.php";

function get_white_clicks($startdate,$enddate) {
    $dataDir = __DIR__ . "/../logs";
    $wclicksStore = new \SleekDB\Store("whiteclicks", $dataDir);
	return $wclicksStore->findBy([["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}

function get_black_clicks($startdate,$enddate) {
    $dataDir = __DIR__ . "/../logs";
    $bclicksStore = new \SleekDB\Store("blackclicks", $dataDir);
	return $bclicksStore->findBy([["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}

function get_leads($startdate,$enddate){
    $dataDir = __DIR__ . "/../logs";
    $leadsStore = new \SleekDB\Store("leads", $dataDir);
	return $leadsStore->findBy([["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}

function get_lpctr($startdate,$enddate){
    $dataDir = __DIR__ . "/../logs";
    $lpctrStore = new \SleekDB\Store("lpctr", $dataDir);
	return $lpctrStore->findBy([["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}
?>
