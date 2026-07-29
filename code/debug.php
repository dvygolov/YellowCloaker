<?php
require_once __DIR__.'/settings.php';
require_once __DIR__.'/logging.php';
class DebugMethods
{
    static array $start_times;

    public static function on(): bool
    {
        global $cloSettings;
        return $cloSettings['debug'];
    }
    public static function start(string $header_name): void
    {
        if (!self::on()) return;
        self::$start_times[$header_name] = microtime(true);
    }

    public static function stop(string $header_name): void
    {
        if (!self::on()) return;
        $time_elapsed_secs = microtime(true) - self::$start_times[$header_name];
        unset(self::$start_times[$header_name]);
        header($header_name.": " . $time_elapsed_secs . " sec.");
    }

    public static function display_errors(): void
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    public static function add_global_exception_handler():void
    {
        set_exception_handler(function (\Throwable $exception) {
            $msg = "Unhandled exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
            ytds_log('error', 'application', $msg);

            if ($exception instanceof RuntimeException && str_starts_with($exception->getMessage(), 'Configuration error:')) {
                http_response_code(500);
                echo htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                return;
            }

            echo "An unexpected error occurred. Please try again later.";
        });
    }

    public static function check_php():void
    {
        $ver=phpversion();
        if (str_ends_with($ver, '-dev'))
            $ver = str_replace('-dev', '', $ver);

        if (version_compare($ver, '8.2.0', '<'))
            die("PHP version should be 8.2.0 or higher! Change your PHP version and return.");
    }
    
    public static function check_sqlite():void
    {
        if (!extension_loaded('sqlite3')) 
            die("SQLite extension NOT FOUND! Use another hosting or enable SQLite.");
    }

    public static function check_curl():void
    {
        $exts = get_loaded_extensions();
        if (!extension_loaded('curl')) 
            die("cURL extension NOT FOUND! Use another hosting or enable cURL.");
    }
    
    public static function check_dirs():void
    {
        if (!is_writable(__DIR__)) 
            die("PHP doesn't have write access for the current directory. Change access rights!");
    }
}
if (DebugMethods::on()){
    DebugMethods::display_errors();
}

DebugMethods::add_global_exception_handler();
DebugMethods::check_php();
DebugMethods::check_curl();
DebugMethods::check_sqlite();
DebugMethods::check_dirs();
