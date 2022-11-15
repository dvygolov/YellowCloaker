<?php
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../logging.php';

function check_password()
{
    global $log_password;
    if (!empty($log_password)) {
        if (!isset($_REQUEST['password'])) {
            $msg = "No password in querystring!";
            add_log("login", $msg, true);
            die($msg);
        }
        if (empty($_REQUEST['password'])) {
            $msg = "Empty password in querystring!";
            add_log("login", $msg, true);
            die($msg);
        }
        if ($_REQUEST['password'] !== $log_password) {
            $msg = "Incorrect password!";
            add_log("login", $msg, true);
            die($msg);
        }
    }
}