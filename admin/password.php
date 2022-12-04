<?php
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../logging.php';

function check_password($die = true): bool
{
    global $log_password;
    if (!empty($log_password)) {
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
        if ($_REQUEST['password'] !== $log_password) {
            $msg = "Incorrect password!";
            add_log("login", $msg, true);
            if ($die) die($msg);
            else return false;
        }
    }
    return true;
}