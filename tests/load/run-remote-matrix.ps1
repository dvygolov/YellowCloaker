param(
    [ValidateSet('smoke', 'core', 'full')]
    [string]$Suite = 'core',
    [int]$Rate = 8,
    [int]$DurationSeconds = 30,
    [string[]]$Include = @(),
    [string]$ResultSuffix = '',
    [string]$Target = 'ywbtest',
    [string]$TargetKey = 'C:\Users\ratta\.ssh\id_ed25519_ywbtestdo_20260306',
    [string]$TargetDomain = 'ywbtest.site',
    [string]$TargetAppRoot = '/var/www/ywbtest.site/fromfolder',
    [ValidateSet('http', 'https')]
    [string]$TargetScheme = 'https',
    [string]$ResultSet = 'ywbtest',
    [switch]$InsecureTls,
    [string]$Generator = 'root@164.90.189.49',
    [string]$GeneratorKey = 'C:\Users\ratta\.ssh\id_ed25519_ywbtestdo_20260306'
)

$ErrorActionPreference = 'Stop'
if ($ResultSuffix -notmatch '^[A-Za-z0-9._-]*$') {
    throw 'ResultSuffix may contain only letters, digits, dots, underscores, and hyphens.'
}
if ($TargetDomain -notmatch '^[A-Za-z0-9.-]+$') {
    throw 'TargetDomain contains unsafe characters.'
}
if ($TargetAppRoot -notmatch '^/[A-Za-z0-9._/-]+$') {
    throw 'TargetAppRoot must be a safe absolute Unix path.'
}
if ($ResultSet -notmatch '^[A-Za-z0-9._-]+$') {
    throw 'ResultSet may contain only letters, digits, dots, underscores, and hyphens.'
}
$ssh = 'C:\Program Files\OpenSSH\ssh.exe'
$targetSshArgs = @('-o', 'BatchMode=yes')
if ($TargetKey) { $targetSshArgs += @('-i', $TargetKey) }
$targetSshArgs += $Target
$backupDir = (& $ssh @targetSshArgs 'cat /root/yellowtds-loadtest-backup-dir').Trim()
if ($LASTEXITCODE -ne 0 -or $backupDir -notlike '/var/backups/yellowtds-loadtest/*') {
    throw 'Could not resolve the safe target backup directory.'
}
& $ssh @targetSshArgs "mkdir -p '$backupDir/results'"
if ($LASTEXITCODE -ne 0) {
    throw 'Could not prepare the target result directory.'
}
$targetEnvironment = "YELLOWTDS_BENCH_DOMAIN=$TargetDomain YELLOWTDS_BENCH_APP_ROOT=$TargetAppRoot YELLOWTDS_BENCH_SCHEME=$TargetScheme"
$baseUrl = "${TargetScheme}://${TargetDomain}"

function New-Case {
    param([string]$Name, [hashtable]$Options = @{}, [string]$Traffic = 'black', [string]$Identity = 'new')
    $defaults = @{
        campaigns = 1; 'campaign-match' = 'first'; flows = 1; 'flow-match' = 'first'
        filters = 0; condition = 'AND'; 'filter-match' = 'late'
        uniqueness = 'off'; 'uniqueness-scope' = 'off'; caps = 'off'; 'cap-checks' = 1
        clicks = 0; conversions = 0; 'cap-state' = 'open'
        distribution = 'equal'; steps = 1; variants = 1; response = 'redirect'
    }
    foreach ($key in $Options.Keys) { $defaults[$key] = $Options[$key] }
    [pscustomobject]@{ Name = $Name; Options = $defaults; Traffic = $Traffic; Identity = $Identity }
}

