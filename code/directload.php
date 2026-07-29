<?php
// ── Direct Load: serve landing/white resources via 404 catch-all ──
// Included from index.php. Expects settings.php and cookies.php already loaded.
require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/requestfunc.php';

global $cloSettings;

$dlMimeTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'webp' => 'image/webp',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'otf' => 'font/otf',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mp3' => 'audio/mpeg',
    'ogg' => 'audio/ogg',
    'pdf' => 'application/pdf',
    'xml' => 'application/xml',
    'txt' => 'text/plain',
    'map' => 'application/json',
];

// Parse the relative request path (strip base dir)
function dl_get_req_path(): string
{
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    if ($scriptDir !== '/' && $scriptDir !== '\\') {
        $reqPath = substr($reqPath, strlen($scriptDir));
    }
    return ltrim($reqPath, '/');
}

// Serve a local file from a base directory, with security checks
// $isWhite: if true, HTML subpages are sanitized (remove trackers, add noindex/nofollow)
function dl_serve_local(string $baseDir, string $folderName, string $reqPath, array $mimeTypes, ?string $contentBaseFolder = null, bool $isWhite = false): bool
{
    $landingBase = $baseDir . '/' . $folderName;
    $filePath = realpath($landingBase . '/' . $reqPath);
    $realBase = realpath($landingBase);

    if ($filePath === false || $realBase === false || !str_starts_with($filePath, $realBase)) {
        return false;
    }
    if (!is_file($filePath)) {
        return false;
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    // PHP/HTML subpages
    if (in_array($ext, ['php', 'html', 'htm'])) {
        require_once __DIR__ . '/htmlprocessing.php';
        if ($contentBaseFolder !== null) {
            $relPath = $contentBaseFolder . '/' . $folderName . '/' . $reqPath;
            $html = load_content_with_include($relPath);
        } else {
            ob_start();
            require $filePath;
            $html = ob_get_clean();
        }
        if ($isWhite) {
            $html = sanitize_white_html($html);
        }
        echo $html;
        exit();
    }

    // Static resources
    $mime = $mimeTypes[$ext] ?? mime_content_type($filePath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=86400');
    readfile($filePath);
    exit();
}

function dl_send_static_file(string $filePath, array $mimeTypes): void
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mime = $mimeTypes[$ext] ?? mime_content_type($filePath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: public, max-age=86400');
    readfile($filePath);
    exit();
}

function dl_not_found(string $message = 'Not Found'): void
{
    http_response_code(404);
    echo $message;
    exit();
}

function dl_handle_black_step_route(string $reqPath, array $mimeTypes): bool
{
    if (!preg_match('#^__dl/([^/]+)/([0-9]+)(?:/(.*))?$#', $reqPath, $m)) {
        return false;
    }

    $clickid = rawurldecode($m[1]);
    $stepIndex = (int)$m[2];
    $innerPath = trim(rawurldecode((string)($m[3] ?? '')), '/');
    if (str_contains($innerPath, '..')) {
        dl_not_found('Invalid path');
    }

    require_once __DIR__ . '/db/db.php';
    require_once __DIR__ . '/campaign.php';
    require_once __DIR__ . '/htmlprocessing.php';
    require_once __DIR__ . '/redirect.php';

    if (!isset($GLOBALS['db']) || !($GLOBALS['db'] instanceof Db)) {
        $GLOBALS['db'] = new Db();
    }
    $db = $GLOBALS['db'];
    $click = $db->get_click_by_clickid($clickid);
    if (empty($click)) {
        dl_not_found('Click not found');
    }

    set_clickid($clickid);

    $maxReachedStep = (int)($click['step'] ?? 0);
    if ($stepIndex > $maxReachedStep) {
        dl_not_found('Step is not reached yet');
    }

    $campId = (int)$click['campaign_id'];
    $settings = $db->get_campaign_settings($campId);
    if (empty($settings)) {
        dl_not_found('Campaign not found');
    }
    $campaign = new Campaign($campId, $settings);

    $flow = null;
    foreach ($campaign->black->flows as $f) {
        if ($f->name === ($click['flow'] ?? '')) {
            $flow = $f;
            break;
        }
    }
    if ($flow === null || !isset($flow->steps[$stepIndex])) {
        dl_not_found('Flow or step not found');
    }

    $step = $flow->steps[$stepIndex];
    $variant = $db->get_click_step_variant($clickid, $stepIndex);
    if ($variant === null) {
        dl_not_found('Recorded step not found');
    }
    if (!in_array($variant, $step->getItems(), true)) {
        dl_not_found('Recorded variant is no longer available');
    }
    if ($step->isRedirect()) {
        $url = $step->getRedirectUrlByLabel($variant);
        redirect($url, $step->redirectType, true);
        exit();
    }
    if (!$step->isFolder()) {
        dl_not_found('Step is not a folder');
    }

    $landingBase = __DIR__ . '/' . get_cache_path('landings') . '/' . $variant;
    $realBase = realpath($landingBase);
    if ($realBase === false) {
        dl_not_found('Folder not found');
    }

    if ($innerPath === '') {
        echo load_step($campaign, $flow, $stepIndex, $variant, $clickid, true);
        exit();
    }

    $candidate = realpath($realBase . '/' . $innerPath);
    if ($candidate === false || !str_starts_with($candidate, $realBase)) {
        dl_not_found();
    }

    if (is_dir($candidate)) {
        echo load_step($campaign, $flow, $stepIndex, $variant, $clickid, true, $innerPath);
        exit();
    }
    if (!is_file($candidate)) {
        dl_not_found();
    }

    $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
    if (in_array($ext, ['php', 'html', 'htm'], true)) {
        echo load_step($campaign, $flow, $stepIndex, $variant, $clickid, true, $innerPath);
        exit();
    }

    dl_send_static_file($candidate, $mimeTypes);

    return true;
}

$reqPath = dl_get_req_path();

if (dl_handle_black_step_route($reqPath, $dlMimeTypes)) {
    return;
}

$dlMode = get_cookie('dl');
if (empty($dlMode)) {
    $dlMode = session_read('dl');
}

if (empty($dlMode)) {
    return;
}

// Skip root, admin, js, and existing TDS files
$isTdsFile = file_exists(__DIR__ . '/' . $reqPath) && !is_dir(__DIR__ . '/' . $reqPath);
if ($reqPath !== '' && !is_admin_request_path($reqPath) && !str_starts_with($reqPath, 'js/') && !$isTdsFile) {

    // Black directload is handled only via __dl/<clickid>/<step>/... route above.

    // ── White folder direct load
    if ($dlMode === 'white') {
        $folder = session_read('white');
        if (!empty($folder)) {
            dl_serve_local(
                __DIR__ . '/' . get_cache_path('whites'),
                $folder,
                $reqPath,
                $dlMimeTypes,
                get_cache_path('whites'),
                true
            );
        }
    }

    // ── White CURL direct load (white — proxy + cache, from session) ──
    if ($dlMode === 'white_curl') {
        $baseUrl = rtrim(session_read('white') ?: '', '/');
        if (!empty($baseUrl)) {
            $cacheDir = __DIR__ . '/' . get_cache_path('whites_curl') . '/' . md5($baseUrl);
            $cachePath = $cacheDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $reqPath);

            // Try serving from cache first
            if (is_file($cachePath)) {
                $ext = strtolower(pathinfo($cachePath, PATHINFO_EXTENSION));
                $mime = $dlMimeTypes[$ext] ?? mime_content_type($cachePath) ?: 'application/octet-stream';
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($cachePath));
                header('Cache-Control: public, max-age=86400');
                readfile($cachePath);
                exit();
            }

            // Cache miss — fetch through the shared HTTP transport
            $resourceUrl = $baseUrl . '/' . $reqPath;
            $resourceResponse = HttpClient::send(new HttpRequest(
                id: 'directload-resource',
                url: $resourceUrl,
                timeout: 15,
                connectTimeout: 5,
                followRedirects: true,
                userAgent: $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0',
            ));
            $content = $resourceResponse->content;
            $httpCode = $resourceResponse->httpCode();
            $contentType = $resourceResponse->contentType();

            if ($httpCode === 200 && $content !== false) {
                // Sanitize HTML subpages (remove trackers, add noindex/nofollow)
                $ext = strtolower(pathinfo($reqPath, PATHINFO_EXTENSION));
                $isHtml = in_array($ext, ['html', 'htm', 'php']) ||
                    (stripos($contentType ?? '', 'text/html') !== false);
                if ($isHtml) {
                    require_once __DIR__ . '/htmlprocessing.php';
                    $content = sanitize_white_html($content);
                }

                // Save to cache (processed HTML or raw resource)
                $cacheFileDir = dirname($cachePath);
                if (!is_dir($cacheFileDir)) {
                    @mkdir($cacheFileDir, 0755, true);
                }
                @file_put_contents($cachePath, $content);

                // Serve
                if ($contentType) {
                    header('Content-Type: ' . $contentType);
                } else {
                    $mime = $dlMimeTypes[$ext] ?? 'application/octet-stream';
                    header('Content-Type: ' . $mime);
                }
                header('Content-Length: ' . strlen($content));
                header('Cache-Control: public, max-age=86400');
                echo $content;
                exit();
            }
            // If CURL failed, fall through to normal TDS
        }
    }
}
