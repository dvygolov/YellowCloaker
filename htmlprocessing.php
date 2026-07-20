<?php

require_once __DIR__ . '/requestfunc.php';
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/htmlinject.php';
require_once __DIR__ . '/macros.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/debug.php';
require_once __DIR__ . '/settings.php';

function get_landing_path(string $folderName): string
{
    return get_cache_path('landings') . '/' . $folderName;
}
function load_content_with_include($url): string
{
    ob_start();
    $fulldir = __DIR__ . '/' . $url;
    if (
        str_ends_with($fulldir, ".php") ||
        str_ends_with($fulldir, ".html") ||
        str_ends_with($fulldir, ".htm")
    ) {
        require $fulldir;
    } elseif (file_exists($fulldir . '/index.php')) {
        require $fulldir . '/index.php';
    } elseif (file_exists($fulldir . '/index.html')) {
        require $fulldir . '/index.html';
    } elseif (file_exists($fulldir . '/index.htm')) {
        require $fulldir . '/index.htm';
    } else {
        http_response_code(404);
        echo $url . ' Not Found!';
    }

    $html = ob_get_clean();
    return $html;
}

function get_next_step_url(string $clickid, int $stepIndex): string
{
    $tds = get_tds_relative_path();
    return $tds . 'next.php?' . http_build_query(['clickid' => $clickid, 'step' => $stepIndex]);
}

function get_directload_step_url(string $clickid, int $stepIndex, string $relativePath = ''): string
{
    $tds = get_tds_relative_path();
    $base = $tds . '__dl/' . rawurlencode($clickid) . '/' . $stepIndex . '/';
    $relativePath = ltrim($relativePath, '/');
    if ($relativePath === '') {
        return $base;
    }

    $parts = array_filter(explode('/', $relativePath), fn($p) => $p !== '');
    return $base . implode('/', array_map('rawurlencode', $parts));
}

function build_send_action_url(string $originalAction, string $clickid, string $folderName): string
{
    $tds = get_tds_relative_path();
    $query = http_build_query([
        'original_action' => $originalAction,
        'clickid' => $clickid,
        'folder' => $folderName,
    ]);
    return $tds . 'send.php?' . $query;
}

function get_tds_relative_path(): string
{
    $scriptPath = array_values(array_filter(explode('/', (string)($_SERVER['SCRIPT_NAME'] ?? '')), 'strlen'));
    array_pop($scriptPath);

    if (!empty($scriptPath) && in_array(end($scriptPath), ['js', 'api'], true)) {
        array_pop($scriptPath);
    }

    $path = '/' . implode('/', $scriptPath);
    if ($path === '/') {
        return '/';
    }
    if (!str_ends_with($path, '/')) {
        $path .= '/';
    }
    return $path;
}

function load_step(Campaign $c, FlowSettings $flow, int $stepIndex, string $folderName, string $clickid, bool $directLoad = false, string $relativePath = ''): string
{
    if (!isset($flow->steps[$stepIndex])) {
        return '';
    }

    $steps = $flow->steps;
    $step = $steps[$stepIndex];
    $isLastStep = $stepIndex === (count($steps) - 1);

    $basePath = get_landing_path($folderName);
    $relativePath = ltrim($relativePath, '/');
    $targetPath = $relativePath === '' ? $basePath : ($basePath . '/' . $relativePath);

    $html = load_content_with_include($targetPath);
    $html = remove_scrapbook($html);

    if ($directLoad) {
        $directBase = get_directload_step_url($clickid, $stepIndex);
        $html = fix_head_add_base($html, $directBase);
        $html = fix_root_relative_urls($html);
    } else {
        $fullpath = get_abs_from_rel($basePath);
        $html = fix_head_add_base($html, $fullpath);
        $html = fix_src($html);
    }

    global $db;
    $click = $db->get_click_by_clickid($clickid);
    $userid = $click['userid'] ?? null;
    $mp = new MacrosProcessor($c, null, $clickid, $userid);

    if ($isLastStep) {
        $html = preg_replace_callback(
            '/\saction=[\'\"]([^\'\"]+)[\'\"]/',
            function ($matches) use ($clickid, $folderName) {
                $sendUrl = build_send_action_url($matches[1], $clickid, $folderName);
                return ' action="' . $sendUrl . '"';
            },
            $html
        );

        $submitRedirectRule = $c->scripts->getSubmitRedirectRule($flow->name, $stepIndex);
        if ($submitRedirectRule !== null) {
            $redirectUrl = $mp->replace_url_macros($submitRedirectRule['url']);
            $html = insert_file_content(
                $html,
                'submitredirect.js',
                '</body>',
                true,
                true,
                ['{REDIRECT_URL_JSON}'],
                [json_encode($redirectUrl)]
            );
        }

        $html = insert_file_content($html, 'fixanchors.js', '<body', false, true);
    } else {
        $html = preg_replace('/(<a[^>]+)(target="_blank")/i', "\\1", $html);

        $replacement = get_next_step_url($clickid, $stepIndex);
        $nextRedirectRule = $c->scripts->getNextRedirectRule($flow->name, $stepIndex);
        if ($nextRedirectRule !== null) {
            $redirectUrl = $mp->replace_url_macros($nextRedirectRule['url']);
            $html = insert_file_content(
                $html,
                'nextredirect.js',
                '</body>',
                true,
                true,
                ['{NEXT_URL_JSON}', '{REDIRECT_URL_JSON}'],
                [json_encode($replacement), json_encode($redirectUrl)]
            );
        }

        $html = preg_replace('/\{next\}/', $replacement, $html);
        $html = preg_replace('/\{offer\}/', $replacement, $html);

        if ($c->scripts->backfix) {
            $urls = array_map(fn($u) => $mp->replace_url_macros($u), $c->scripts->backfixUrls);
            $html = add_backfix($html, $urls);
        }
    }

    $html = $mp->replace_html_macros($html);
    $html = fix_phone_and_name($html);

    if ($isLastStep && !$flow->hasMultipleSteps() && $c->scripts->backfix) {
        $urls = array_map(fn($u) => $mp->replace_url_macros($u), $c->scripts->backfixUrls);
        $html = add_backfix($html, $urls);
    }

    if ($c->scripts->imagesLazyLoad) {
        $html = add_images_lazy_load($html);
    }

    $html = add_event_tracking($html, $c->scripts, $clickid);

    return $html;
}

