<?php

require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/dates.php';
require_once __DIR__ . '/databasemaintenance.php';

$gs = $db->get_common_settings();
$timezone = (string)($gs['statistics']['timezone'] ?? 'UTC');
if (!in_array($timezone, timezone_identifiers_list(), true)) {
    $timezone = 'UTC';
}
[$calendarStart, $calendarEnd] = Dates::get_calend_dates();
$csrf = database_maintenance_csrf_token();
?>
<!doctype html>
<html lang="en">
<?php include __DIR__ . '/head.php'; ?>
<link rel="stylesheet" href="<?=get_admin_base_url()?>css/database.css?v=<?=filemtime(__DIR__ . '/css/database.css')?>">
<body class="database-page">
<?php include __DIR__ . '/header.php'; ?>

<main class="all-content-wrapper database-content">
    <div id="databaseAlert" class="database-alert" hidden></div>

    <section id="maintenanceProgress" class="maintenance-progress" hidden>
        <div class="maintenance-progress-head">
            <div>
                <strong id="maintenanceStatus">Database maintenance</strong>
                <span id="maintenanceMessage"></span>
            </div>
            <div class="maintenance-progress-actions">
                <button class="btn btn-primary" type="button" id="resumeMaintenance" hidden>Resume</button>
                <button class="btn btn-warning" type="button" id="retryCompaction" hidden>Retry compaction</button>
                <button class="btn btn-outline-danger" type="button" id="cancelMaintenance" hidden>Stop</button>
            </div>
        </div>
        <div class="maintenance-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <span id="maintenanceProgressBar"></span>
        </div>
        <div class="maintenance-result" id="maintenanceResult" hidden></div>
    </section>

    <section class="database-section">
        <div class="database-section-head">
            <h1>Delete clicks</h1>
            <div class="database-section-actions">
                <button class="btn btn-outline-warning" type="button" id="compactDatabase"><i class="bi bi-arrows-collapse"></i> Compact database</button>
                <button class="btn btn-danger" type="button" id="cleanupDatabase" disabled><i class="bi bi-trash3"></i> Clean selected</button>
            </div>
        </div>
        <div class="database-table-wrap">
            <table class="database-table">
                <thead>
                    <tr>
                        <th class="database-check"><input type="checkbox" id="selectAllCampaigns" aria-label="Select all statistics"></th>
                        <th>Campaign</th>
                        <th>Clicks in range</th>
                        <th>Blocked in range</th>
                        <th>Total in range</th>
                        <th>Total stored</th>
                        <th>Stored dates</th>
                    </tr>
                </thead>
                <tbody id="campaignDatabaseRows">
                    <tr><td colspan="7" class="database-loading">Loading campaign statistics…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<div id="cleanupConfirmModal" class="database-confirm-modal" style="display:none">
    <h2>Confirm irreversible cleanup</h2>
    <div id="cleanupPreviewSummary" class="cleanup-preview-summary"></div>
    <div class="database-warning database-warning-danger">
        <i class="bi bi-exclamation-octagon"></i>
        <div><strong>No backup will be created.</strong><br>Deleted statistics cannot be restored. Late events and postbacks for deleted click ids will no longer be accepted.</div>
    </div>
    <div id="activeTrafficWarning" class="database-warning" hidden></div>
    <label class="delete-confirm-field">
        <span>Type <strong>DELETE</strong> to continue</span>
        <input class="form-control" id="deleteConfirmation" type="text" autocomplete="off" spellcheck="false">
    </label>
    <div class="database-modal-actions">
        <button class="btn btn-outline-secondary" type="button" id="cancelCleanupConfirm">Cancel</button>
        <button class="btn btn-danger" type="button" id="startCleanup" disabled>Delete statistics</button>
    </div>
</div>

<script id="databaseMaintenanceConfig" type="application/json"><?=
    json_encode([
        'csrf' => $csrf,
        'startdate' => $calendarStart,
        'enddate' => $calendarEnd,
        'timezone' => $timezone,
        'api' => 'databasemaintenance.php',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
?></script>
<script src="<?=get_admin_base_url()?>js/database.js?v=<?=filemtime(__DIR__ . '/js/database.js')?>"></script>
</body>
</html>
