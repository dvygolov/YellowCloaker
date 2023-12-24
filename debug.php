<?php

class DebugMethods
{

    static float $start_time;

    public static function start(): void
    {
       DebugMethods::$start_time = microtime(true);
    }

    public static function stop($header_name): void
    {
        $time_elapsed_secs = microtime(true) - DebugMethods::$start_time;
        header($header_name.": " . $time_elapsed_secs);
    }

    public static function display_errors(): void
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }
}
DebugMethods::display_errors();