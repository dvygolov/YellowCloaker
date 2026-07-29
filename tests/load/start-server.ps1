# Start 8 php-cgi FastCGI workers + Caddy reverse proxy
# Usage: .\tests\load\start-server.ps1

param(
    [int]$Workers = 16,
    [int]$BasePort = 9000
)

$ErrorActionPreference = "Stop"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectDir = Split-Path -Parent (Split-Path -Parent $scriptDir)

Write-Host "Starting $Workers php-cgi workers..." -ForegroundColor Cyan

$workerCmd = Join-Path $scriptDir "start-worker.cmd"
for ($i = 0; $i -lt $Workers; $i++) {
    $port = $BasePort + $i
    Start-Process -WindowStyle Hidden -FilePath "cmd.exe" -ArgumentList "/c", $workerCmd, $port -WorkingDirectory "$projectDir\code"
    Write-Host "  Worker $($i+1): 127.0.0.1:$port" -ForegroundColor Gray
}

Start-Sleep -Seconds 1
Write-Host "Starting Caddy..." -ForegroundColor Cyan
Start-Process -WindowStyle Hidden -FilePath "caddy" -ArgumentList "run", "--config", "$scriptDir\Caddyfile" -WorkingDirectory $projectDir

Start-Sleep -Seconds 2
Write-Host ""
Write-Host "Server ready at http://127.0.0.1:8080" -ForegroundColor Green
Write-Host "Workers: $Workers php-cgi processes (ports $BasePort-$($BasePort + $Workers - 1))"
Write-Host ""
Write-Host "To stop: .\tests\load\stop-server.ps1"
