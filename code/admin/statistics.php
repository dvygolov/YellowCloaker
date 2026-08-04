<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/tablecolumns.php';
require_once __DIR__ . '/campinit.php';
require_once __DIR__ . '/dates.php';

global $c, $campId, $db;
$timeRange = Dates::get_time_range($c->statistics->timezone);
$curTableIndex = $_GET['table']?? 0;

$ss = $c->statistics;
$mvtPlacements = [];
foreach ($c->black->flows as $flow) {
    foreach ($flow->steps as $stepIndex => $step) {
        if (!$step->isFolder()) {
            continue;
        }
        foreach ($step->folders as $folder) {
            if ($folder->mvt->tests === []) {
                continue;
            }
            $mvtPlacements[] = [
                'flow' => $flow->name,
                'step' => $stepIndex,
                'landing' => $folder->name,
                'tests' => $folder->mvt->tests,
            ];
        }
    }
}
function normalize_stats_mvt_grouping(array $requested, array $placements): array
{
    $requestedTests = is_array($requested['tests'] ?? null)
        ? $requested['tests']
        : ((int)($requested['test'] ?? 0) > 0 ? [$requested['test']] : []);
    $requested = [
        'flow' => (string)($requested['flow'] ?? ''),
        'step' => isset($requested['step']) ? (int)$requested['step'] : -1,
        'landing' => (string)($requested['landing'] ?? ''),
        'tests' => array_values(array_unique(array_filter(array_map(
            static fn($test): int => (int)$test,
            $requestedTests
        ), static fn(int $test): bool => $test > 0))),
    ];
    foreach ($placements as $placement) {
        if (
            $placement['flow'] === $requested['flow']
            && $placement['step'] === $requested['step']
            && $placement['landing'] === $requested['landing']
            && array_reduce(
                $requested['tests'],
                static fn(bool $valid, int $test): bool => $valid && isset($placement['tests'][$test - 1]),
                true
            )
        ) {
            return $requested;
        }
    }
    return [];
}

$allCampaigns = $db->get_campaigns_list();
usort($allCampaigns, function ($a, $b) {
    return strnatcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
});
if (count($ss->tables)>0){
    $tSettings = $ss->tables[$curTableIndex];
    $tFilters = isset($tSettings->filters) ? (array)$tSettings->filters : [];
    $tOrderby = isset($tSettings->orderby) ? $tSettings->orderby : [];
    $mvtGrouping = normalize_stats_mvt_grouping((array)$tSettings->mvt, $mvtPlacements);
    $dataset = $db->get_statistics(
        $tSettings->columns,
        $tSettings->groupby,
        $campId,
        $timeRange[0],
        $timeRange[1],
        $ss->timezone,
        $tFilters,
        $tOrderby,
        $mvtGrouping
    );
    $dJson = json_encode($dataset);
    $tName = $tSettings->name;
    $tDomId = 'statsTable_' . intval($curTableIndex);
    $tJsVar = 'statsTable' . intval($curTableIndex);
    $displayGroupBy = $tSettings->groupby;
    if ($mvtGrouping !== [] && !in_array('mvt', $displayGroupBy, true)) {
        $displayGroupBy[] = 'mvt';
    }
    $tColumns = Tabulator::get_stats_columns($tSettings->columns, $tName, $displayGroupBy);
}
?>
<!doctype html>
<html lang="en">
<?php include "head.php" ?>

