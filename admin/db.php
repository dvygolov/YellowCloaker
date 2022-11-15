<?php
use SleekDB\Store;

require_once __DIR__."/../db/SleekDB.php";
require_once __DIR__."/../db/Store.php";
require_once __DIR__."/../db/QueryBuilder.php";
require_once __DIR__."/../db/Query.php";
require_once __DIR__."/../db/Cache.php";

/**
 * @throws \SleekDB\Exceptions\IOException
 * @throws \SleekDB\Exceptions\InvalidConfigurationException
 * @throws \SleekDB\Exceptions\InvalidArgumentException
 */
function get_white_clicks($startdate, $enddate, $config): array
{
    $dataDir = __DIR__ . "/../logs";
    $wclicksStore = new Store("whiteclicks", $dataDir);
	return $wclicksStore->findBy([["config","=",$config],["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}

/**
 * @throws \SleekDB\Exceptions\IOException
 * @throws \SleekDB\Exceptions\InvalidConfigurationException
 * @throws \SleekDB\Exceptions\InvalidArgumentException
 */
function get_black_clicks($startdate, $enddate, $config): array
{
    $dataDir = __DIR__ . "/../logs";
    $bclicksStore = new Store("blackclicks", $dataDir);
	return $bclicksStore->findBy([["config","=",$config],["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}

/**
 * @throws \SleekDB\Exceptions\IOException
 * @throws \SleekDB\Exceptions\InvalidConfigurationException
 * @throws \SleekDB\Exceptions\InvalidArgumentException
 */
function get_leads($startdate, $enddate, $config): array
{
    $dataDir = __DIR__ . "/../logs";
    $leadsStore = new Store("leads", $dataDir);
	return $leadsStore->findBy([["config","=",$config],["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}

/**
 * @throws \SleekDB\Exceptions\IOException
 * @throws \SleekDB\Exceptions\InvalidConfigurationException
 * @throws \SleekDB\Exceptions\InvalidArgumentException
 */
function get_lpctr($startdate, $enddate, $config): array
{
    $dataDir = __DIR__ . "/../logs";
    $lpctrStore = new Store("lpctr", $dataDir);
	return $lpctrStore->findBy([["config","=",$config],["time",">=",$startdate],["time","<=",$enddate]],["time"=>"desc"]);
}