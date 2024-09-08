<?php
require_once __DIR__ . '/../logging.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../cookies.php';

function check_password($die = true): bool
{
    global $cloSettings;
    $pwd = $cloSettings['adminPassword'];
    get_session();

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
    session_write_close();
    return true;
}