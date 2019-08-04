<?php
//ini_set("display_errors","1"); //Для отображение отладочной информации
require 'bnc.php';
$full_cloak_on=0; //если 1, то всегда возвращает whitepage, используем при модерации
$admin_ip='0.0.0.2'; //ip админа, не пишется в лог посетителей
$binom_cloaker=new Cloacker();
$binom_cloaker->os_white='Android,iOS,Windows'; //Список разрешённых ОС
$binom_cloaker->country_white='ES,RU'; //Строка двухбуквенных обозначений стран через запятую, допущенных к blackpage
$binom_cloaker->ip_black='0.0.0.1';//Доп. список адресов через запятую, которые будут отправлены на white page
$binom_cloaker->tokens_black=''; //Список слов через запятую, при наличии которых в адресе перехода (в ссылке, по которой перешли), юзер будет отправлен на whitepage
$binom_cloaker->ua_black='facebook,Facebot,curl,gce-spider,yandex.com/bots'; //Список слова через запятую, при наличии которых в UserAgent, юзер будет отправлен на whitepage
$binom_cloaker->referer='0'; //при ='1' все запросы без referer будут идти на whitepage
$check_result=$binom_cloaker->check();

//запись посетителей в файл visitors.txt
write_visitors_to_log();

//если включен full_cloak_on, то шлём всех на white page, полностью набрасываем плащ)
if ($full_cloak_on==1)
{
	echo load_content('/white/Mysite.html');
	return;
}

if($check_result==0) //Обычный юзверь
{
  //A/B тестирование лендингов
  //TODO:добавить при тестировании проброс суб-метки с номером ленда
  $landings=array('/land/index.html');
  $r=rand(0,count($landings)-1);
  //1.если у вас html лендинг, то вам нужна эта часть кода
  echo load_content($landings[$r]);
  //конец части для html-лендингов
  //2.если у вас php лендинг, то вам нужна вот эта часть кода
  /*ob_start();
  include $landings[$r];
  $result = ob_get_clean();
  echo $result;*/
  //конец части для php лендингов
} 
else //Обнаружили бота или модера
{
	echo load_content('/white/Mysite.html');
}

//Подгрузка контента из другой папки через CURL
function load_content($url)
{
  $domain = $_SERVER['HTTP_HOST'];
  $prefix = $_SERVER['HTTPS'] ? 'https://' : 'http://';
  $fullpath=$prefix.$domain.$url;
  $curl = curl_init();
  // define options
  $optArray = array(
    CURLOPT_URL => $fullpath,
    CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYPEER => false,
   );
  curl_setopt_array($curl, $optArray);
  $blackbody= curl_exec($curl);
  curl_close($curl);
  $parts=explode('/',$url);
  $baseurl= '/'.$parts[1].'/';
  //переписываем все относительные src,href & action (не начинающиеся с http)
  $blackbody = preg_replace('/\ssrc=[\'\"](?!http)([^\'\"]+)[\'\"]/', " src=\"$baseurl\\1\"", $blackbody);
  $blackbody = preg_replace('/\shref=[\'\"](?!http|#)([^\'\"]+)[\'\"]/', " href=\"$baseurl\\1\"", $blackbody);
  $blackbody = preg_replace('/\saction=[\'\"](?!http)([^\'\"]+)[\'\"]/', " action=\"$baseurl\\1\"", $blackbody);
  //добавляем в страницу GTM
  $gtm_id=file_get_contents('gtm.txt');
  if(!empty($gtm_id)){
	$blackbody=insert_gtm_tag($blackbody,$gtm_id);
  }
  //если в querystring есть id пикселя фб, то встраиваем его скрытым полем в форму на лендинге
  //чтобы потом передать его на страницу "Спасибо" через send.php и там отстучать Lead - норм схемка, да?))
  $fb_pixel=$_GET["fbpixel"];
  $fb_input='<input type="hidden" name="fbpixel" value="'.$fb_pixel.'"/>';
  $needle='</form>';
  $lastPos = 0;
  $positions = array();
  while (($lastPos = strpos($blackbody, $needle, $lastPos))!== false) {
	$positions[] = $lastPos;
	$lastPos = $lastPos + strlen($needle);
  }
  $positions=array_reverse($positions);
  
  foreach ($positions as $pos) {
	if (!empty($fb_pixel)){
      $blackbody=substr_replace($blackbody,$fb_input,$pos,0);
	}
  }
 
  return $blackbody;
}

//если задан ID Google Tag Manager, то создаём его скрипт
function insert_gtm_tag($html,$gtm_id){
	if (!empty($gtm_id)){
		$needle='</head>';
		$pos=strpos($html,$needle,0);
		$gtm_text="<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','".$gtm_id."');</script>";
		$html=substr_replace($html,$gtm_text,$pos,0);
	}
	return $html;
}

//Запись всех посетителей в csv файл
function write_visitors_to_log()
{
	global $binom_cloaker, $admin_ip, $check_result;
	$calledIp=$binom_cloaker->detect['ip'];
	
	if ($calledIp!=$admin_ip){
	  $file = "visitors.csv";
	  $time = date('Y-m-d H:i:s');  
	  $os= $binom_cloaker->detect['os'];
	  $user_agent=$binom_cloaker->detect['ua'];
	  
	  if (isset($binom_cloaker->result))
		$reason=implode(";",$binom_cloaker->result);
	  else
		$reason="";
	  $country=$binom_cloaker->detect['country'];
	  $message = "$calledIp, $country, $time, $check_result, $reason, $os, $user_agent \n";
	  $save_order = fopen($file, 'a+');
	  fwrite($save_order, $message);
	  fflush($save_order);
	  fclose($save_order);
	}
}
?>
