<?php
//ini_set("display_errors","1"); //Для отображение отладочной информации
require 'bnc.php';
$full_cloak_on=0; //если 1, то всегда возвращает whitepage, используем при модерации
$admin_ip='0.0.0.2'; //ip админа не пишется в лог посетителей
$binom_cloaker=new Cloacker();
$binom_cloaker->os_white='Windows, Windows Phone, OS X, iOS, Android, Chrome OS, Linux, 
    SymbOS, Nokia, BlackBerry, FreeBSD, OpenBSD, NetBSD, OpenSolaris, SunOS, 
    OS2, BeOS'; //Список разрешённых ОС
$binom_cloaker->country_white='RU'; //Строка двухбуквенных обозначений стран через запятую, допущенных к blackpage
$binom_cloaker->ip_black='0.0.0.1';//Доп. список адресов через запятую, которые будут отправлены на white page
$binom_cloaker->tokens_black=''; //Список слов через запятую, при наличии которых в адресе перехода (в ссылке, по которой перешли), юзер будет отправлен на whitepage
$binom_cloaker->referer='0'; //при ='1' все запросы без referer будут идти на whitepage
$check_result=$binom_cloaker->check();

//запись посетителей в файл visitors.txt
$calledIp=$binom_cloaker->getip();
if ($calledIp!=$admin_ip){
  $file = "visitors.txt";
  $time = date('Y-m-d H:i:s');  
  $os= $binom_cloaker->detect['os'];
  $user_agent=$_SERVER['HTTP_USER_AGENT'];
  $reason=implode(",",$binom_cloaker->result);
  $country=$binom_cloaker->detect['country'];
  $message = "$calledIp, $country, $time, $check_result, $reason, $os, $user_agent \n";
  $save_order = fopen($file, 'a+');
  fwrite($save_order, $message);
  fclose($save_order);
}
//конец записи посетителей

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on==1)
{
	$whitePage=file_get_contents("indexw.html");
	echo $whitePage;
	return;
}

if($check_result==0) //Обычный юзверь
{
  //A/B тестирование лендингов
  $landings=array("indexb.html");
  $r=rand(0,count($landings)-1);
  echo file_get_contents($landings[$r]);
} 
else //Обнаружили бота или модера
{
	$whitePage=file_get_contents("indexw.html");
	echo $whitePage;
}
?>