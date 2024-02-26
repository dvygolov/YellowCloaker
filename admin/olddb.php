<?php

require_once __DIR__ . "/db/Classes/IoHelper.php";
require_once __DIR__ . "/db/Store.php";
require_once __DIR__ . "/db/QueryBuilder.php";
require_once __DIR__ . "/db/Query.php";
require_once __DIR__ . "/db/Cache.php";
require_once __DIR__ . "/../db.php";

use SleekDB\Store;

class OldDb
{
    private $data_dir;
    private $white_clicks_store;
    private $black_clicks_store;
    private $leads_store;
    private $lpctr_store;

    public function __construct()
    {
        $this->data_dir = __DIR__ . "/../logs";
        $this->white_clicks_store = new Store("whiteclicks", $this->data_dir);
        $this->black_clicks_store = new Store("blackclicks", $this->data_dir);
        $this->leads_store = new Store("leads", $this->data_dir);
        $this->lpctr_store = new Store("lpctr", $this->data_dir);
    }

    public function get_white_clicks($config): array
    {
        return $this->white_clicks_store->findBy(
            [
                ["config", "=", $config]
            ],
            ["time" => "desc"]
        );
    }

    public function get_black_clicks($config): array
    {
        return $this->black_clicks_store->findBy(
            [
                ["config", "=", $config]
            ],
            ["time" => "desc"]
        );
    }

    public function get_single_click($subid, $config): array
    {
        if (empty($subid)) {
            return [];
        }
        return $this->black_clicks_store->findBy(
            [
                ["config", "=", $config],
                ["subid", "=", $subid]
            ]
        );
    }

    public function get_leads($startdate, $enddate, $config): array
    {
        return $this->leads_store->findBy(
            [
                ["config", "=", $config],
                ["time", ">=", $startdate], ["time", "<=", $enddate]
            ],
            ["time" => "desc"]
        );
    }

    public function get_lpctr($config): array
    {
        return $this->lpctr_store->findBy(
            [
                ["config", "=", $config]
            ],
            ["time" => "desc"]
        );
    }
}

$oldDb = new OldDb();
$clicks = $oldDb->get_white_clicks("default");
$db = new Db();
for ($i = 0; $i < count($clicks); $i++) {
    $db->add_white_click($clicks[$i], $clicks[$i]['reason'], "default");
}

$clicks = $oldDb->get_black_clicks("default");
for ($i = 0; $i < count($clicks); $i++) {
    $db->add_black_click($clicks[$i]['subid'],$clicks[$i], $clicks[$i]['preland'], $clicks[$i]['land'], "default");
}

$clicks = $oldDb->get_lpctr("default");
for ($i = 0; $i < count($clicks); $i++) {
    $db->add_lpctr($clicks[$i]['subid']);
}

echo 'All Done!';
