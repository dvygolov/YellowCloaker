# Stop all php-cgi workers and Caddy
taskkill /F /IM php-cgi.exe 2>$null
taskkill /F /IM caddy.exe 2>$null
Write-Host "Server stopped." -ForegroundColor Yellow
