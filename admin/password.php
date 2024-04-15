<?php
require_once __DIR__ . '/../logging.php';

function check_password($die = true): bool
{
    $cloSettings = json_decode(file_get_contents(__DIR__ . '/../settings.json'), true);
    $pwd = $cloSettings['password'];

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!empty($_SESSION['loggedin']) && $_SESSION['loggedin'] === true)
        return true;

    if (empty($pwd))
        return true;

    if (!isset($_REQUEST['password'])) {
        $msg = "No password found!";
        add_log("login", $msg, true);
        if ($die)
            die($msg);
        else
            return false;
    }
    if (empty($_REQUEST['password'])) {
        $msg = "Empty password!";
        add_log("login", $msg, true);
        if ($die)
            die($msg);
        else
            return false;
    }
    if ($_REQUEST['password'] !== $pwd) {
        $msg = "Incorrect password!";
        add_log("login", $msg, true);
        if ($die)
            die($msg);
        else
            return false;
    }
    $_SESSION['loggedin'] = true;
    return true;
}