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

/**
 * @throws IOException
 * @throws JsonException
 * @throws InvalidConfigurationException
 * @throws IdNotAllowedException
 * @throws InvalidArgumentException
 */
function add_white_click($data, $reason, $config)
{
    $dataDir = __DIR__ . "/logs";
    $wclicksStore = new Store("whiteclicks", $dataDir);

    parse_str($_SERVER['QUERY_STRING'], $queryarr);

    $click = [
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
    $wclicksStore->insert($click);
}

/**
 * @throws IOException
 * @throws JsonException
 * @throws InvalidConfigurationException
 * @throws InvalidArgumentException
 * @throws IdNotAllowedException
 */
function add_black_click($subid, $data, $preland, $land, $config)
{
    $dataDir = __DIR__ . "/logs";
    $bclicksStore = new Store("blackclicks", $dataDir);

    parse_str($_SERVER['QUERY_STRING'], $queryarr);

    $click = [
        "subid" => $subid,
        "time" => (new DateTime())->getTimestamp(),
        "ip" => $data['ip'],
        "country" => $data['country'],
        "os" => $data['os'],
        "isp" => str_replace(',', ' ', $data['isp']),
        "ua" => str_replace(',', ' ', $data['ua']),
        "subs" => $queryarr,
        "preland" => empty($preland) ? 'unknown' : $preland,
        "land" => empty($land) ? 'unknown' : $land,
        "config" => $config
    ];
    $bclicksStore->insert($click);
}

/**
 * @throws IOException
 * @throws JsonException
 * @throws InvalidConfigurationException
 * @throws InvalidArgumentException
 * @throws IdNotAllowedException
 */
function add_lead($subid, $name, $phone, $config, $status = 'Lead'): array
{
    $dataDir = __DIR__ . "/logs";
    $leadsStore = new Store("leads", $dataDir);

    $fbp = get_cookie('_fbp');
    $fbclid = get_cookie('fbclid');
    if ($fbclid === '') $fbclid = get_cookie('_fbc');

    if ($status == '') $status = 'Lead';

    $dt = new DateTime();
    $time = $dt->getTimestamp();

    $land = get_cookie('landing');
    if (empty($land)) $land = 'unknown';
    $preland = get_cookie('prelanding');
    if (empty($preland)) $preland = 'unknown';

    $lead = [
        "subid" => $subid,
        "time" => $time,
        "name" => $name,
        "phone" => $phone,
        "status" => $status,
        "fbp" => $fbp,
        "fbclid" => $fbclid,
        "preland" => $preland,
        "land" => $land,
        "config" => $config
    ];
    return $leadsStore->insert($lead);
}

/**
 * @throws IdNotAllowedException
 * @throws InvalidArgumentException
 * @throws IOException
 * @throws JsonException
 * @throws InvalidConfigurationException
 */
function update_lead($subid, $status, $payout): bool
{
    $dataDir = __DIR__ . "/logs";
    $leadsStore = new Store("leads", $dataDir);
    $lead = $leadsStore->findOneBy([["subid", "=", $subid]]);
    if ($lead === null) {
        $bclicksStore = new Store("blackclicks", $dataDir);
        $click = $bclicksStore->findOneBy([["subid", "=", $subid]]);
        if ($click === null) return false;
        $lead = add_lead($subid, '', '', $config);
    }

    $lead["status"] = $status;
    $lead["payout"] = $payout;
    $leadsStore->update($lead);
    return true;
}

/**
 * @throws IOException
 * @throws InvalidConfigurationException
 * @throws InvalidArgumentException
 */
function email_exists_for_subid($subid): bool
{
    $dataDir = __DIR__ . "/logs";
    $leadsStore = new Store("leads", $dataDir);
    $lead = $leadsStore->findOneBy([["subid", "=", $subid]]);
    return !($lead === null) && array_key_exists("email", $lead);
}

/**
 * @throws IOException
 * @throws InvalidConfigurationException
 * @throws InvalidArgumentException
 */
function add_email($subid, $email)
{
    $dataDir = __DIR__ . "/logs";
    $leadsStore = new Store("leads", $dataDir);
    $lead = $leadsStore->findOneBy([["subid", "=", $subid]]);
    if ($lead === null) return;
    $lead["email"] = $email;
    $leadsStore->update($lead);
}

/**
 * @throws IOException
 * @throws JsonException
 * @throws InvalidConfigurationException
 * @throws IdNotAllowedException
 * @throws InvalidArgumentException
 */
function add_lpctr($subid, $preland, $config)
{
    $dataDir = __DIR__ . "/logs";
    $lpctrStore = new Store("lpctr", $dataDir);
    $dt = new DateTime();
    $time = $dt->getTimestamp();

    $lpctr = [
        "time" => $time,
        "subid" => $subid,
        "preland" => $preland,
        "config" => $config
    ];
    $lpctrStore->insert($lpctr);
}

//проверяем, есть ли в файле лидов subid текущего пользователя
//если есть, и также есть такой же номер - значит ЭТО ДУБЛЬ!
//И нам не нужно слать его в ПП и не нужно показывать пиксель ФБ!!
/**
 * @throws IOException
 * @throws InvalidConfigurationException
 * @throws InvalidArgumentException
 */
function lead_is_duplicate($subid, $phone): bool
{
    $dataDir = __DIR__ . "/logs";
    $leadsStore = new Store("leads", $dataDir);
    if ($subid !== '') {
        $lead = $leadsStore->findOneBy([["subid", "=", $subid]]);
        if ($lead === null) return false;
        header("YWBDuplicate: We have this sub!");
        $phoneexists = ($lead["phone"] === $phone);
        if ($phoneexists) {
            header("YWBDuplicate: We have this phone!");
            return true;
        } else {
            return false;
        }
    } else {
        //если куки c subid у нас почему-то нет, то проверяем по номеру телефона
        $lead = $leadsStore->findOneBy([["phone", "=", $phone]]);
        return !($lead === null);
    }
}