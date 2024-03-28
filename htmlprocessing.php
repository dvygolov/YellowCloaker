<?php
require_once __DIR__ . '/js/obfuscator.php';
require_once __DIR__ . '/bases/ipcountry.php';
require_once __DIR__ . '/requestfunc.php';
require_once __DIR__ . '/htmlinject.php';
require_once __DIR__ . '/macros.php';
require_once __DIR__ . '/cookies.php';

function load_content_with_include($url): string
{
    ob_start();
    $fulldir = __DIR__ . '/' . $url;
    if (str_ends_with($fulldir,".php")||str_ends_with($fulldir,".html")){
        require $fulldir;
    }
    // Check for each file and require/include the first one that exists
    else if (file_exists($fulldir . '/index.php')) {
        require $fulldir . '/index.php';
    } elseif (file_exists($fulldir . '/index.html')) {
        require $fulldir . '/index.html';
    } elseif (file_exists($fulldir . '/index.htm')) {
        require $fulldir . '/index.htm';
    }

    $html = ob_get_clean();
    return $html;
}

//Подгрузка контента блэк проклы из другой папки
function load_prelanding($url, $land_number): string
{
    $fullpath = get_abs_from_rel($url);

    $html = load_content_with_include($url);
    $html = remove_scrapbook($html);

    //чистим тег <head> от всякой ненужной мути
    $html = fix_head_add_base($html, $fullpath);
    $html = fix_src($html);

    $html = replace_html_macros($html);
    $html = fix_phone_and_name($html);
    //добавляем во все формы сабы
    $html = insert_subs_into_forms($html);

    //убираем target=_blank если был изначально на прокле
    $html = preg_replace('/(<a[^>]+)(target="_blank")/i', "\\1", $html);

    $html = replace_landing_link($html, $land_number);

    $html = add_images_lazy_load($html);

    return $html;
}

function replace_landing_link($html, $land_number): string
{
    global $replace_prelanding, $replace_prelanding_address;
    $cloaker = get_cloaker_path();
    $querystr = $_SERVER['QUERY_STRING'];
    //замена макроса {offer} на прокле на универсальную ссылку ленда landing.php
    $replacement = $cloaker . 'landing.php?l=' . $land_number . (!empty($querystr) ? '&' . $querystr : '');

    //если мы будем подменять преленд при переходе на ленд, то ленд надо открывать в новом окне
    if ($replace_prelanding) {
        $replacement = $replacement . '" target="_blank"';
        $url = replace_url_macros($replace_prelanding_address); //заменяем макросы
        $html = insert_file_content($html, 'replaceprelanding.js', '</body>', true, true, '{REDIRECT}', $url);
    }
    $html = preg_replace('/\{offer\}/', $replacement, $html);
    return $html;
}


//Подгрузка контента блэк ленда из другой папки
function load_landing($url)
{
    global $black_land_use_custom_thankyou_page;
    global $replace_landing, $replace_landing_address;

    $fullpath = get_abs_from_rel($url);

    $html = load_content_with_include($url);
    $html = remove_scrapbook($html);
    $html = fix_head_add_base($html, $fullpath);
    $html = fix_src($html);

    if ($black_land_use_custom_thankyou_page === true) {
        $query = http_build_query($_GET);
        $html = preg_replace_callback(
            '/\saction=[\'\"]([^\'\"]+)[\'\"]/',
            function ($matches) use ($query) {
                $originalAction = urlencode($matches[1]);
                $send = " action=\"../send.php?original_action={$originalAction}";
                if ($query !== '') $send .= "&" . $query;
                $send .= "\"";
                return $send;
            },
            $html
        );
    }
    //если мы будем подменять ленд при переходе на страницу Спасибо, то Спасибо надо открывать в новом окне
    if ($replace_landing) {
        $replacelandurl = replace_url_macros($replace_landing_address); //заменяем макросы
        $html = insert_file_content($html, 'replacelanding.js', '</body>', true, true, '{REDIRECT}', $replacelandurl);
    }

    //добавляем во все формы сабы
    $html = insert_subs_into_forms($html);

    $html = fix_anchors($html);
    $html = replace_html_macros($html);
    //заменяем поле с телефоном на более удобный тип - tel + добавляем autocomplete
    $html = fix_phone_and_name($html);

    $html = add_images_lazy_load($html);

    return $html;
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

//если тип поля телефона - text, меняем его на tel для более удобного ввода с мобильных
//добавляем autocomplete к полям name и phone
//добавляем required, если его нет
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


function fix_anchors($html)
{
    return insert_file_content($html, "replaceanchorswithsmoothscroll.js", "<body", false, true);
}

function add_images_lazy_load($html)
{
    global $images_lazy_load;
    if (!$images_lazy_load) return $html;
    $html = preg_replace('/(<img\s)((?!.*?loading=([\'\"])[^\'\"]+\3)[^>]*)(>)/s', '<img loading="lazy" \\2\\4', $html);
    return $html;
}

//Подгрузка контента вайта ИЗ ПАПКИ
function load_white_content($url, $add_js_check)
{
    $html = load_content_with_include($url);
    $baseurl = '/' . $url . '/';
    //переписываем все относительные src и href (не начинающиеся с http)
    $html = rewrite_relative_urls($html, $baseurl);

    //добавляем в <head> пару доп. метатегов
    $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);
    $html = remove_scrapbook($html);

    if ($add_js_check) {
        $html = add_js_testcode($html);
    }
    return $html;
}

