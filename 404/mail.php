<?php
include_once __DIR__ . '/../db.php';
include_once __DIR__ . '/../redirect.php';

$subid = $_POST['subid'] ?? '';
$email = $_POST['email'] ?? '';
if (!empty($subid) && !empty($email))
    add_email($subid, $email);
redirect($lang . "email.html");