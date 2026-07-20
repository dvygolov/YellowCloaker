<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/requestfunc.php';
global $db, $cloSettings;

$clickid = (string)($_POST['clickid'] ?? $_GET['clickid'] ?? get_clickid());
if (!empty($clickid)) {
    set_clickid($clickid);
}

//send to Aff Network only if it's not empty and not a duplicate
if (empty($_POST) || has_conversion_cookies($_POST)) {
    redirect(get_tds_path());
    return;
}

$fullpath = '';
$original_action = (string)($_GET['original_action'] ?? '');
//if the form action is an absolute URL, send the form data to that URL
if (str_starts_with($original_action, "http")) {
    $fullpath = $original_action;
} //else, compose the full address to the script
else {
    $folder = (string)($_GET['folder'] ?? $_POST['folder'] ?? get_cookie('landing'));
    if ($folder === '') {
        http_response_code(400);
        echo 'Missing folder for relative form action';
        return;
    }
    $landingPath = get_cache_path('landings') . '/' . $folder;
    $url = $landingPath . '/' . $original_action;
    $fullpath = get_abs_from_rel($url);
}


if (DebugMethods::On()){
    $res = [];
    $res['info']=[];
    $res['info']['http_code'] = rand(0, 1) ? 200 : 302;
    $res['info']['redirect_url'] = "http://example.com";
    $res['content'] = "<html>This is debug send!</html>";
    $res['error'] = null;
}
else{
    $res = post($fullpath, $_POST);
}

$useUTP = $cloSettings['useUTP'];

switch ($res["info"]["http_code"]) {
    case 302:
        $db->add_lead($clickid,$_POST);
        $thankyouData = $_POST;
        if (!empty($clickid)) {
            $thankyouData['clickid'] = $clickid;
            $click = $db->get_click_by_clickid($clickid);
            if (!empty($click['userid'])) {
                $thankyouData['userid'] = $click['userid'];
            }
        }
        if ($useUTP) {
            redirect("/thankyou/index.php?" . http_build_query($thankyouData));
        } else {
            redirect($res["info"]["redirect_url"]);
        }
        break;
    case 200:
        $db->add_lead($clickid, $_POST);
        $thankyouData = $_POST;
        if (!empty($clickid)) {
            $thankyouData['clickid'] = $clickid;
            $click = $db->get_click_by_clickid($clickid);
            if (!empty($click['userid'])) {
                $thankyouData['userid'] = $click['userid'];
            }
        }
        if ($useUTP) {
            echo redirect("/thankyou/index.php?" . http_build_query($thankyouData),"js");
        } else {
            echo $res["content"];
        }
        break;
    default:
        echo $fullpath."<br/>";
        var_dump($res["content"]);
        echo '<br/>';
        var_dump($res["error"]);
        echo '<br/>';
        var_dump($res["info"]);
        echo '<br/>';
        var_dump($_POST);
        exit();
}
