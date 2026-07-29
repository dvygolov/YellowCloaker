# YellowTDS Load Test Runner
# Runs all scenarios sequentially with a pause between each.
#
# Usage:
#   .\tests\load\run.ps1                          # full run (standard profile)
#   .\tests\load\run.ps1 -Profile smoke           # quick smoke test
#   .\tests\load\run.ps1 -Profile smoke -BaseUrl http://remote:8080
#   .\tests\load\run.ps1 -Scenario mixed          # run only one scenario
#   .\tests\load\run.ps1 -Scenario events         # requires setup_events.php
#   .\tests\load\run.ps1 -Scenario events -StatsUrl "http://host/admin/statistics.php?campId={campaign_id}&table=0&password=..."

param(
    [string]$Profile = "standard",
    [string]$BaseUrl = "http://localhost:8080",
    [string]$Scenario = "",
    [string]$EventFixture = "",
    [string]$StatsUrl = ""
)

$ErrorActionPreference = "Stop"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$scenariosDir = Join-Path $scriptDir "k6\scenarios"
$eventFixturePath = if ($EventFixture -ne "") {
    [System.IO.Path]::GetFullPath($EventFixture)
} else {
    Join-Path $scriptDir ".events-fixture.json"
}

# Check k6 is installed
if (-not (Get-Command k6 -ErrorAction SilentlyContinue)) {
    Write-Host "ERROR: k6 is not installed." -ForegroundColor Red
    Write-Host "Install with: winget install Grafana.k6"
    Write-Host "         or: choco install k6"
    exit 1
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " YellowTDS Load Test Suite" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Profile:  $Profile"
Write-Host "Base URL: $BaseUrl"
Write-Host ""

# Define scenario order (most important first)
$scenarios = @(
    @{ Name = "white";         File = "white.js";         Desc = "White traffic (bots + filtered countries)" },
    @{ Name = "black";         File = "black.js";         Desc = "Black traffic (real users)" },
    @{ Name = "jsconnect";     File = "jsconnect.js";     Desc = "JS Connect flow (two-phase)" },
    @{ Name = "mixed";         File = "mixed.js";         Desc = "Mixed realistic traffic (main test)" },
    @{ Name = "sqlite-stress"; File = "sqlite-stress.js"; Desc = "SQLite write stress test" },
    @{ Name = "events";        File = "events.js";        Desc = "Events API ordinary + Performance writes"; RequiresFixture = $true }
)

# Filter to single scenario if specified
if ($Scenario -ne "") {
    $scenarios = @($scenarios | Where-Object { $_.Name -eq $Scenario })
    if ($scenarios.Count -eq 0) {
        Write-Host "ERROR: Unknown scenario '$Scenario'" -ForegroundColor Red
        Write-Host "Available: white, black, jsconnect, mixed, sqlite-stress, events, jsbotdetect"
        exit 1
    }
} elseif (-not (Test-Path -LiteralPath $eventFixturePath -PathType Leaf)) {
    $scenarios = @($scenarios | Where-Object { $_.Name -ne "events" })
    Write-Host "Events scenario skipped: run php tests/load/setup_events.php to create its scoped fixture." -ForegroundColor DarkGray
}

$requiresEventsFixture = @($scenarios | Where-Object { $_.RequiresFixture }).Count -gt 0
if ($requiresEventsFixture -and -not (Test-Path -LiteralPath $eventFixturePath -PathType Leaf)) {
    Write-Host "ERROR: Events fixture is missing: $eventFixturePath" -ForegroundColor Red
    Write-Host "Create it explicitly with: php tests/load/setup_events.php"
    exit 1
}

$totalScenarios = $scenarios.Count
$current = 0
$results = @()

foreach ($s in $scenarios) {
    $current++
    $file = Join-Path $scenariosDir $s.File

    Write-Host ""
    Write-Host "[$current/$totalScenarios] $($s.Desc)" -ForegroundColor Yellow
    Write-Host "  File: $file"
    Write-Host "  Starting..." -ForegroundColor Gray

    $startTime = Get-Date

    if ($s.RequiresFixture) {
        if ($StatsUrl -ne "") {
            k6 run `
                -e "BASE_URL=$BaseUrl" `
                -e "PROFILE=$Profile" `
                -e "EVENT_FIXTURE=$eventFixturePath" `
                -e "STATS_URL=$StatsUrl" `
                --summary-trend-stats="avg,min,med,max,p(90),p(95),p(99)" `
                $file
        } else {
            k6 run `
                -e "BASE_URL=$BaseUrl" `
                -e "PROFILE=$Profile" `
                -e "EVENT_FIXTURE=$eventFixturePath" `
                --summary-trend-stats="avg,min,med,max,p(90),p(95),p(99)" `
                $file
        }
    } else {
        k6 run `
            -e "BASE_URL=$BaseUrl" `
            -e "PROFILE=$Profile" `
            --summary-trend-stats="avg,min,med,max,p(90),p(95),p(99)" `
            $file
    }

    $exitCode = $LASTEXITCODE
    $duration = (Get-Date) - $startTime

    $status = if ($exitCode -eq 0) { "PASS" } else { "FAIL" }
    $color = if ($exitCode -eq 0) { "Green" } else { "Red" }

    Write-Host "  Result: $status (${duration:mm\:ss})" -ForegroundColor $color

    $results += @{
        Name = $s.Name
        Status = $status
        Duration = $duration
        ExitCode = $exitCode
    }

    # Pause between scenarios to let the server recover
    if ($current -lt $totalScenarios) {
        Write-Host "  Cooling down for 10 seconds..." -ForegroundColor Gray
        Start-Sleep -Seconds 10
    }
}

# Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " Results Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

foreach ($r in $results) {
    $color = if ($r.Status -eq "PASS") { "Green" } else { "Red" }
    $dur = $r.Duration.ToString("mm\:ss")
    Write-Host "  $($r.Name.PadRight(20)) $($r.Status.PadRight(6)) ($dur)" -ForegroundColor $color
}

$failed = ($results | Where-Object { $_.Status -eq "FAIL" }).Count
if ($failed -gt 0) {
    Write-Host ""
    Write-Host "$failed scenario(s) FAILED" -ForegroundColor Red
    exit 1
} else {
    Write-Host ""
    Write-Host "All scenarios PASSED" -ForegroundColor Green
}
