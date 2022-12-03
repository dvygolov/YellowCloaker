<?php
include_once __DIR__ . '/../db.php';
include_once __DIR__ . '/../settings.php';
include_once __DIR__ . '/../redirect.php';
$subid = $_POST['subid'] ?? '';
$email = $_POST['email'] ?? '';
$lang = $_POST['language'] ?? '';
add_email($subid, $email, $cur_config);
redirect($lang . "email.html");