function add_event_tracking(string $html, ScriptsSettings $scripts, string $clickid): string
{
    if (!$scripts->scrollTrackingUse && !$scripts->timeTrackingUse) {
        return $html;
    }

    $eventApiUrl = get_tds_relative_path() . 'api/events.php';
    return insert_file_content(
        $html,
        'eventtracking.js',
        '</body>',
        true,
        true,
        ['{EVENT_API_URL_JSON}', '{CLICK_ID_JSON}', '{SCROLL_THRESHOLDS_JSON}', '{TIME_THRESHOLDS_JSON}'],
        [
            json_encode($eventApiUrl),
            json_encode($clickid),
            json_encode($scripts->scrollTrackingUse ? $scripts->scrollTrackingThresholds : []),
            json_encode($scripts->timeTrackingUse ? $scripts->timeTrackingThresholds : []),
        ]
    );
}

function fix_head_add_base($html, $fullpath)
{
    $html = preg_replace('/<head [^>]+>/', '<head>', $html);
    $html = insert_after_tag($html, "<head>", "<base href='" . $fullpath . "'>");
    return $html;
}

function fix_src($html): string
{
    $src_regex = '/(<[^>]+src=[\'\"])\/([^\/][^>]*>)/';
    return preg_replace($src_regex, "\\1\\2", $html);
}

function fix_root_relative_urls(string $html): string
{
    $tdsBase = rtrim(get_tds_relative_path(), '/');
    $html = preg_replace_callback(
        '/(\s(?:src|href|action)=[\'\"])(\/(?!\/)[^\'\"]*)/i',
        function ($matches) use ($tdsBase) {
            $attrPrefix = $matches[1];
            $url = $matches[2];

            if ($tdsBase !== '' && str_starts_with($url, $tdsBase . '/')) {
                return $attrPrefix . $url;
            }

            return $attrPrefix . ltrim($url, '/');
        },
        $html
    );
    return $html;
}

function add_input_attribute($html, $regex, $attribute)
{
    if (preg_match_all($regex, $html, $matches, PREG_OFFSET_CAPTURE)) {
        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
            if (!str_contains($matches[0][$i][0], $attribute)) {
                $replacement = "<input {$attribute}" . substr($matches[0][$i][0], 6);
                $html = substr_replace($html, $replacement, $matches[0][$i][1], strlen($matches[0][$i][0]));
            }
        }
    }
    return $html;
}

//if type of phone field is text, change it to tel for more convenient input on mobile
//add autocomplete to name and phone fields
//add required if it's not there
function fix_phone_and_name($html)
{
    //fix type=text to type=tel
    $firstr = '/(<input[^>]*name="(phone|tel)"[^>]*type=")(text)("[^>]*>)/';
    $secondr = '/(<input[^>]*type=")(text)("[^>]*name="(phone|tel)"[^>]*>)/';
    $html = preg_replace($secondr, "\\1tel\\3", $html);
    $html = preg_replace($firstr, "\\1tel\\4", $html);

    $telregex = '/<input[^>]*type="tel"[^>]*>/';
    $html = add_input_attribute($html, $telregex, 'autocomplete="tel"');
    $html = add_input_attribute($html, $telregex, 'required');

    $nameregex = '/<input[^>]*name="name"[^>]*>/';
    $html = add_input_attribute($html, $nameregex, 'autocomplete="name"');
    $html = add_input_attribute($html, $nameregex, 'required');

    return $html;
}

