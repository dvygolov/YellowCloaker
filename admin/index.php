<?php
require_once __DIR__ . '/../paths.php';
//fix for Apache Multiviews and/or PHP Development Server
if ($_SERVER['SCRIPT_NAME'] !== $_SERVER['PHP_SELF']) {
    http_response_code(404);
    exit("Not Found");
}
//we always need a slash at the end of the url, otherwise links will not work properly
$url = $_SERVER['REQUEST_URI'];
$urlPath = parse_url($url, PHP_URL_PATH);
$urlPath = is_string($urlPath) ? $urlPath : $url;
if (str_ends_with($urlPath, '/' . get_admin_path_segment())){
    header("Location: " . $url . "/");
    exit();
}

require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/clmns.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/dates.php';

$gs = $db->get_common_settings();
$savedFilters = $gs['statistics']['campaignsFilters'] ?? [];
$hasActiveFilters = !empty($savedFilters) && !empty($savedFilters['rules']);
$timeRange = Dates::get_time_range($gs['statistics']['timezone']);
$dataset = $db->get_campaigns(
    $timeRange[0],
    $timeRange[1],
    array_column($gs['statistics']['table'], 'field'),
    $savedFilters
);
?>
<!doctype html>
<html lang="en">
<?php include __DIR__ . "/head.php" ?>
<body class="dashboard-page">
    <?php include __DIR__ . "/header.php" ?>
    <div class="all-content-wrapper dashboard-content" id="dashboardContent">
        <div class="buttons-block">
            <button id="newCampaign" title="Create new campaign" class="btn btn-primary"><i
                    class="bi bi-plus-circle-fill"></i> New</button>
            <div class="system-status-inline" id="systemStatus" role="status" aria-live="polite">
                <span class="system-status-item" id="statusFree" title="Free space on the disk containing YellowTDS">
                    <i class="bi bi-device-hdd" aria-hidden="true"></i>
                    <span class="system-status-label">Free:</span>
                    <span class="system-status-value" id="statusFreeValue">…</span>
                </span>
                <span class="system-status-separator" aria-hidden="true"></span>
                <span class="system-status-item" id="statusDatabase" title="SQLite database including WAL and shared-memory files">
                    <i class="bi bi-database" aria-hidden="true"></i>
                    <span class="system-status-label">DB:</span>
                    <span class="system-status-value" id="statusDatabaseValue">…</span>
                </span>
                <span class="system-status-separator" aria-hidden="true"></span>
                <span class="system-status-item" id="statusCache" title="All files in the configured cache directory, including landing and white pages">
                    <i class="bi bi-folder2-open" aria-hidden="true"></i>
                    <span class="system-status-label">Cache:</span>
                    <span class="system-status-value" id="statusCacheValue">…</span>
                </span>
                <span class="system-status-separator" aria-hidden="true"></span>
                <span class="system-status-item" id="statusLogs" title="All YellowTDS log files">
                    <i class="bi bi-journal-text" aria-hidden="true"></i>
                    <span class="system-status-label">Logs:</span>
                    <span class="system-status-value" id="statusLogsValue">…</span>
                </span>
            </div>
            <div class="buttons-right">
                <button id="resetFilters" title="Reset all filters" class="btn btn-outline-danger" style="<?= $hasActiveFilters ? '' : 'display:none;' ?>"><i
                        class="bi bi-funnel"></i> Reset Filters</button>
                <button id="columnsSelect" title="Select and order columns" class="btn btn-info"><i
                        class="bi bi-layout-three-columns"></i></button>
                <button id="trafficBack" title="Trafficback url" class="btn btn-info"><i
                        class="bi bi-exclude"></i></button>
                <button id="trafficBackStats" title="Show trafficback statistics" class="btn btn-info"><i
                        class="bi bi-graph-up"></i></button>
                <button id="downloadCsv" title="Download table as XLSX" class="btn btn-success"><i
                        class="bi bi-download"></i></button>
            </div>
        </div>
        <div class="campaign-table-shell">
            <div id="campaigns"></div>
        </div>
    </div>
    <style>
        .buttons-block {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .buttons-block > #newCampaign {
            flex: 0 0 auto;
        }
        .buttons-right {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }
        .buttons-right .btn {
            margin: 0;
        }
        .camp-name-cell {
            display: flex;
            align-items: center;
            width: 100%;
            gap: 4px;
        }
        .camp-name-link {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .camp-menu-btn {
            flex: 0 0 auto;
            background: none;
            border: none;
            color: #8a9bb5;
            cursor: pointer;
            padding: 0 2px;
            font-size: 14px;
            line-height: 1;
            transition: color 0.15s;
        }
        .camp-menu-btn:hover {
            color: #60a5fa;
        }
        .camp-menu-dropdown {
            display: none;
            position: fixed;
            z-index: 99999;
            min-width: 150px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            padding: 4px 0;
            white-space: nowrap;
        }
        .camp-menu-dropdown.show {
            display: block;
        }
        .camp-menu-item {
            padding: 6px 12px;
            cursor: pointer;
            color: #cbd5e1;
            font-size: 13px;
            transition: background 0.1s;
        }
        .camp-menu-item:hover {
            background: #334155;
            color: #f1f5f9;
        }
        .camp-menu-item i {
            margin-right: 6px;
            width: 16px;
            text-align: center;
        }
        .camp-menu-danger {
            color: #f87171;
        }
        .camp-menu-danger:hover {
            background: #7f1d1d;
            color: #fca5a5;
        }
        .camp-menu-divider {
            height: 1px;
            background: #334155;
            margin: 4px 0;
        }
        .dashboard-page {
            overflow: hidden;
        }
        .dashboard-content {
            display: flex;
            flex-direction: column;
            min-height: 220px;
        }
        .campaign-table-shell {
            flex: 1 1 auto;
            min-height: 0;
        }
        #campaigns {
            height: 100%;
        }
        .system-status-inline {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: center;
            gap: 12px;
            min-width: 0;
            margin: 0 16px;
            padding: 4px 0;
            overflow-x: auto;
            color: #94a3b8;
            font-size: 13px;
            line-height: 1;
            white-space: nowrap;
            scrollbar-width: none;
        }
        .system-status-inline::-webkit-scrollbar {
            display: none;
        }
        @media (max-width: 1000px) {
            .system-status-inline {
                justify-content: flex-start;
                gap: 8px;
                margin: 0 10px;
            }
        }
        .system-status-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .system-status-item i {
            color: #60a5fa;
            font-size: 15px;
        }
        .system-status-label {
            color: #cbd5e1;
            font-weight: 500;
        }
        .system-status-value {
            color: #f1f5f9;
            font-variant-numeric: tabular-nums;
        }
        .system-status-separator {
            width: 1px;
            height: 16px;
            background: #475569;
        }
        .system-status-item.status-warning i,
        .system-status-item.status-warning .system-status-value {
            color: #f59e0b;
        }
        .system-status-item.status-critical i,
        .system-status-item.status-critical .system-status-value,
        .system-status-item.status-unavailable i,
        .system-status-item.status-unavailable .system-status-value {
            color: #f87171;
        }
    </style>
    <script>
        function resizeDashboard() {
            const dashboard = document.getElementById('dashboardContent');
            const top = dashboard.getBoundingClientRect().top;
            dashboard.style.height = `${Math.max(220, Math.floor(window.innerHeight - top - 5))}px`;
        }

        resizeDashboard();

        let tableData = <?= json_encode($dataset) ?>;
        let tableColumns = <?= Tabulator::get_campaigns_columns($gs['statistics']['table']) ?>;
        let table = new Tabulator('#campaigns', {
            layout: "fitColumns",
            columns: tableColumns,
            pagination: false,
            height: "100%",
            data: tableData,
            columnDefaults: {
                tooltip: true,
            },
            columnCalcs: "both",
            dependencies:{
                XLSX:XLSX,
            }
        });

        let dashboardResizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(dashboardResizeTimer);
            dashboardResizeTimer = setTimeout(function () {
                resizeDashboard();
                table.redraw(true);
            }, 80);
        });

        function formatStatusBytes(bytes) {
            if (!Number.isFinite(bytes) || bytes < 0) return '—';
            if (bytes === 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
            const value = bytes / Math.pow(1024, unitIndex);
            const maximumFractionDigits = unitIndex === 0 ? 0 : 1;
            return `${new Intl.NumberFormat(undefined, { maximumFractionDigits }).format(value)} ${units[unitIndex]}`;
        }

        function setStatusValue(name, metric) {
            const item = document.getElementById(`status${name}`);
            const value = document.getElementById(`status${name}Value`);
            item.classList.toggle('status-unavailable', !metric || metric.available === false);
            value.textContent = metric && metric.available !== false ? formatStatusBytes(metric.bytes) : '—';
        }

        async function loadSystemStatus() {
            const freeItem = document.getElementById('statusFree');
            const freeValue = document.getElementById('statusFreeValue');
            try {
                const response = await fetch('systemstatus.php', { headers: { 'Accept': 'application/json' } });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const status = await response.json();
                if (status.error) throw new Error(status.error);

                const disk = status.disk || {};
                freeItem.classList.remove('status-warning', 'status-critical', 'status-unavailable');
                freeItem.classList.add(`status-${disk.level || 'unavailable'}`);
                freeValue.textContent = Number.isFinite(disk.freeBytes) && Number.isFinite(disk.freePercent)
                    ? `${formatStatusBytes(disk.freeBytes)} (${new Intl.NumberFormat(undefined, { maximumFractionDigits: 1 }).format(disk.freePercent)}%)`
                    : '—';

                setStatusValue('Database', status.database);
                setStatusValue('Cache', status.cache);
                setStatusValue('Logs', status.logs);
            } catch (error) {
                document.querySelectorAll('.system-status-item').forEach(item => item.classList.add('status-unavailable'));
                document.querySelectorAll('.system-status-value').forEach(value => value.textContent = '—');
                document.getElementById('systemStatus').title = `System status unavailable: ${error.message}`;
            }
        }

        table.on("columnResized", async function (column) {
            let updatedColumn = { field: column.getField(), width: column.getWidth() };
            await fetch("clmnseditor.php?action=width&table=campaigns", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(updatedColumn),
            });
        });
    </script>
    <?php include __DIR__ . "/clmnspopup.html" ?>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadSystemStatus();
            document.getElementById("newCampaign").onclick = async () => {
                let campName = prompt("Enter new campaign name:");
                if (campName)
                    await campEditor('add', null, campName);
            };

            document.getElementById("trafficBack").onclick = async () => {
                let tbUrl = prompt("Enter trafficback url:", "<?= $gs['trafficBackUrl'] ?>");
                if (tbUrl === null) return;
                let res = await fetch("commonseditor.php?action=trafficback", {
                    method: "POST",
                    body: tbUrl,
                });
                let js = await res.json();
                if (!js.error) {
                    alert('TrafficBack url saved!');
                    window.location.reload();
                }
                else
                    alert('Error saving trafficback url: ' + js.result);
            };

            document.getElementById("trafficBackStats").onclick = () => {
                let startDateEndDateParams = getStartDateEndDateParams();
                window.location.href = `clicks.php?view=trafficback${startDateEndDateParams}`;
            };

            document.getElementById("downloadCsv").onclick = () => {
                table.download("xlsx", `Campaigns${getDateSuffix()}.xlsx`);
            };
            document.getElementById("resetFilters").onclick = async () => {
                try {
                    await fetch("clmnseditor.php?action=savecolumns&table=campaigns", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ columns: <?= json_encode(array_map(fn($c) => is_array($c) ? $c['field'] : $c, $gs['statistics']['table'])) ?>, filters: {} })
                    });
                    window.location.reload();
                } catch(e) { alert('Error resetting filters: ' + e.message); }
            };
            document.getElementById("columnsSelect").onclick = async () => {
                let availableClmns = <?= json_encode(AvailableColumns::get_columns_for_type('stats')) ?>;
                let selectedClmns = <?= json_encode($gs['statistics']['table']) ?>;
                let existingFilters = <?= json_encode($savedFilters) ?>;
                addColumnsToList(selectedClmns, availableClmns, existingFilters, 'campaigns', {showParamButton: false});
                setSaveButtonHandler("clmnseditor.php?action=savecolumns&table=campaigns");
                $('#columnModal').modal({
                    modalClass: 'ywbmodal',
                    fadeDuration: 250,
                    fadeDelay: 0.80,
                    showClose: false
                });
            }
        });
    </script>
</body>

</html>
