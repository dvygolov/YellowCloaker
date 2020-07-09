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

	$message = "$calledIp, $country, $time, $check_result, $reason_str, $os, $user_agent, $querystring, $preland, $land \n";

	$file = "visitors.csv";
	$save_order = fopen($file, 'a+');
	fwrite($save_order, $message);
	fflush($save_order);
	fclose($save_order);
}
?>