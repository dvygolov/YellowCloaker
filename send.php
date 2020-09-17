<?php
	//Включение отладочной информации
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	//Конец включения отладочной информации
	include 'settings.php';
	include 'logging.php';
	
	$name = '';
	if (isset($_POST['name']))
		$name=$_POST['name'];
	if (isset($_POST['fio']))
		$name=$_POST['fio'];
	if (isset($_POST['first_name'])&&isset($_POST['last_name']))
		$name = $_POST['first_name'].' '.$_POST['last_name'];
	if (isset($_POST['firstname'])&&isset($_POST['lastname']))
		$name = $_POST['firstname'].' '.$_POST['lastname'];
	$phone = isset($_POST['phone'])?$_POST['phone']:$_POST['tel'];
	$subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';
			
	$is_duplicate = lead_is_duplicate($subid,$phone);

	$cookietime=time()+60*60*24*5; //время, на которое ставятся куки, по умолчанию - 5 дней
	//устанавливаем пользователю в куки его имя и телефон, чтобы показать их на стр Спасибо
	header("Set-Cookie: name={$name}; Expires={$cookietime}; Path=/; SameSite=None; Secure",false);
	header("Set-Cookie: phone={$phone}; Expires={$cookietime}; Path=/; SameSite=None; Secure",false);

	//шлём в ПП только если это не дубль	
	if (!$is_duplicate){ 
		//если у формы прописан в action адрес, а не локальный скрипт, то шлём все данные формы на этот адрес
		if (substr($black_land_conversion_script, 0, 4 ) === "http"){ 
			//var_dump(http_build_query($_POST));
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $black_land_conversion_script,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => false,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => http_build_query($_POST), 
			  CURLOPT_HTTPHEADER => array(
				"Content-Type: application/x-www-form-urlencoded"
			  ),
			));
			$result = curl_exec($curl);
			$response = curl_getinfo($curl);
			curl_close($curl);
			//в ответе должен быть редирект, если его нет - грузим обычную страницу Спасибо кло
			switch($response["http_code"]){
				case 302:
					write_leads_to_log($subid,$name,$phone,'');
					header("Location: ".$response["redirect_url"]);
					break;
				case 200:
					write_leads_to_log($subid,$name,$phone,'');
					Redirect("thankyou.php?".http_build_query($_GET));
					break;
				default:
					var_dump($response);
					exit();
					break;
			}
		}
		//если локальный скрипт - пробуем его вызвать через include и потом редиректим на страницу Спасибо кло
		else{
			include_once $_COOKIE['landing'].'/'.$black_land_conversion_script;
			header_remove("Location"); //удаляем редирект из файла заказа ПП
			//пишем лида в базу и редиректим на Спасибо
			write_leads_to_log($subid,$name,$phone,'');
			Redirect("thankyou.php?".http_build_query($_GET));
		}
	}
	else //если это дубль - тупо шлём на страницу Спасибо и привет)
	{
		header("Location: thankyou.php");
	}
	
	function Redirect($url){
		echo "<script type='text/javascript'> window.location='$url';</script>";
		return;
	}
?>