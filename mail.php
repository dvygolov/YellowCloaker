<?php
	include 'logging.php';
	$name = isset($_POST['name'])?$_POST['name']:'';
	$phone = isset($_POST['phone'])?$_POST['phone']:'';
	$subid = isset($_POST['subid'])?$_POST['subid']:'';
	$email = isset($_POST['email'])?$_POST['email']:'';
	write_mail_to_log($subid,$name,$phone,$email);
	header("Location: emailthankyou.html");
?>