<?php
include '../keitaro/keitaro.php';
$email = $_GET['email'];
$subid = $_GET['subid'];
$kt = new KeitaroHelper();
return $kt->update_email($subid,$email);
?>