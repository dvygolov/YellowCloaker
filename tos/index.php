<?php
require_once __DIR__.'/../ipcountry.php';
$ip = getip();
$cc = getcountry($ip);
$filepath="tos/".$cc.".html";
if (!file_exists($filepath))
	$filepath="tos/EN.html";
echo file_get_contents($filepath);
?>