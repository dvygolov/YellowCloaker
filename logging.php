<?php
//Запись всех посетителей в csv файл
function write_visitors_to_log($data,$reason,$check_result,$preland,$land) {
	$calledIp = $data['ip'];
	$country = $data['country'];
	$time = date('Y-m-d H:i:s');
	$os = $data['os'];

	$user_agent = str_replace(',',' ',$data['ua']); //заменяем все зпт на пробелы, чтобы в csv-файле это было одним полем
	$reason_str = isset($reason)? implode(";", $reason):'';

	$qsarray=explode('&',$_SERVER['QUERY_STRING']);
	sort($qsarray);
	$querystring=implode('&',$qsarray);
	$subid=isset($_COOKIE['subid'])?$_COOKIE['subid']:'';

	$message = "$subid, $calledIp, $country, $time, $check_result, $reason_str, $os, $user_agent, $querystring, $preland, $land \n";

	//создаёт папку для логов, если её нет
	if (!file_exists("logs")) mkdir("logs");
	
	$file = "logs/".date("d.m.y").".csv";
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