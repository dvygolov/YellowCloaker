<?php

function write_white_to_log($data,$reason,$check_result,$preland,$land) {
	$filename = date("d.m.y").".blocked.csv";
	write_visitors_to_log('',$filename,$data,$reason,$check_result,$preland,$land);
}

function write_black_to_log($subid,$data,$reason,$check_result,$preland,$land) {
	$filename = date("d.m.y").".csv";
	write_visitors_to_log($subid,$filename,$data,$reason,$check_result,$preland,$land);
}

//пишем лиды в отдельный лог-файл
function write_leads_to_log($subid,$name,$phone,$status) {
	$file = __DIR__."/logs/".date("d.m.y").".leads.csv";
	if(!file_exists($file)) //если новый день а файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,Name,Phone,Fbp,Fbclid,Status\n");
		fflush($save_order);
		fclose($save_order);
	}
	
	$fbp=isset($_COOKIE['_fbp'])?$_COOKIE['_fbp']:'';
	$fbclid=isset($_COOKIE['fbclid'])?$_COOKIE['fbclid']:(isset($_COOKIE['_fbc'])?$_COOKIE['_fbc']:'');
	
	if ($status=='') $status='Lead';
	
	$save_order = fopen($file, 'a+');
	fwrite($save_order, "$subid, $name, $phone, $fbp, $fbclid, $status\n");
	fflush($save_order);
	fclose($save_order);
}

function write_mail_to_log($subid,$name,$phone,$email){
	$file = __DIR__."/logs/".date("d.m.y").".emails.csv";
	if(!file_exists($file)) //если новый день а файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,Name,Phone,Email\n");
		fflush($save_order);
		fclose($save_order);
	}

	$save_order = fopen($file, 'a+');
	fwrite($save_order, "$subid, $name, $phone, $email\n");
	fflush($save_order);
	fclose($save_order);
}

function write_lpctr_to_log($subid,$preland){
	$file = __DIR__."/logs/".date("d.m.y").".lpctr.csv";
	if(!file_exists($file)) //если новый день а файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,Preland\n");
		fflush($save_order);
		fclose($save_order);
	}
	
	$save_lp = fopen($file, 'a+');
	fwrite($save_lp, "$subid, $preland\n");
	fflush($save_lp);
	fclose($save_lp);
}

//проверяем, есть ли в файле лидов subid текущего пользователя
//если есть, и также есть такой же номер - значит ЭТО ДУБЛЬ! 
//И нам не нужно слать его в ПП и не нужно показывать пиксель ФБ!!
function lead_is_duplicate($subid,$phone){
	$file = __DIR__."/logs/".date("d.m.y").".leads.csv";
	if(!file_exists($file)) return false;
	if($subid!=''){
		$exist = strpos(file_get_contents($file),$subid);
		if ($exists>=0) {return strpos(file_get_contents($file),$phone);}
		else {return false;}
	}
	else {
		//если куки c subid у нас почему-то нет, то проверяем по номеру телефона
		return strpos(file_get_contents($file),$phone);
	}
}

//общая функция записи блэк и вайт посетителей в csv файл
function write_visitors_to_log($subid,$filename,$data,$reason,$check_result,$preland,$land) {
	$calledIp = $data['ip'];
	$country = $data['country'];
	$time = date('Y-m-d H:i:s');
	$os = $data['os'];
	$isp = str_replace(',',' ',$data['isp']);//заменяем все зпт на пробелы, чтобы в csv-файле это было одним полем
	$user_agent = str_replace(',',' ',$data['ua']); 
	$reason_str = isset($reason)? implode(";", $reason):'';

	$qsarray=explode('&',$_SERVER['QUERY_STRING']);
	sort($qsarray);
	$querystring=implode('&',$qsarray);

	$message = "$subid, $calledIp, $country, $isp, $time, $check_result, $reason_str, $os, $user_agent, $querystring, $preland, $land \n";

	//создаёт папку для логов, если её нет
	if (!file_exists("logs")) mkdir("logs");
	
	$file = __DIR__."/logs/".$filename;
	if(!file_exists($file)) //если новый день и файла лога ещё нет, то пишем туда заголовки столбцов
	{
		$save_order = fopen($file, 'a+');
		fwrite($save_order, "SubId,IP,Country,ISP,Time,Is Denied?,Deny Reason,OS,UA,QueryString,Preland,Land\n");
		fflush($save_order);
		fclose($save_order);
	}
	
	$save_order = fopen($file, 'a+');
	fwrite($save_order, $message);
	fflush($save_order);
	fclose($save_order);
}
?>