$cases = @(
    (New-Case 'matrix-baseline'),
    (New-Case 'campaigns-0010-first' @{ campaigns = 10; 'campaign-match' = 'first' }),
    (New-Case 'campaigns-0010-last' @{ campaigns = 10; 'campaign-match' = 'last' }),
    (New-Case 'campaigns-0050-last' @{ campaigns = 50; 'campaign-match' = 'last' }),
    (New-Case 'campaigns-0200-last' @{ campaigns = 200; 'campaign-match' = 'last' }),
    (New-Case 'campaigns-1000-first' @{ campaigns = 1000; 'campaign-match' = 'first' }),
    (New-Case 'campaigns-1000-last' @{ campaigns = 1000; 'campaign-match' = 'last' }),
    (New-Case 'flows-0005-last' @{ flows = 5; 'flow-match' = 'last'; filters = 1 }),
    (New-Case 'flows-0020-last' @{ flows = 20; 'flow-match' = 'last'; filters = 1 }),
    (New-Case 'flows-0050-last' @{ flows = 50; 'flow-match' = 'last'; filters = 1 }),
    (New-Case 'flows-0050-none' @{ flows = 50; 'flow-match' = 'none'; filters = 1 }),
    (New-Case 'filters-0005-and' @{ filters = 5; condition = 'AND' }),
    (New-Case 'filters-0020-and' @{ filters = 20; condition = 'AND' }),
    (New-Case 'filters-0050-and' @{ filters = 50; condition = 'AND' }),
    (New-Case 'filters-0050-and-fail-early' @{ filters = 50; condition = 'AND'; 'flow-match' = 'none'; 'filter-match' = 'early' }),
    (New-Case 'filters-0050-and-fail-late' @{ filters = 50; condition = 'AND'; 'flow-match' = 'none'; 'filter-match' = 'late' }),
    (New-Case 'filters-0050-or-match-early' @{ filters = 50; condition = 'OR'; 'filter-match' = 'early' }),
    (New-Case 'filters-0050-or-match-late' @{ filters = 50; condition = 'OR'; 'filter-match' = 'late' }),
    (New-Case 'unique-003k-new' @{ uniqueness = 'on'; clicks = 3000 } 'black' 'new'),
    (New-Case 'unique-003k-repeat' @{ uniqueness = 'on'; clicks = 3000 } 'black' 'repeat'),
    (New-Case 'unique-100k-new' @{ uniqueness = 'on'; clicks = 100000 } 'black' 'new'),
    (New-Case 'unique-100k-repeat' @{ uniqueness = 'on'; clicks = 100000 } 'black' 'repeat'),
    (New-Case 'unique-1m-new' @{ uniqueness = 'on'; clicks = 1000000 } 'black' 'new'),
    (New-Case 'unique-1m-repeat' @{ uniqueness = 'on'; clicks = 1000000 } 'black' 'repeat'),
    (New-Case 'caps-campaign-100k-c01-open' @{ caps = 'campaign'; 'cap-checks' = 1; conversions = 100000 }),
    (New-Case 'caps-campaign-100k-c20-open' @{ caps = 'campaign'; 'cap-checks' = 20; conversions = 100000 }),
    (New-Case 'caps-campaign-100k-c01-reached' @{ caps = 'campaign'; 'cap-checks' = 1; conversions = 100000; 'cap-state' = 'reached' }),
    (New-Case 'caps-flow-100k-c01-open' @{ caps = 'flow'; 'cap-checks' = 1; conversions = 100000 }),
    (New-Case 'caps-flow-100k-c20-open' @{ caps = 'flow'; 'cap-checks' = 20; conversions = 100000 }),
    (New-Case 'realistic-10x5-unique-caps' @{ flows = 10; 'flow-match' = 'last'; filters = 5; uniqueness = 'on'; caps = 'campaign'; 'cap-checks' = 5; clicks = 100000; conversions = 10000 }),
    (New-Case 'extreme-50x50-unique-caps' @{ flows = 50; 'flow-match' = 'last'; filters = 50; uniqueness = 'on'; caps = 'flow'; 'cap-checks' = 20; clicks = 100000; conversions = 10000 }),
    (New-Case 'response-white' @{ response = 'white' } 'white'),
    (New-Case 'response-html' @{ response = 'html' }),
    (New-Case 'response-jsconnect' @{ response = 'jsconnect' } 'jsconnect'),
    (New-Case 'response-mixed' @{ response = 'mixed' } 'mixed'),
    (New-Case 'distribution-weighted' @{ distribution = 'weighted'; variants = 5 }),
    (New-Case 'distribution-thompson' @{ distribution = 'thompson'; variants = 5 }),
    (New-Case 'steps-05-equal' @{ steps = 5 }),
    (New-Case 'steps-10-equal' @{ steps = 10 })
)