//когда подгружаем вайт методом CURL
function load_white_curl($url, $add_js_check)
{
    $res = get($url);
    $html = $res['html'];
    $html = rewrite_relative_urls($html, $url);

    //удаляем лишние палящие теги
    $html = preg_replace('/(<meta property=\"og:url\" [^>]+>)/', "", $html);
    $html = preg_replace('/(<link rel=\"canonical\" [^>]+>)/', "", $html);
    //режем все трекинговые скрипты
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
    foreach ($tracking_scripts as $key => $url) {
        $pattern = '#<script[^>]*(src="[^"]*' . preg_quote($url) . '[^"]*")[^>]*>.*?</script>|<script[^>]*>[^<]*' . preg_quote($url) . '[^<]*</script>#is';
        $html = preg_replace($pattern, '', $html);
    }
    //добавляем в <head> пару доп. метатегов
    $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta name="robots" content="noindex, nofollow">', $html);

    if ($add_js_check) {
        $html = add_js_testcode($html);
    }
    return $html;
}

function load_js_testpage()
{
    $test_page = load_content_with_include('js/testpage.html');
    return add_js_testcode($test_page);
}

function add_js_testcode($html)
{
    global $js_obfuscate;
    $jsCode = str_replace('{DOMAIN}', get_cloaker_path(), file_get_contents(__DIR__ . '/js/connect.js'));
    if ($js_obfuscate) {
        $hunter = new HunterObfuscator($jsCode);
        $jsCode = $hunter->Obfuscate();
    }
    $jsCode = "<script id='connect'>{$jsCode}</script>";
    $needle = '</body>';
    if (!str_contains($html, $needle)) $needle = '</html>';
    return insert_before_tag($html, $needle, $jsCode);
}

//вставляет все сабы в hidden полях каждой формы
function insert_subs_into_forms($html)
{
    global $sub_ids;
    $all_subs = '';
    $preset = ['subid', 'prelanding', 'landing'];
    foreach ($sub_ids as $sub) {
        $key = $sub["name"];
        $value = $sub["rewrite"];

        if (in_array($key, $preset) && !empty(get_cookie($key))) {
            $html = preg_replace('/(<input[^>]*name="' . $value . '"[^>]*>)/', "", $html);
            $all_subs = $all_subs . '<input type="hidden" name="' . $value . '" value="' . get_cookie($key) . '"/>';
        } elseif (!empty($_GET[$key])) {
            $html = preg_replace('/(<input[^>]*name="' . $value . '"[^>]*>)/', "", $html);
            $all_subs = $all_subs . '<input type="hidden" name="' . $value . '" value="' . $_GET[$key] . '"/>';
        }
    }
    if (!empty($all_subs)) {
        $needle = '<form';
        return insert_after_tag($html, $needle, $all_subs);
    }
    return $html;
}

//переписываем все относительные src и href (не начинающиеся с http или с //)
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