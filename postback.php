<?php
//Включение отладочной информации
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Конец включения отладочной информации

require_once __DIR__.'/settings.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/url.php';
require_once __DIR__.'/requestfunc.php';

$curLink = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$subid = $_REQUEST['subid'] ?? '';
if ($subid ===''){
    http_response_code(500);
    echo "No subid found! Url: $curLink";
    return;
}
$status = $_REQUEST['status'] ?? '';
if ($status ===''){
    http_response_code(500);
    echo "No status found! Url: $curLink";
    return;
}
$payout = $_REQUEST['payout'] ?? '';
if ($payout ===''){
    http_response_code(500);
    echo "No payout found! Url: $curLink";
    return;
}
$inner_status = '';
switch ($status) {
    case $lead_status_name:
        $inner_status = 'Lead';
        break;
    case $purchase_status_name:
        $inner_status = 'Purchase';
        break;
    case $reject_status_name:
        $inner_status = 'Reject';
        break;
    case $trash_status_name:
        $inner_status = 'Trash';
        break;
}
add_postback_log($subid, $inner_status, $payout);
$res = update_lead($subid, $inner_status, $payout);
process_s2s_posbacks($s2s_postbacks, $inner_status);

if ($res) {
    http_response_code(200);
    echo "Postback for subid $subid with status $status and payout $payout accepted.";
}
else {
    http_response_code(404);
    echo "Postback for subid $subid with status $status and payout $payout NOT accepted! Subid NOT FOUND.";
}

function process_s2s_posbacks(array $s2s_postbacks,string $inner_status)
{
    foreach ($s2s_postbacks as $s2s) {
        if (!in_array($inner_status, $s2s['events'])) continue;
        if (empty($s2s['url'])) continue;
        $final_url = replace_all_macros($s2s['url']);
        $final_url = str_replace('{status}', $inner_status, $final_url);
        $s2s_res = '';
        switch ($s2s['method']) {
            case 'GET':
                $s2s_res = get($final_url);
                break;
            case 'POST':
                $urlParts = explode('?', $final_url);
                if (count($urlParts) == 1)
                    $params = array();
                else
                    parse_str($urlParts[1], $params);
                $s2s_res = post($urlParts[0], $params);
                break;
        }
        add_s2s_log($final_url, $inner_status, $s2s['method'], $s2s_res);
    }
}

function add_s2s_log($url, $status, $method, $s2s_res)
{
    //создаёт папку для лога постбэков, если её нет
    if (!file_exists(__DIR__ . "/pblogs")) mkdir(__DIR__ . "/pblogs");
    $file = __DIR__ . "/pblogs/" . date("d.m.y") . ".pb.log";
    $save_order = fopen($file, 'a+');
    fwrite($save_order, date("Y-m-d H:i:s") . " $method $url $status {$s2s_res['info']['http_code']}\n");
    fflush($save_order);
    fclose($save_order);
}

function add_postback_log($subid, $status, $payout)
{
    global $curLink;
    //создаёт папку для лога постбэков, если её нет
    if (!file_exists(__DIR__ . "/pblogs")) mkdir(__DIR__ . "/pblogs");
    $file = __DIR__ . "/pblogs/" . date("d.m.y") . ".pb.log";
    $save_order = fopen($file, 'a+');
    if ($subid === '' || $status === '') {
        $err_msg = date("Y-m-d H:i:s") . " Error! No subid or status! {$curLink}\n";
        fwrite($save_order, $err_msg);
        fflush($save_order);
        fclose($save_order);
        die($err_msg);
    } else {
        fwrite($save_order, date("Y-m-d H:i:s") . " $subid, $status, $payout\n");
        fflush($save_order);
        fclose($save_order);
    }
}