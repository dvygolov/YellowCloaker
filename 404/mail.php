<?php
	include '../db.php';
	include '../redirect.php';
	$subid = isset($_POST['subid'])?$_POST['subid']:'';
	$email = isset($_POST['email'])?$_POST['email']:'';
	$lang = isset($_POST['language'])?$_POST['language']:'';
	add_email($subid,$email);
	redirect($lang."email.html");
?>