<body>
    <?php include "header.php" ?>
    <div class="all-content-wrapper">
    <?php include __DIR__."/statstableeditor.html" ?>

    <script>
        let availableClmns = <?= json_encode(AvailableColumns::get_stats_columns_for_campaign($c)) ?>;
        let availableDimensions = <?= json_encode(AvailableColumns::get_columns_for_type('groupby')) ?>;
        let campaignConversionStatuses = <?= json_encode($c->conversions->statusNames(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        let availableMvtPlacements = <?= json_encode($mvtPlacements, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <div class="buttons-block" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <button id="addNewTable" title="Add new statisctics table" class="btn btn-primary"><i
                    class="bi bi-plus-circle-fill"></i>&nbsp;New</button>
            <?php if (count($ss->tables)>0){ ?>
            <!-- Table selector with improved styling for dark background -->
            <span style="margin-left: 20px; display: inline-block;">
                <label for="tableSelector" style="color: white; font-weight: bold;">Table:</label>
                <select id="tableSelector" class="form-control" style="width: 150px; display: inline-block; margin-left: 10px; background-color: #fff; color: #000; appearance: auto; -webkit-appearance: menulist; padding-right: 24px;">
                    <?php
                    for ($i=0; $i<count($c->statistics->tables); $i++) {
                        $t = $c->statistics->tables[$i];
                        echo "<option value='" . $i . "'" . ($i == $curTableIndex ? " selected" : "") . ">" . $t->name . "</option>";
                    }
                    ?>
                </select>
            </span>
            <?php } ?>
        </div>
        <?php if (count($ss->tables)>0){ ?>
        <div class="stats-toolbar-actions">
            <button id="columnsSelect<?=$tDomId?>" title="Edit table" class="btn btn-info" style="margin-left: 8px;"><i
                    class="bi bi-layout-three-columns"></i></button>
            <button id="share<?=$tDomId?>" title="Share table to other campaigns" class="btn btn-primary" style="margin-left: 8px;"><i
                    class="bi bi-share-fill"></i></button>
            <button id="download<?=$tDomId?>" title="Download table as XLSX" class="btn btn-success" style="margin-left: 8px;"><i
                    class="bi bi-download"></i></button>
            <button id="delete<?=$tDomId?>" title="Delete table" class="btn btn-danger" style="margin-left: 8px;"><i
                    class="bi bi-trash-fill"></i></button>
        </div>
        <?php } ?>
    </div>
    <?php if (count($ss->tables)>0){ ?>
    <style>
        .stats-toolbar-actions { display:flex; align-items:center; }
        @media(max-width:760px) {
            .buttons-block { align-items:flex-start !important; gap:10px; }
            .stats-toolbar-actions { flex-wrap:wrap; justify-content:flex-end; }
        }
        #shareStatsModal { max-width: 760px !important; width: min(760px, 94vw) !important; }
        #shareStatsModal .modal-body { padding: 14px 16px; }
        #shareStatsModal .modal-footer { padding: 12px 16px; }
        #shareStatsTitle { margin: 0 0 10px; font-size: 24px; font-weight: 600; line-height: 1.2; color: #e2e8f0; }
        #shareStatsSearch { margin-bottom: 10px; }
        #shareStatsList {
            max-height: 380px;
            overflow-y: auto;
            border: 1px solid #2a3245;
            border-radius: 8px;
            background: #11192c;
            padding: 4px 8px;
        }
        #shareStatsList .share-camp-row {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 10px;
            padding: 10px 6px;
            margin: 0;
            border-bottom: 1px solid #25324a;
            color: #e2e8f0;
            font-size: 17px;
            cursor: pointer;
        }
        #shareStatsList .share-camp-row:last-child { border-bottom: none; }
        #shareStatsList .share-camp-row:hover { background: rgba(255,255,255,0.04); }
        #shareStatsList .share-camp-checkbox { width: 18px; height: 18px; flex: 0 0 18px; }
        #shareStatsList .share-camp-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    </style>
    <div id="shareStatsModal" class="ywbmodal" style="display:none;">
        <div class="modal-content">
            <div class="modal-body">
                <h5 id="shareStatsTitle">Share Table "<?=htmlspecialchars($tName)?>"</h5>
                <input type="text" id="shareStatsSearch" class="form-control" placeholder="Quick filter campaigns...">
                <div id="shareStatsList">
                    <?php foreach ($allCampaigns as $campRow) {
                        $cid = (int)$campRow['id'];
                        $cname = (string)$campRow['name'];
                        if ($cid === (int)$campId) continue;
                        $lower = strtolower($cname . ' #' . $cid);
                    ?>
                        <label class="share-camp-row" data-search="<?=htmlspecialchars($lower)?>">
                            <input type="checkbox" class="share-camp-checkbox" value="<?=$cid?>">
                            <span class="share-camp-name"><?=htmlspecialchars($cname)?> (id:<?=$cid?>)</span>
                        </label>
                    <?php } ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="shareStatsCancel">Cancel</button>
                <button type="button" class="btn btn-primary" id="shareStatsConfirm">OK</button>
            </div>
        </div>
    </div>
    <?php } ?>
    <?php if (count($ss->tables)>0){ ?>
    <div class="tabulator-scroll-wrapper">
    <div id="<?=$tDomId?>" style="clear: both;"></div>
    </div>
    <script>
        let <?=$tJsVar?>Data = <?=$dJson?>;
        let <?=$tJsVar?>Columns = <?=$tColumns?>;
        let <?=$tJsVar?>Table = new Tabulator('#<?=$tDomId?>', {
            layout: "fitData",
            columns: <?=$tJsVar?>Columns,
            nestedFieldSeparator: false,
            columnCalcs: "both",
            pagination: "local",
            paginationSize: 500,
            paginationSizeSelector: [25, 50, 100, 200, 500, 1000, 2000, 5000],
            paginationCounter: "rows",
            dataTree: true,
            dataTreeBranchElement:false,
            dataTreeStartExpanded:false,
            dataTreeChildIndent: 15,
            data: <?=$tJsVar?>Data,
            columnDefaults:{
                tooltip:true,
            },
            dependencies:{
                XLSX:XLSX,
            }
        });

        <?=$tJsVar?>Table.on("columnResized", async function (column) {
            let updatedColumn = { field: column.getField(), width: column.getWidth() };
            await fetch("clmnseditor.php?action=width&name=<?=$tName?>&table=stats&campid=<?=$campId?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(updatedColumn),
            });
        });

        $('#tableSelector').change(function() {
            let newUrl = new URL(window.location.href);
            newUrl.searchParams.set('table', $(this).val());
            window.location.href = newUrl.href;
        });
        
        document.getElementById("download<?=$tDomId?>").onclick = () => {
            const treeData = <?=$tJsVar?>Data;
            const table = <?=$tJsVar?>Table;

            // Get visible columns from Tabulator
            const cols = table.getColumns().filter(c => c.isVisible() && c.getField());
            const fields = cols.map(c => c.getField());
            const titles = cols.map(c => c.getDefinition().title || c.getField());

            // Flatten tree into rows with depth info
            const rowDepths = [];
            const flatRows = [];
            function walkTree(nodes, depth) {
                for (const node of nodes) {
                    flatRows.push(node);
                    rowDepths.push(depth);
                    if (node._children && node._children.length) {
                        walkTree(node._children, depth + 1);
                    }
                }
            }
            walkTree(treeData, 0);

            // TOTAL row from Tabulator's columnCalcs (correct formulas for %, ratios, etc.)
            const calcResults = table.getCalcResults();
            const totalData = calcResults.bottom || {};
            totalData[fields[0]] = 'TOTAL';

            // Build AOA
            const aoa = [titles];
            flatRows.forEach(node => {
                aoa.push(fields.map(f => { const v = node[f]; return v !== undefined && v !== null ? v : ''; }));
            });
            aoa.push(fields.map(f => {
                const v = totalData[f];
                if (v === undefined || v === null) return '';
                if (typeof v === 'string' && v !== 'TOTAL') { const n = parseFloat(v); if (!isNaN(n)) return n; }
                return v;
            }));

            // Build worksheet + workbook
            const ws = XLSX.utils.aoa_to_sheet(aoa);
            // Set hidden rows (SheetJS community writes hidden but not outlineLevel)
            ws['!rows'] = [{}]; // header
            rowDepths.forEach(d => ws['!rows'].push(d > 0 ? { hidden: true } : {}));
            ws['!rows'].push({}); // TOTAL

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');

            // Generate xlsx as zip, patch sheet XML to add outlineLevel + outlinePr
            const maxDepth = Math.max(0, ...rowDepths);
            if (maxDepth > 0) {
                const zipData = XLSX.write(wb, { bookType: 'xlsx', type: 'array', compression: true });
                const zip = new JSZip();
                zip.loadAsync(zipData).then(z => {
                    return z.file('xl/worksheets/sheet1.xml').async('string');
                }).then(xml => {
                    // Add outlineLevel to <row> elements (row numbers are 1-indexed, row 1 = header)
                    let rowIdx = 0;
                    xml = xml.replace(/<row /g, (match) => {
                        const dataIdx = rowIdx - 1; // -1 for header row
                        rowIdx++;
                        if (dataIdx >= 0 && dataIdx < rowDepths.length && rowDepths[dataIdx] > 0) {
                            return match + 'outlineLevel="' + rowDepths[dataIdx] + '" ';
                        }
                        return match;
                    });
                    // Add <sheetPr><outlinePr summaryBelow="0"/></sheetPr> after <worksheet> tag
                    xml = xml.replace(/<sheetPr[^>]*\/>/, '<sheetPr><outlinePr summaryBelow="0"/></sheetPr>');
                    xml = xml.replace(/<sheetPr>/, '<sheetPr><outlinePr summaryBelow="0"/>');
                    if (!xml.includes('outlinePr')) {
                        xml = xml.replace(/<worksheet[^>]*>/, '$&<sheetPr><outlinePr summaryBelow="0"/></sheetPr>');
                    }
                    return zip.file('xl/worksheets/sheet1.xml', xml).generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                }).then(blob => {
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `<?=$tName?>${getDateSuffix()}.xlsx`;
                    a.click();
                    URL.revokeObjectURL(url);
                });
            } else {
                XLSX.writeFile(wb, `<?=$tName?>${getDateSuffix()}.xlsx`);
            }
        };
        document.getElementById("columnsSelect<?=$tDomId?>").onclick = async () => {
            let selectedClmns = <?= json_encode($tSettings->columns) ?>;
            let selectedDimensions = <?= json_encode($tSettings->groupby) ?>;
            
            let existingFilters = <?= json_encode(isset($tSettings->filters) ? $tSettings->filters : new stdClass()) ?>;
            let existingOrderby = <?= json_encode(isset($tSettings->orderby) ? $tSettings->orderby : []) ?>;
            let existingMvt = <?= json_encode(isset($tSettings->mvt) ? $tSettings->mvt : new stdClass()) ?>;
            initializeStatsTableEditor(
                availableClmns,
                selectedClmns,
                availableDimensions,
                selectedDimensions,
                "<?=$tName?>",
                `clmnseditor.php?action=savestats&name=<?=$tName?>&campid=<?=$campId?>`,
                existingFilters,
                existingOrderby,
                campaignConversionStatuses,
                availableMvtPlacements,
                existingMvt
            );

            $('#statsTableModal').modal({
                modalClass: 'ywbmodal',
                fadeDuration: 250,
                fadeDelay: 0.80,
                showClose: false
            });
        };
        $('#delete<?=$tDomId?>').click((e) => {
            deleteStatsTable('<?=$tName?>', 'clmnseditor.php?action=delstats&name=<?=$tName?>&campid=<?=$campId?>');
        });

        document.getElementById('share<?=$tDomId?>').onclick = () => {
            const search = document.getElementById('shareStatsSearch');
            if (search) search.value = '';
            document.querySelectorAll('.share-camp-row').forEach(row => {
                row.style.display = '';
            });
            $('#shareStatsModal').modal({
                modalClass: 'ywbmodal',
                fadeDuration: 200,
                fadeDelay: 0.8,
                showClose: false,
            });
        };

        document.getElementById('shareStatsCancel').onclick = () => {
            jQuery.modal.close();
        };

        document.getElementById('shareStatsSearch').addEventListener('input', (e) => {
            const q = (e.target.value || '').trim().toLowerCase();
            document.querySelectorAll('.share-camp-row').forEach(row => {
                const text = (row.dataset.search || '').toLowerCase();
                row.style.display = q === '' || text.includes(q) ? '' : 'none';
            });
        });

        document.getElementById('shareStatsConfirm').onclick = async () => {
            const targetCampIds = [...document.querySelectorAll('.share-camp-checkbox:checked')].map(cb => parseInt(cb.value, 10)).filter(Boolean);
            if (targetCampIds.length === 0) {
                alert('Select at least one campaign');
                return;
            }

            try {
                const res = await fetch('clmnseditor.php?action=sharestats&name=<?=$tName?>&campid=<?=$campId?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: '<?=$tName?>', targetCampIds }),
                });
                const data = await res.json();
                if (data.error) {
                    throw new Error(data.result || 'Share failed');
                }
                alert(data.result || 'Table shared');
                jQuery.modal.close();
            } catch (err) {
                alert('Error sharing table: ' + err.message);
            }
        };
    </script>
    <?php } ?>
    <script>
        $('#addNewTable').click(() => {
            initializeStatsTableEditor(
                availableClmns,
                [], // no selected columns
                availableDimensions,
                [], // no selected group by
                'New', // no table name
                'clmnseditor.php?action=savestats&campid=<?=$campId?>',
                {},
                [],
                campaignConversionStatuses,
                availableMvtPlacements,
                {}
            );
            $('#statsTableModal').modal({
                modalClass: 'ywbmodal',
                fadeDuration: 250,
                fadeDelay: 0.80,
                showClose: false
            });
        });
    </script>
    <br/>
    <br/>
    </div>
</body>

</html>
