<?php
require_once __DIR__ . "/db/Classes/IoHelper.php";
require_once __DIR__ . "/db/Store.php";
require_once __DIR__ . "/db/QueryBuilder.php";
require_once __DIR__ . "/db/Query.php";
require_once __DIR__ . "/db/Cache.php";
require_once __DIR__ . "/cookies.php";

use SleekDB\Exceptions\IdNotAllowedException;
use SleekDB\Exceptions\InvalidConfigurationException;
use SleekDB\Exceptions\IOException;
use SleekDB\Exceptions\JsonException;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Store;

class Db
{
    private $data_dir;
    private $white_clicks_store;
    private $black_clicks_store;
    private $leads_store;
    private $lpctr_store;

    public function __construct()
    {
        $this->data_dir = __DIR__ . "/logs";
        $this->white_clicks_store = new Store("whiteclicks", $this->data_dir);
        $this->black_clicks_store = new Store("blackclicks", $this->data_dir);
        $this->leads_store = new Store("leads", $this->data_dir);
        $this->lpctr_store = new Store("lpctr", $this->data_dir);
    }

    public function get_white_clicks($startdate, $enddate, $orderby, $sortby, $config): array
    {
        if (empty($orderby)) $orderby='time';
        if (empty($sortby)) $sortby = 'desc';
        return $this->white_clicks_store->findBy([["config", "=", $config], ["time", ">=", $startdate], ["time", "<=", $enddate]], [$orderby => $sortby]);
    }

    public function get_black_clicks($startdate, $enddate, $config): array
    {
        return $this->black_clicks_store->findBy([["config", "=", $config], ["time", ">=", $startdate], ["time", "<=", $enddate]], ["time" => "desc"]);
    }

    public function get_single_click($subid, $config): array
    {
        return $this->black_clicks_store->findBy([["config", "=", $config], ["subid", "=", $subid]]);
    }

    public function get_leads($startdate, $enddate, $config): array
    {
        return $this->leads_store->findBy([["config", "=", $config], ["time", ">=", $startdate], ["time", "<=", $enddate]], ["time" => "desc"]);
    }

    public function get_lpctr($startdate, $enddate, $config): array
    {
        return $this->lpctr_store->findBy([["config", "=", $config], ["time", ">=", $startdate], ["time", "<=", $enddate]], ["time" => "desc"]);
    }

    public function add_white_click($data, $reason, $config)
    {
        $click = $this->prepare_click_data($data, $reason, $config);
        $this->white_clicks_store->insert($click);
    }

    public function add_black_click($subid, $data, $preland, $land, $config)
    {
        $click = $this->prepare_click_data($data, '', $config);
        $click['subid'] = $subid;
        $click['preland'] = empty($preland) ? 'unknown' : $preland;
        $click['land'] = empty($land) ? 'unknown' : $land;
        $this->black_clicks_store->insert($click);
    }

    public function add_lead($subid, $name, $phone, $config, $status = 'Lead')
    {
        $lead = [
            "subid" => $subid,
            "time" => (new DateTime())->getTimestamp(),
            "name" => $name,
            "phone" => $phone,
            "status" => $status ?: 'Lead',
            "fbp" => get_cookie('_fbp'),
            "fbclid" => get_cookie('fbclid') ?: get_cookie('_fbc'),
            "preland" => get_cookie('prelanding') ?: 'unknown',
            "land" => get_cookie('landing') ?: 'unknown',
            "config" => $config
        ];
        return $this->leads_store->insert($lead);
    }

    public function update_lead($subid, $status, $payout, $config): bool
    {
        $lead = $this->leads_store->findOneBy([["subid", "=", $subid]]);
        if ($lead === null) {
            $click = $this->black_clicks_store->findOneBy([["subid", "=", $subid]]);
            if ($click === null) return false;
            $lead = $this->add_lead($subid, '','', $config);
            $lead['preland'] = $click['preland']??'unknown';
            $lead['land'] = $click['land']??'unknown';
        }
        $lead["status"] = $status;
        $lead["payout"] = $payout;
        $this->leads_store->update($lead);
        return true;
    }

    public function email_exists_for_subid($subid)
    {
        $lead = $this->leads_store->findOneBy([["subid", "=", $subid]]);
        return !($lead === null) && array_key_exists("email", $lead);
    }

    public function add_email($subid, $email)
    {
        $lead = $this->leads_store->findOneBy([["subid", "=", $subid]]);
        if ($lead === null) return;
        $lead["email"] = $email;
        $this->leads_store->update($lead);
    }

    public function add_lpctr($subid, $preland, $config)
    {
        $lpctr = [
            "time" => (new DateTime())->getTimestamp(),
            "subid" => $subid,
            "preland" => $preland,
            "config" => $config
        ];
        $this->lpctr_store->insert($lpctr);
    }

    public function lead_is_duplicate($subid, $phone)
    {
        if ($subid !== '') {
            $lead = $this->leads_store->findOneBy([["subid", "=", $subid]]);
            if ($lead === null) return false;
            header("YWBDuplicate: We have this sub!");
            return $lead["phone"] === $phone;
        } else {
            $lead = $this->leads_store->findOneBy([["phone", "=", $phone]]);
            return !($lead === null);
        }
    }

    private function prepare_click_data($data, $reason, $config)
    {
        $queryarr = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryarr);
        }
        return [
            "time" => (new DateTime())->getTimestamp(),
            "ip" => $data['ip'],
            "country" => $data['country'],
            "os" => $data['os'],
            "isp" => str_replace(',', ' ', $data['isp']),
            "ua" => str_replace(',', ' ', $data['ua']),
            "reason" => $reason,
            "subs" => $queryarr,
            "config" => $config
        ];
    }
}
