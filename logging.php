<?php
//Запись всех посетителей в csv файл
function write_visitors_to_log() {
	global $cloacker,
	$admin_ip,
	$check_result;
	$calledIp = $cloacker->detect['ip'];

	if ($calledIp != $admin_ip) {
		$file = "visitors.csv";
		$time = date('Y-m-d H:i:s');
		$os = $cloacker->detect['os'];
		$user_agent = str_replace(',',' ',$cloacker->detect['ua']); //заменяем все зпт на пробелы, чтобы в csv-файле это было одним полем

		if (isset($cloacker->result))
			$reason = implode(";", $cloacker->result);
		else
			$reason = "";
		$country = $cloacker->detect['country'];
		$qsarray=explode('&',$_SERVER['QUERY_STRING']);
		sort($qsarray);
		$querystring=implode(',',$qsarray);
		$message = "$calledIp, $country, $time, $check_result, $reason, $os, $user_agent, $querystring \n";
		$save_order = fopen($file, 'a+');
		fwrite($save_order, $message);
		fflush($save_order);
		fclose($save_order);
	}
}
?>