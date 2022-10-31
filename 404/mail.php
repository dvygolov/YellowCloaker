<?php
include '../db.php';
include '../redirect.php';
$subid = $_POST['subid'] ?? '';
$email = $_POST['email'] ?? '';
$lang = $_POST['language'] ?? '';
add_email($subid, $email);
redirect($lang . "email.html");