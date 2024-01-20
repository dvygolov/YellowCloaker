<?php
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../logging.php';

function check_password($die = true): bool
{
    global $admin_password;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!empty($_SESSION['loggedin']) && $_SESSION['loggedin'] === true)
        return true;

    if (empty($admin_password)) return true;

    if (!isset($_REQUEST['password'])) {
        $msg = "No password found!";
        add_log("login", $msg, true);
        if ($die) die($msg);
        else return false;
    }
    if (empty($_REQUEST['password'])) {
        $msg = "Empty password!";
        add_log("login", $msg, true);
        if ($die) die($msg);
        else return false;
    }
    if ($_REQUEST['password'] !== $admin_password) {
        $msg = "Incorrect password!";
        add_log("login", $msg, true);
        if ($die) die($msg);
        else return false;
    }
    $_SESSION['loggedin']=true;
    return true;
}