<?php
require_once __DIR__ . '/bases/ipcountry.php';
function add_log($subdir, $msg, $logIp = false)
{
    $dir = __DIR__ . "/logs/$subdir";
    if (!file_exists($dir))
        mkdir($dir, 0777, true);
    $date = date("d.m.y");
    $fileName = "$dir/$date.log";
    $file = fopen($fileName, 'a+');
    $time = date("Y-m-d H:i:s");
    if ($logIp) {
        $ip = getip();
        $time .= " IP:{$ip}";
    }
    $msg = "$time $msg\n";
    fwrite($file, $msg);
    fflush($file);
    fclose($file);
}