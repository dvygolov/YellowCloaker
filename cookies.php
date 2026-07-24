<?php

function set_cookie($name, $value, $sessionOnly = false): void
{
    if (!$sessionOnly) {
        $path = '/';
        $expires = time() + 60 * 60 * 24 * 5; //time to live for cookies - 5 days
        header("Set-Cookie: {$name}={$value}; Expires={$expires}; Path={$path}; SameSite=None; Secure", false);
    }
    session_write($name, $value);
}

function session_write($name, $value)
{
    get_session();
    $_SESSION[$name] = $value;
    session_write_close();
}

function session_read($name)
{
    get_session(true);
    return $_SESSION[$name] ?? null;
}

function session_remove($name){
    get_session();
    unset($_SESSION[$name]);
    session_write_close();
}

function get_cookie($name): string
{
    get_session(true);
    return $_COOKIE[$name] ?? $_SESSION[$name] ?? '';
}

function get_userid(): string
{
    return isset($_COOKIE['userid']) && is_string($_COOKIE['userid']) ? $_COOKIE['userid'] : '';
}

function generate_userid(): string
{
    return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}

function set_userid_cookie(string $userid): void
{
    $isSecure = function_exists('is_https')
        ? is_https()
        : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('userid', $userid, [
        'expires' => time() + 30 * 24 * 60 * 60,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function generate_clickid(string $userid = ''): string
{
    return rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
}

function set_clickid(string $clickid): void
{
    session_write('clickid', $clickid);
}

function get_clickid(): string
{
    return session_read('clickid') ?? '';
}

//if the user has already converted before with the same data return true
function has_conversion_cookies(array $data): bool
{
    $cdata = get_cookie('postmd5');
    $ctime = get_cookie('ctime');

    if (empty($ctime) || empty($cdata)) {
        set_conversion_cookies($data);
        return false;
    }

    $curmd5 = md5(json_encode($data));
    if ($cdata !== $curmd5) {
        set_conversion_cookies($data);
        return false;
    }

    $currentTimestamp = (new DateTime())->getTimestamp();
    $secondsDiff = $currentTimestamp - $ctime;
    if ($secondsDiff < 24 * 60 * 60) {
        set_cookie('ctime', $currentTimestamp);
        return true;
    }

    set_conversion_cookies($data);
    return false;
}

function set_conversion_cookies(array $data): void
{
    $curmd5 = md5(json_encode($data));
    set_cookie('postmd5', $curmd5);
    set_cookie('ctime', (new DateTime())->getTimestamp());
}

function get_session($readOnly = false)
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        "SameSite" => $isSecure ? "None" : "Lax",
        "Secure"   => $isSecure,
        "HttpOnly" => false,
    ]);
    
    if ($readOnly) {
        session_start(['read_and_close' => true]);
    } else {
        session_start();
    }
}