if ($Suite -eq 'smoke') {
    $names = @('matrix-baseline', 'campaigns-1000-last', 'flows-0050-last', 'filters-0050-and', 'unique-100k-new', 'unique-100k-repeat', 'caps-campaign-100k-c20-open', 'caps-flow-100k-c20-open', 'realistic-10x5-unique-caps', 'extreme-50x50-unique-caps')
    $cases = $cases | Where-Object Name -In $names
}
elseif ($Suite -eq 'full') {
    foreach ($count in 1, 5, 20) {
        $cases += New-Case "caps-campaign-010k-c$count-open" @{ caps = 'campaign'; 'cap-checks' = $count; conversions = 10000 }
        $cases += New-Case "caps-flow-010k-c$count-open" @{ caps = 'flow'; 'cap-checks' = $count; conversions = 10000 }
    }
    $cases += New-Case 'unique-filter-campaign-new' @{ uniqueness = 'on'; 'uniqueness-scope' = 'campaign'; clicks = 100000 } 'black' 'new'
    $cases += New-Case 'unique-filter-flow-new' @{ uniqueness = 'on'; 'uniqueness-scope' = 'flow'; clicks = 100000 } 'black' 'new'
}

if ($Include.Count -gt 0) {
    $cases = @($cases | Where-Object Name -In $Include)
    if ($cases.Count -eq 0) { throw 'No cases matched -Include.' }
}

foreach ($case in $cases) {
    $name = $case.Name + $ResultSuffix
    Write-Host "CASE_START $name"
    $setupArgs = @('--confirm=YELLOWTDS_LOADTEST', "--domain=$TargetDomain")
    foreach ($key in ($case.Options.Keys | Sort-Object)) {
        $value = [string]$case.Options[$key]
        if ($value -notmatch '^[A-Za-z0-9._-]+$') { throw "Unsafe option value for $key" }
        $setupArgs += "--$key=$value"
    }
    $setupCommand = "php '$TargetAppRoot/../loadtest/setup_matrix.php' " + ($setupArgs -join ' ') + " > '$backupDir/results/$name.config.json'" +
        " && chown www-data:www-data '$TargetAppRoot/db/clicks.db'" +
        " && chmod 0664 '$TargetAppRoot/db/clicks.db'" +
        " && chown -R www-data:www-data '$TargetAppRoot/logs'" +
        " && cat '$backupDir/results/$name.config.json'"
    & $ssh @targetSshArgs $setupCommand
    if ($LASTEXITCODE -ne 0) { throw "Setup failed for $name" }

    $targetOut = Join-Path $env:TEMP "yellowtds-$name-target.out"
    $targetErr = Join-Path $env:TEMP "yellowtds-$name-target.err"
    $generatorOut = Join-Path $env:TEMP "yellowtds-$name-generator.out"
    $generatorErr = Join-Path $env:TEMP "yellowtds-$name-generator.err"
    # Start target telemetry first and keep a 15-second tail so SSH startup
    # jitter never truncates the nginx/FPM sample before k6 finishes.
    $targetDuration = $DurationSeconds + 15
    $targetProcess = Start-Process $ssh -ArgumentList ($targetSshArgs + "$targetEnvironment /root/run-target-case.sh $name $targetDuration '$backupDir'") -PassThru -NoNewWindow -RedirectStandardOutput $targetOut -RedirectStandardError $targetErr
    & $ssh @targetSshArgs "for i in `$(seq 1 150); do test -f '/run/yellowtds-benchmark-$name.ready' && exit 0; sleep 0.1; done; exit 1"
    if ($LASTEXITCODE -ne 0) {
        throw "Target telemetry did not become ready for $name"
    }
    $generatorEnvironment = if ($InsecureTls) { 'YELLOWTDS_INSECURE_TLS=1 ' } else { '' }
    $generatorProcess = Start-Process $ssh -ArgumentList @('-o', 'BatchMode=yes', '-i', $GeneratorKey, $Generator, "$generatorEnvironment/opt/yellowtds-loadtest/run-generator-case.sh $name $Rate $DurationSeconds $($case.Traffic) $($case.Identity) $baseUrl $ResultSet") -PassThru -NoNewWindow -RedirectStandardOutput $generatorOut -RedirectStandardError $generatorErr
    $targetProcess.WaitForExit()
    $generatorProcess.WaitForExit()
    Get-Content $targetOut, $generatorOut
    if ($targetProcess.ExitCode -ne 0 -or $generatorProcess.ExitCode -ne 0) {
        Get-Content $targetErr, $generatorErr -ErrorAction SilentlyContinue
        throw "Case $name failed: target=$($targetProcess.ExitCode), generator=$($generatorProcess.ExitCode)"
    }
    Remove-Item -LiteralPath $targetOut, $targetErr, $generatorOut, $generatorErr -Force -ErrorAction SilentlyContinue
    Write-Host "CASE_DONE $name"
}
