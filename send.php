<?php
	include 'settings.php';
	include 'logging.php';
	
	$name = isset($_POST['name'])?$_POST['name']:$_POST['fio'];
	$phone = isset($_POST['phone'])?$_POST['phone']:$_POST['tel'];
	$subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';
			
	$is_duplicate = lead_is_duplicate($subid,$phone);

	$cookietime=time()+60*60*24*5; //время, на которое ставятся куки, по умолчанию - 5 дней
	//устанавливаем пользователю в куки его имя и телефон, чтобы показать их на стр Спасибо
	header("Set-Cookie: name={$name}; Expires={$cookietime}; Path=/; SameSite=None; Secure");
	header("Set-Cookie: phone={$phone}; Expires={$cookietime}; Path=/; SameSite=None; Secure");

	//шлём в ПП только если это не дубль	
	if (!$is_duplicate){ 
		include_once $_COOKIE['landing'].'/'.$black_land_conversion_script;
		header_remove("Location"); //удаляем редирект из файла заказа ПП
		//пишем лида в базу и редиректим на Спасибо
		write_leads_to_log($subid,$name,$phone,'');
		Redirect("thankyou.php?".http_build_query($_GET));
	}
	else
	{
		header("Location: thankyou.php");
	}
	
	function Redirect($url){
		echo "<script type='text/javascript'> window.location='$url';</script>";
		return;
	}
?>