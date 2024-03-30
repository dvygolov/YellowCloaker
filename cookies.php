<?php
function ywbsetcookie($name, $value, $path = '/'): void
{
    $expires = time() + 60 * 60 * 24 * 5; //время, на которое ставятся куки, по умолчанию - 5 дней
    header("Set-Cookie: {$name}={$value}; Expires={$expires}; Path={$path}; SameSite=None; Secure", false);
}

function get_cookie($name): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(['read_and_close' => true]);
    }
    return $_COOKIE[$name] ?? $_SESSION[$name] ?? '';
}

function set_subid(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set("session.cookie_secure", 1);
        session_start();
    }
    //устанавливаем пользователю в куки уникальный subid, либо берём его из куки, если он уже есть
    $cursubid = $_COOKIE['subid'] ?? uniqid();
    ywbsetcookie('subid', $cursubid, '/');
    $_SESSION['subid'] = $cursubid;
    session_write_close();
    return $cursubid;
}

function set_px(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set("session.cookie_secure", 1);
        session_start();
    }
    //устанавливаем пользователю в куки уникальный subid, либо берём его из куки, если он уже есть
    $curpx = $_GET['px']??'';
    if (empty($curpx)) return;
    ywbsetcookie('px', $curpx, '/');
    $_SESSION['px'] = $curpx;
    session_write_close();
}

//проверяем, если у пользователя установлена куки, что он уже конвертился, а также имя и телефон, то сверяем время
//если прошло менее суток, то хуй ему, а не лид, обнуляем время
function has_conversion_cookies($name, $phone): bool
{
    $cname = get_cookie('name');
    $cphone = get_cookie('phone');
    $ctime = get_cookie('ctime');

    if (empty($ctime) || empty($name) || empty($phone)) {
        return false;
    }

    if ($cname !== $name || $cphone !== $phone) {
        return false;
    }

    $currentTimestamp = (new DateTime())->getTimestamp();
    $secondsDiff = $currentTimestamp - $ctime;

    if ($secondsDiff < 24 * 60 * 60) {
        ywbsetcookie('ctime', $currentTimestamp);
        return true;
    }

    return false;
}