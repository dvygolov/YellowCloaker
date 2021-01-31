<?php
	include '../logging.php';
	include '../redirect.php';
	$name = isset($_POST['name'])?$_POST['name']:'';
	$phone = isset($_POST['phone'])?$_POST['phone']:'';
	$subid = isset($_POST['subid'])?$_POST['subid']:'';
	$email = isset($_POST['email'])?$_POST['email']:'';
	$lang = isset($_POST['language'])?$_POST['language']:'';
	write_mail_to_log($subid,$name,$phone,$email);
	$filepath = $lang.'email.html';
	if (!file_exists($filepath))
		$filepath = 'ENemail.html';
	$html = file_get_contents($filepath);
	$search='{NAME}';
	$html = str_replace($search,$_COOKIE['name'],$html);
	$search='{PHONE}';
	$html = str_replace($search,$_COOKIE['phone'],$html);
	echo $html;
?>