function add_images_lazy_load($html)
{
    $html = preg_replace('/(<img\s)((?!.*?loading=([\'\"])[^\'\"]+\3)[^>]*)(>)/s', '<img loading="lazy" \\2\\4', $html);
    return $html;
}

//load safe page from FOLDER
function load_white_content($url, string $mode = 'base'): string
{
    $path = get_cache_path('whites') . '/' . $url;
    $html = load_content_with_include($path);

    switch ($mode) {
        case 'direct':
            session_write('dl', 'white');
            $html = fix_root_relative_urls($html);
            break;
        case 'rewrite':
            $baseurl = get_tds_path() . $path . '/';
            $html = rewrite_relative_urls($html, $baseurl);
            break;
        case 'base':
        default:
            $fullpath = get_abs_from_rel($path);
            $html = fix_head_add_base($html, $fullpath);
            break;
    }

    //adding no-referer,noindex,nofollow
    $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);
    $html = remove_scrapbook($html);

    return $html;
}

//sanitize safe page HTML: remove trackers, og:url, canonical, noscript; add noindex/nofollow
function sanitize_white_html(string $html): string
{
    //remove everything unneeded
    $html = preg_replace('/(<meta property=\"og:url\" [^>]+>)/', "", $html);
    $html = preg_replace('/(<link rel=\"canonical\" [^>]+>)/', "", $html);
    //killing tracking scripts
    $tracking_scripts = array(
        'google_analytics' => 'https://www.google-analytics.com/analytics.js',
        'google_tag_manager' => 'https://www.googletagmanager.com/gtag/js',
        'facebook_pixel' => 'connect.facebook.net/en_US/fbevents.js',
        'twitter_conversion' => 'https://platform.twitter.com/oct.js',
        'linkedin_insight_tag' => 'https://snap.licdn.com/li.lms-analytics/insight.min.js',
        'pinterest_tag' => '//s.pinimg.com/ct/core.js',
        'adobe_dtm' => 'https://assets.adobedtm.com',
        'adobe_analytics' => '.sc.omtrdc.net/s/s_code.js',
        'hubspot_tracking_code' => '//js.hs-scripts.com/',
        'bing_ads' => '//bat.bing.com/bat.js',
        'crazy_egg' => '//script.crazyegg.com/pages/scripts/',
        'yandex_metrika' => 'https://mc.yandex.ru/metrika/tag.js',
        'hotjar' => 'static.hotjar.com/c/hotjar'
    );
    foreach ($tracking_scripts as $key => $scriptUrl) {
        $pattern = '#<script[^>]*(src="[^"]*' . preg_quote($scriptUrl) . '[^"]*")[^>]*>.*?</script>|<script[^>]*>[^<]*' . preg_quote($scriptUrl) . '[^<]*</script>#is';
        $html = preg_replace($pattern, '', $html);
    }
    //removing all noscript tags
    $pattern = '#<noscript>.*?</noscript>#is';
    $html = preg_replace($pattern, '', $html);
    //adding some additional tags to head
    $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);

    return $html;
}

//loading safe page with CURL
function load_white_curl(string $url, string $mode = 'rewrite'): string
{
    $res = get($url);
    $html = $res['content'];
    if ($mode === 'direct') {
        session_write('dl', 'white_curl');
    } else {
        $html = rewrite_relative_urls($html, $url);
    }

    $html = sanitize_white_html($html);
    return $html;
}

function add_backfix(string $html, array $urls): string
{
    $debug = DebugMethods::On() ? 'true' : 'false';
    $path = get_tds_path(true, false);
    $linksJson = htmlspecialchars(json_encode(array_values($urls)), ENT_QUOTES);
    $jsCode = <<<EOT
    <script src='{$path}/scripts/backfix.php' 
        data-links='{$linksJson}'
        data-traceenabled='{$debug}'
        data-redirect='false'
        data-isoff='false'>
    </script>
EOT;
    $needle = '</head>';
    if (!str_contains($html, $needle)) {
        $needle = '</body>';
    }
    return insert_before_tag($html, $needle, $jsCode);
}

//rewrite relative urls (not starting with http or //)
function rewrite_relative_urls($html, $url)
{
    $modified = preg_replace('/\ssrc=[\'\"](?!http|\/\/|data:)([^\'\"]+)[\'\"]/', " src=\"$url\\1\"", $html);
    $modified = preg_replace('/\shref=[\'\"](?!http|#|\/\/)([^\'\"]+)[\'\"]/', " href=\"$url\\1\"", $modified);
    $modified = preg_replace('/background-image:\s*url\((?!http|#|\/\/)([^\)]+)\)/', "background-image: url($url\\1)", $modified);
    return $modified;
}

function remove_scrapbook($html)
{
    $modified = preg_replace('/data\-scrapbook\-source=[\'\"][^\'\"]+[\'\"]/', '', $html);
    $modified = preg_replace('/data\-scrapbook\-create=[\'\"][^\'\"]+[\'\"]/', '', $modified);
    return $modified;
}
