<?php
require_once __DIR__.'/dates.php';
require_once __DIR__.'/timezones.php';
require_once __DIR__.'/../debug.php';
require_once __DIR__.'/../paths.php';
function get_bases_version(): string
{
    $basesDir = __DIR__ . "/../bases";
    if (!is_dir($basesDir)) {
        return "Bases directory NOT FOUND";
    }

    $missing = [];
    foreach (['country.mmdb', 'asn.mmdb'] as $file) {
        if (!is_readable($basesDir . "/" . $file)) {
            $missing[] = $file;
        }
    }
    if (!empty($missing)) {
        return "Missing: " . implode(', ', $missing);
    }

    $updateFile = __DIR__ . "/../bases/update.txt";
    if (!file_exists($updateFile)) {
        return "Found";
    }
    return file_get_contents($updateFile);
}

$calDs = Dates::get_calend_dates();
$cdStr = $calDs[0] === $calDs[1] ? $calDs[0] : "{$calDs[0]} - {$calDs[1]}";

$headerPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$hideDateControl = $headerPage === 'campsettings.php';
$headerTimezone = 'UTC';
$headerTimezoneScope = 'common';
$headerCampId = null;

if (isset($c) && isset($c->statistics) && isset($c->statistics->timezone)) {
    $headerTimezone = $c->statistics->timezone;
    $headerTimezoneScope = 'campaign';
    $headerCampId = isset($campId) ? (int)$campId : null;
} elseif (isset($tz) && is_string($tz) && $tz !== '') {
    $headerTimezone = $tz;
    if (isset($campId) && $campId !== null && (($view ?? '') !== 'trafficback')) {
        $headerTimezoneScope = 'campaign';
        $headerCampId = (int)$campId;
    }
} elseif (isset($gs['statistics']['timezone'])) {
    $headerTimezone = $gs['statistics']['timezone'];
}

$headerDateConfig = [
    'enabled' => !$hideDateControl,
    'timezone' => $headerTimezone,
    'timezoneShort' => get_timezone_short_label($headerTimezone),
    'scope' => $headerTimezoneScope,
    'campId' => $headerCampId,
    'options' => get_timezone_options(),
];
?>
<div class="header-advance-area">
    <div class="header-top-area">
        <div class="container-fluid header-shell">
                <div class="header-brand-area">
                    <div class="logo-pro">
                        <div class="logo-container">
                            <a href="index.php?startdate=<?=$calDs[0]?>&enddate=<?=$calDs[1]?>" class="logo-link">
                                <img class="main-logo" src="<?=get_admin_base_url()?>img/logo.png" alt="" />
                            </a>
                            <div class="geo-version">
                                <?php 
                                    $basesVersion = get_bases_version(); 
                                    $basesClass = str_starts_with($basesVersion, 'Missing:') || str_ends_with($basesVersion, 'NOT FOUND') ? 'text-danger' : '';
                                    $basesEncoded = htmlspecialchars($basesVersion, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                ?>
                                GeoBases: <span class="<?=$basesClass?>"><?=$basesEncoded?></span>
                                <?php if (DebugMethods::on()): ?>
                                <span style="color: red; margin-left: 10px;">Debug Mode</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="header-actions-area">
                    <div class="header-right-info">
                        <ul class="nav navbar-nav mai-top-nav header-right-menu">
                            <li class="nav-item">
                                <?php if (!$hideDateControl): ?>
                                <a class="nav-link" id='litepicker'>
                                    <i class="bi bi-calendar"></i>
                                    <span>
                                        Date:&nbsp;&nbsp;<?= $cdStr ?> · <?= htmlspecialchars(get_timezone_short_label($headerTimezone)) ?>
                                    </span>
                                </a>
                                <?php endif; ?>
                                <a class="nav-link" href="#" id="openSettings">
                                    <i class="bi bi-gear"></i>
                                    <span>Settings</span>
                                </a>
                                <a class="nav-link" href="logout.php">
                                    <i class="bi bi-door-closed"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
        </div>
    </div>
</div>
<script id="headerDateConfig" type="application/json">
    <?=json_encode($headerDateConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<?php include __DIR__ . '/settingsmodal.php'; ?>
<div class="overlay" id="updateOverlay">
    <canvas id="matrix-rain"></canvas>
    <div class="grid-overlay"></div>
    <div class="updating-text">
        <span id="typing-text"></span><span class="cursor">█</span>
    </div>
</div>
