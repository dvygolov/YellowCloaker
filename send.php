<?php
	include 'settings.php';
	
	//TODO: добавить запись кук фб и fbclid
	$name = isset($_POST['name'])?$_POST['name']:$_POST['fio'];
	$phone = isset($_POST['phone'])?$_POST['phone']:$_POST['tel'];
	$subid = isset($_COOKIE['subid'])?$_COOKIE['subid']:'';
	
	$file = "leads/".date("d.m.y").".csv";
	if(!file_exists($file)) //если новый день а файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,Name,Phone,FB,Status\n");
		fflush($save_order);
		fclose($save_order);
	}
	
	$is_duplicate=false;
	if($subid!='')
		//проверяем, есть ли в файле лидов subid текущего пользователя
		//если есть, значит ЭТО ДУБЛЬ! И нам не нужно слать его в ПП и не нужно показывать пиксель ФБ!!
		$is_duplicate = strpos(file_get_contents($file),$subid);
	else 
		//если куки у нас почему-то нет, то проверяем по номеру телефона
		$is_duplicate = strpos(file_get_contents($file),$phone);

	//шлём в ПП только если это не дубль	
	if (!$is_duplicate){ 
		include_once $_COOKIE['landing'].'/'.$black_land_conversion_script;
		header_remove("Location"); //удаляем редирект из файла заказа ПП
		//пишем лида в базу и редиректим на Спасибо
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "$subid, $name, $phone,,Lead\n");
		fflush($save_order);
		fclose($save_order);
		header("Location: thankyou.php?".http_build_query($_GET));
	}
	else
	{
		header_remove("Location"); //удаляем редирект из файла заказа ПП
		header("Location: thankyou.php");
	}
?>