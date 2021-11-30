<?php
require_once '../settings.php';

function check_password(){
    global $log_password;
    if (!empty($log_password)){
        if (!isset($_GET['password']))
            die("No password in querystring!");
        if (empty($_GET['password']))
            die("Empty password in querystring!");
        if ($_GET['password'] !== $log_password)
            die("Incorrect password!");
    }
}

?>