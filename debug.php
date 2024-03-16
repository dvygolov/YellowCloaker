<?php

class DebugMethods
{

    static bool $on = true;
    static float $start_time;

    public static function start(): void
    {
        if (!DebugMethods::$on) return;
        DebugMethods::$start_time = microtime(true);
    }

    public static function stop($header_name): void
    {
        if (!DebugMethods::$on) return;
        $time_elapsed_secs = microtime(true) - DebugMethods::$start_time;
        header($header_name.": " . $time_elapsed_secs);
    }

    public static function display_errors(): void
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    public static function check_php():void
    {
        $ver=phpversion();
        if (version_compare($ver, '8.2.0', '<'))
            die("PHP version should be 8.2 or higher! Change your PHP version and return.");
    }
}
DebugMethods::display_errors();
DebugMethods::check_php();