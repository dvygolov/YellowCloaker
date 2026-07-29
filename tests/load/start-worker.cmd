@echo off
set PHP_FCGI_MAX_REQUESTS=0
php-cgi -d xdebug.mode=off -b 127.0.0.1:%1
