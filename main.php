<?php
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/htmlprocessing.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/redirect.php';
require_once __DIR__ . '/abtest.php';

function white($use_js_checks)
{
    global $c; //Campaign
    $ws = $c->white;

    //HACK: dirty hack to pass the referer through cookies
    if ($use_js_checks && !empty($_SERVER['HTTP_REFERER'])) {
        set_cookie("referer", $_SERVER['HTTP_REFERER']);
    }

    if ($ws->domainFilterEnabled) { //if we want to use different white pages for different domains
        $curdomain = $_SERVER['HTTP_HOST'];
        if (str_ends_with($curdomain, ':' . $_SERVER['SERVER_PORT'])) {
            $portLength = strlen(':' . $_SERVER['SERVER_PORT']);
            $curdomain = substr($curdomain, 0, - $portLength);
        }
        foreach ($ws->domainSpecific as $wds) {
            if ($wds['name'] == $curdomain) {
                $wtd_arr = explode(":", $wds['action'], 2);
                $action = $wtd_arr[0];
                switch ($action) {
                    case 'error':
                        $error_codes = [intval($wtd_arr[1])];
                        break;
                    case 'folder':
                        $folder_names = [$wtd_arr[1]];
                        break;
                    case 'curl':
                        $curl_urls = [$wtd_arr[1]];
                        break;
                    case 'redirect':
                        $redirect_urls = [$wtd_arr[1]];
                        break;
                }
                break;
            }
        }
    }

    //if we have Javascript bot tests enabled then we use a special white page
    //or add the test code into an existing white page
    if ($use_js_checks) {
        switch ($action) {
            case 'error':
            case 'redirect':
                echo load_js_testpage();
                break;
            case 'folder':
                $curfolder = select_item($folder_names, $c->saveUserFlow, 'white', true);
                echo load_white_content($curfolder[0], $use_js_checks);
                break;
            case 'curl':
                $cururl = select_item($curl_urls, $c->saveUserFlow, 'white', false);
                echo load_white_curl($cururl[0], $use_js_checks);
                break;
        }
    } else {
        switch ($action) {
            case 'error':
                $curcode = select_item($error_codes, $c->saveUserFlow, 'white', true);
                http_response_code($curcode[0]);
                break;
            case 'folder':
                $curfolder = select_item($folder_names, $c->saveUserFlow, 'white', true);
                echo load_white_content($curfolder[0], false);
                break;
            case 'curl':
                $cururl = select_item($curl_urls, $c->saveUserFlow, 'white', false);
                echo load_white_curl($cururl[0], false);
                break;
            case 'redirect':
                $cururl = select_item($redirect_urls, $c->saveUserFlow, 'white', false);
                redirect($cururl[0], $ws->redirectType);
                break;
        }
    }
}

function black(array $clickparams)
{
    global $c; //Campaign
    header('Access-Control-Allow-Credentials: true');
    if (isset($_SERVER['HTTP_REFERER'])) {
        $parsed_url = parse_url($_SERVER['HTTP_REFERER']);
        header('Access-Control-Allow-Origin: ' . $parsed_url['scheme'] . '://' . $parsed_url['host']);
    }

    $cursubid = set_subid();
    set_px();

    $landings = [];
    $isfolderland = false;
    
    $bl = $c->black->land;
    if ($bl->action == 'redirect')
        $landings = $bl->redirectUrls;
    else if ($bl->action == 'folder') {
        $landings = $bl->folderNames;
        $isfolderland = true;
    }

    $bp = $c->black->preland;
    $db = new Db();
    switch ($bp->action) {
        case 'none':
            $res = select_item($landings, $c->saveUserFlow, 'landing', $isfolderland);
            $landing = $res[0];
            $db->add_black_click($cursubid, $clickparams, '', $landing, $c->campaignId);

            switch ($bl->action) {
                case 'folder':
                    echo load_landing($landing);
                    break;
                case 'redirect':
                    redirect($landing,$bl->redirectType,true);
                    break;
            }
            break;
        case 'folder': //если мы используем локальные проклы
            $prelandings = $bp->folderNames;
            if (empty($prelandings)) break;
            $res = select_item($prelandings, $c->saveUserFlow, 'prelanding', true);
            $prelanding = $res[0];
            $res = select_item($landings, $c->saveUserFlow, 'landing', $isfolderland);
            $landing = $res[0];
            $t = $res[1];

            echo load_prelanding($prelanding, $t);
            $db->add_black_click($cursubid, $clickparams, $prelanding, $landing, $c->campaignId);
            break;
    }
}