param(
    [int]$Port = 8080,
    [string]$Profile = 'comparison',
    [ValidateRange(1, 9)]
    [int]$Repeats = 5
)

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$workspaceDir = Split-Path -Parent $scriptDir
$scenario = Join-Path $scriptDir 'k6\scenarios\black.js'
$baselineSummary = Join-Path ([System.IO.Path]::GetTempPath()) ("yellowtds-baseline-{0}.json" -f [guid]::NewGuid())
$uniquenessSummary = Join-Path ([System.IO.Path]::GetTempPath()) ("yellowtds-uniqueness-{0}.json" -f [guid]::NewGuid())

function Run-Profile([string]$mode, [string]$summaryPath) {
    & php (Join-Path $scriptDir 'setup_campaign.php') $Port $mode
    if ($LASTEXITCODE -ne 0) { throw "Campaign setup failed for uniqueness=$mode" }

    & k6 run --quiet -e "BASE_URL=http://127.0.0.1:$Port" -e "PROFILE=$Profile" `
        --summary-trend-stats "avg,min,med,max,p(90),p(95),p(99)" `
        --summary-export $summaryPath $scenario
    if ($LASTEXITCODE -ne 0) { throw "k6 failed for uniqueness=$mode" }
}

function Read-P95([string]$summaryPath) {
    $summary = Get-Content -LiteralPath $summaryPath -Raw | ConvertFrom-Json
    $p95 = [double]$summary.metrics.http_req_duration.'p(95)'
    if ($p95 -le 0) {
        throw 'k6 summary did not contain a positive http_req_duration p(95) value'
    }
    return $p95
}

function Read-FailedRate([string]$summaryPath) {
    $summary = Get-Content -LiteralPath $summaryPath -Raw | ConvertFrom-Json
    return [double]$summary.metrics.http_req_failed.value
}

function Get-Median([double[]]$values) {
    $ordered = @($values | Sort-Object)
    $middle = [math]::Floor($ordered.Count / 2)
    if (($ordered.Count % 2) -eq 1) { return [double]$ordered[$middle] }
    return ([double]$ordered[$middle - 1] + [double]$ordered[$middle]) / 2
}

try {
    Push-Location $workspaceDir
    $baselineResults = @()
    $enabledResults = @()
    $pairedGrowthResults = @()
    $failedRates = @()

    for ($run = 1; $run -le $Repeats; $run++) {
        $modes = if (($run % 2) -eq 1) { @('off', 'on') } else { @('on', 'off') }
        foreach ($mode in $modes) {
            $summaryPath = if ($mode -eq 'off') { $baselineSummary } else { $uniquenessSummary }
            Run-Profile $mode $summaryPath
            $p95 = Read-P95 $summaryPath
            $failedRates += Read-FailedRate $summaryPath
            if ($mode -eq 'off') { $baselineResults += $p95 } else { $enabledResults += $p95 }
        }
        $pairBaseline = [double]$baselineResults[$run - 1]
        $pairEnabled = [double]$enabledResults[$run - 1]
        $pairedGrowthResults += (($pairEnabled - $pairBaseline) / $pairBaseline) * 100
    }

    $baselineP95 = Get-Median $baselineResults
    $enabledP95 = Get-Median $enabledResults
    $growth = Get-Median $pairedGrowthResults
    $failedRate = [double](($failedRates | Measure-Object -Maximum).Maximum)

    [pscustomobject]@{
        BaselineRunsMs = ($baselineResults | ForEach-Object { [math]::Round($_, 3) }) -join ', '
        UniquenessRunsMs = ($enabledResults | ForEach-Object { [math]::Round($_, 3) }) -join ', '
        PairedGrowthRunsPercent = ($pairedGrowthResults | ForEach-Object { [math]::Round($_, 2) }) -join ', '
        BaselineP95Ms = [math]::Round($baselineP95, 3)
        UniquenessP95Ms = [math]::Round($enabledP95, 3)
        GrowthPercent = [math]::Round($growth, 2)
        FailedRate = $failedRate
    } | Format-List

    if ($growth -gt 10) { throw "Uniqueness p95 growth is $([math]::Round($growth, 2))%, above 10%" }
    if ($failedRate -gt 0) { throw "Uniqueness run produced failed HTTP requests" }
} finally {
    Pop-Location -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $baselineSummary -Force -ErrorAction SilentlyContinue
    Remove-Item -LiteralPath $uniquenessSummary -Force -ErrorAction SilentlyContinue
}
