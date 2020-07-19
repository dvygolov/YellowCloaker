<?php

function write_white_to_log($data,$reason,$check_result,$preland,$land) {
	$filename = date("d.m.y").".blocked.csv";
	write_visitors_to_log($filename,$data,$reason,$check_result,$preland,$land);
}

function write_black_to_log($data,$reason,$check_result,$preland,$land) {
	$filename = date("d.m.y").".csv";
	write_visitors_to_log($filename,$data,$reason,$check_result,$preland,$land);
}

function write_leads_to_log($subid,$name,$phone) {
	$file = "logs/".date("d.m.y").".leads.csv";
	if(!file_exists($file)) //если новый день а файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,Name,Phone,Fbp,Fbclid,Status\n");
		fflush($save_order);
		fclose($save_order);
	}
	
	$fbp=isset($_COOKIE['_fbp'])?$_COOKIE['_fbp']:'';
	$fbclid=isset($_COOKIE['fbclid'])?$_COOKIE['fbclid']:'';
	
	$save_order = fopen($file, 'a+');
	fwrite($save_order, "$subid, $name, $phone, $fbp, $fbclid, Lead\n");
	fflush($save_order);
	fclose($save_order);
}

//проверяем, есть ли в файле лидов subid текущего пользователя
//если есть, значит ЭТО ДУБЛЬ! И нам не нужно слать его в ПП и не нужно показывать пиксель ФБ!!
function lead_is_duplicate($subid,$phone){
	$file = "logs/".date("d.m.y").".leads.csv";
	if(!file_exists($file)) return false;
	if($subid!='')
		return strpos(file_get_contents($file),$subid);
	else 
		//если куки c subid у нас почему-то нет, то проверяем по номеру телефона
		return strpos(file_get_contents($file),$phone);
}

//Запись норм посетителей в csv файл
function write_visitors_to_log($filename,$data,$reason,$check_result,$preland,$land) {
	$calledIp = $data['ip'];
	$country = $data['country'];
	$time = date('Y-m-d H:i:s');
	$os = $data['os'];

	$user_agent = str_replace(',',' ',$data['ua']); //заменяем все зпт на пробелы, чтобы в csv-файле это было одним полем
	$reason_str = isset($reason)? implode(";", $reason):'';

	$qsarray=explode('&',$_SERVER['QUERY_STRING']);
	sort($qsarray);
	$querystring=implode('&',$qsarray);
	$cursubid=isset($_COOKIE['subid'])?$_COOKIE['subid']:'';

	$message = "$cursubid, $calledIp, $country, $time, $check_result, $reason_str, $os, $user_agent, $querystring, $preland, $land \n";

	//создаёт папку для логов, если её нет
	if (!file_exists("logs")) mkdir("logs");
	
	$file = "logs/".$filename;
	if(!file_exists($file)) //если новый день и файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,IP,Country,Time,Is Denied?,Deny Reason,OS,UA,QueryString,Preland,Land\n");
		fflush($save_order);
		fclose($save_order);
	}
	
	$save_order = fopen($file, 'a+');
	fwrite($save_order, $message);
	fflush($save_order);
	fclose($save_order);
}
?>