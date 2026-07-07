<?php

require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/settings.php';
DebugMethods::start("YWBMainCycle");

//we always need a slash at the end of the url, otherwise links will not work properly
$url = $_SERVER['REQUEST_URI'];
$urlPath = parse_url($url, PHP_URL_PATH);
$urlPath = is_string($urlPath) ? $urlPath : $url;
if (str_ends_with($urlPath, '/' . get_admin_path_segment())) {
    header("Location: " . $url . "/");
    exit();
}

//handle robots.txt requests
if (isset($_SERVER['REQUEST_URI']) && str_ends_with($_SERVER['REQUEST_URI'], '/robots.txt')) {
    header('Content-Type: text/plain');
    echo "User-agent: *\nDisallow: /\n";
    exit();
}

require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/directload.php';

//fix for Apache Multiviews and/or PHP Development Server
if ($_SERVER['SCRIPT_NAME'] !== $_SERVER['PHP_SELF']) {
    http_response_code(404);
    exit("Not Found");
}

require_once __DIR__ . '/tds.php';
require_once __DIR__ . '/redirect.php';

$action = Tds::getAction();
if ($action->action !== 'redirect') {
    DebugMethods::stop("YWBMainCycle");
    $action->perform();
} else {
    $action->perform();
    DebugMethods::stop("YWBMainCycle");
}
