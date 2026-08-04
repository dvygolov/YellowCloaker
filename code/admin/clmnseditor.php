<?php
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../logging.php';

$passOk = check_password(false);
if (!$passOk)
    return send_clmnseditor_result("Error: password check not passed!",true);

$action = $_REQUEST['action'];
$table = $_REQUEST['table']??''; //table type: various clicks or campaigns or stats
$tName = $_REQUEST['name']??''; //table name for stats
$campId = $_REQUEST['campid']??null;
$postData = file_get_contents('php://input');

add_log('trace', "ColumnsEditor action: $action, table: $table, name: $tName, campId: $campId");

switch ($action) {
    case 'width':
        $currentColumns = $table==='stats' ? 
            get_current_stats_columns($tName, $campId) : 
            get_current_columns_for_type($table, $campId);
        $uc = json_decode($postData, true);
        update_width($currentColumns, $uc);
        $saved = $table==='stats' ? 
            save_stats_columns($currentColumns, $tName, $campId) :
            save_columns_for_type($currentColumns, $table, $campId);
        return $saved? 
            send_clmnseditor_result("OK"):
            send_clmnseditor_result("Error saving settings!",true);
    case 'savecolumns':
        $currentColumns = get_current_columns_for_type($table, $campId);
        $data = json_decode($postData, true);
        if (empty($data)) {
            return send_clmnseditor_result("Error: missing columns data", true);
        }

        // Support both old format (plain array) and new format ({columns, filters})
        $columnNames = isset($data['columns']) ? $data['columns'] : $data;
        $filters = $data['filters'] ?? [];

        $newColumns = get_new_columns($currentColumns, $columnNames);
        $saved = save_columns_for_type($newColumns, $table, $campId);
        if ($saved) {
            save_filters_for_type($filters, $table, $campId);
        }
        return $saved? 
            send_clmnseditor_result("OK"):
            send_clmnseditor_result("Error saving settings!",true);
    
    case 'newstats':
    case 'savestats':
        $data = json_decode($postData, true);

        if (!isset($data['name']) || !isset($data['columns']) || !isset($data['groupby'])) {
            return send_clmnseditor_result("Error: invalid table configuration", true);
        }
        $normalizedColumns = [];
        $validationError = validate_stats_columns_config((array)$data['columns'], $normalizedColumns);
        if ($validationError !== null) {
            return send_clmnseditor_result($validationError, true);
        }
        $data['columns'] = $normalizedColumns;
        $data['mvt'] = normalize_stats_mvt_config(
            is_array($data['mvt'] ?? null) ? $data['mvt'] : [],
            $db->get_campaign_settings((int)$campId)
        );

        $saved = save_stats_table($campId, $tName,$data);

        return $saved?
            send_clmnseditor_result("Stats table saved successfully"):
            send_clmnseditor_result("Error saving table",true);
    case 'delstats':
        $data = json_decode($postData, true);

        if (!isset($data['name'])) {
            return send_clmnseditor_result("Error: missing table name", true);
        }

        $deleted = delete_stats_table($campId, $data['name']);
        return $deleted?
            send_clmnseditor_result("Stats table deleted successfully"):
            send_clmnseditor_result("Error deleting table",true);
    case 'sharestats':
        $data = json_decode($postData, true);
        if (!isset($data['name']) || !isset($data['targetCampIds']) || !is_array($data['targetCampIds'])) {
            return send_clmnseditor_result("Error: invalid share payload", true);
        }

        $copied = share_stats_table((int)$campId, $data['name'], $data['targetCampIds']);
        if ($copied === false) {
            return send_clmnseditor_result("Error sharing table", true);
        }
        return send_clmnseditor_result("Table shared to {$copied} campaign(s)");
    default:
        return send_clmnseditor_result("Error: unknown action", true);
}

function send_clmnseditor_result($msg,$error=false): void
{
    $res = ["result" => $msg];
    if ($error){
        $res['error']=true;
    }
    header('Content-type: application/json');
    http_response_code(200);
    $json = json_encode($res);
    echo $json;
}


/**
 * Updates the width of the specified column in the specified table array
 * @param array $table
 * @param array $c
 * @return void
 */
function update_width(array &$table, array $c) {
    foreach ($table as &$tcolumn) {
        if ($tcolumn['field'] !== $c['field'])
            continue;
        $tcolumn['width'] = $c['width'];
        return;
    }
    $table[] = ['field' => $c['field'], 'width' => $c['width']];
}


function get_new_columns($existingColumns, $newColumnNames): array
{
    $newColumns = [];

    // Process each column from the new data
    foreach ($newColumnNames as $cName) {
        // Support both string and array column formats
        if (is_array($cName)) {
            $fieldName = $cName['field'] ?? '';
            $newColData = $cName;
        } else {
            $fieldName = $cName;
            $newColData = null;
        }

        $found = false;
        foreach ($existingColumns as $existingColumn) {
            $existingField = is_array($existingColumn) ? ($existingColumn['field'] ?? '') : $existingColumn;
            if ($existingField === $fieldName) {
                // Preserve existing width if set by user, merge with new column data
                if ($newColData !== null) {
                    $merged = $newColData;
                    if (isset($existingColumn['width']) && $existingColumn['width'] > 0) {
                        $merged['width'] = $existingColumn['width'];
                    }
                    $newColumns[] = $merged;
                } else {
                    $newColumns[] = $existingColumn;
                }
                $found = true;
                break;
            }
        }
        // If column not found, add it with default width
        if (!$found) {
            if ($newColData !== null) {
                $newColumns[] = $newColData;
            } elseif ($fieldName === 'group') {
                $newColumns[] = ['field' => $fieldName, 'width' => 100];
            } else {
                $newColumns[] = ['field' => $fieldName, 'width' => -1];
            }
        }
    }

    return $newColumns;
}

function validate_stats_columns_config(array $columns, array &$normalizedColumns): ?string
{
    foreach ($columns as $column) {
        if (!is_array($column)) {
            continue;
        }
        $valid = !empty($column['custom'])
            ? Db::normalize_custom_metric_column($column) !== null
            : (!empty($column['status_metric']) ? Db::normalize_status_metric_column($column) !== null : true);
        if (!$valid) {
            $title = trim((string)($column['title'] ?? $column['field'] ?? 'custom column'));
            return "Error: invalid stats column: $title";
        }
    }

    $normalizedColumns = Db::normalize_stats_columns_config($columns);
    return null;
}

function normalize_stats_mvt_config(array $mvt, array $campaignSettings): array
{
    if ($mvt === []) {
        return [];
    }

    $flowName = (string)($mvt['flow'] ?? '');
    $stepIndex = filter_var($mvt['step'] ?? null, FILTER_VALIDATE_INT);
    $landingName = (string)($mvt['landing'] ?? '');
    $requestedTests = is_array($mvt['tests'] ?? null)
        ? $mvt['tests']
        : ((int)($mvt['test'] ?? 0) > 0 ? [$mvt['test']] : []);
    if ($flowName === '' || $stepIndex === false || $stepIndex < 0 || $landingName === '') {
        return [];
    }

    foreach ((array)($campaignSettings['black']['flows'] ?? []) as $flow) {
        if ((string)($flow['name'] ?? '') !== $flowName) {
            continue;
        }
        $step = $flow['steps'][$stepIndex] ?? null;
        if (!is_array($step) || (string)($step['action'] ?? '') !== 'folder') {
            return [];
        }
        foreach ((array)($step['folders'] ?? []) as $folder) {
            if (!is_array($folder) || (string)($folder['name'] ?? '') !== $landingName) {
                continue;
            }
            $tests = (array)($folder['mvt']['tests'] ?? []);
            $testNumbers = array_values(array_unique(array_filter(array_map(
                static fn($test): int => (int)$test,
                $requestedTests
            ), static fn(int $test): bool => $test > 0)));
            if ($tests === [] || array_reduce(
                $testNumbers,
                static fn(bool $valid, int $test): bool => $valid && array_key_exists($test - 1, $tests),
                true
            ) === false) {
                return [];
            }
            return [
                'flow' => $flowName,
                'step' => $stepIndex,
                'landing' => $landingName,
                'tests' => $testNumbers,
            ];
        }
        return [];
    }

    return [];
}

function get_current_columns_for_type(string $table, ?int $campId = null): array{
    global $db;
    switch($table){
        case 'campaigns':
            $s = $db->get_common_settings();
            return $s['statistics']['table'];
        case 'trafficback':
            $s = $db->get_common_settings();
            return $s['statistics']['trafficBack'];
        case 'allowed':
        case 'single':
            $s = $db->get_campaign_settings($campId);
            return $s['statistics']['allowed'];
        case 'blocked':
            $s = $db->get_campaign_settings($campId);
            return $s['statistics']['blocked'];
        case 'leads':
            $s = $db->get_campaign_settings($campId);
            return $s['statistics']['leads'];
        default:
            $errMsg = "Table $table not found in campaign settings";
            add_error_log($errMsg);
            trigger_error($errMsg, E_USER_ERROR);
            exit;
    }
}

function get_current_stats_columns(string $name, int $campId, bool $groupBy = false):array{
    global $db;
    $s = $db->get_campaign_settings($campId);
    foreach ($s['statistics']['tables'] as $t) {
        if ($t['name'] === $name) {
            return $groupBy ? $t['groupby'] : $t['columns'];
        }
    }
    return [];
}

function save_columns_for_type(array $columns, string $table, ?int $campId = null):bool{
    global $db;
    switch($table){
        case 'campaigns':
            $s = $db->get_common_settings();
            $s['statistics']['table'] = $columns;
            return $db->set_common_settings($s);
        case 'trafficback':
            $s = $db->get_common_settings();
            $s['statistics']['trafficBack'] = $columns;
            return $db->set_common_settings($s);
        case 'allowed':
        case 'single':
            $s = $db->get_campaign_settings($campId);
            $s['statistics']['allowed'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        case 'blocked':
            $s = $db->get_campaign_settings($campId);
            $s['statistics']['blocked'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        case 'leads':
            $s = $db->get_campaign_settings($campId);
            $s['statistics']['leads'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        default:
            $errMsg = "Table $table not found in campaign settings";
            add_error_log($errMsg);
            trigger_error($errMsg, E_USER_ERROR);
            exit;
    }
}

function save_filters_for_type(array $filters, string $table, ?int $campId = null): bool {
    global $db;
    $filterKey = match($table) {
        'allowed', 'single' => 'allowedFilters',
        'blocked' => 'blockedFilters',
        'leads' => 'leadsFilters',
        'trafficback' => 'trafficBackFilters',
        'campaigns' => 'campaignsFilters',
        default => null,
    };
    if (!$filterKey) return false;

    if ($table === 'trafficback' || $table === 'campaigns') {
        $s = $db->get_common_settings();
        $s['statistics'][$filterKey] = $filters;
        return $db->set_common_settings($s);
    }

    $s = $db->get_campaign_settings($campId);
    $s['statistics'][$filterKey] = $filters;
    return $db->save_campaign_settings($campId, $s);
}

function save_stats_columns(array $columns, string $name, int $campId): bool
{  
    global $db;
    $s = $db->get_campaign_settings($campId);
    foreach ($s['statistics']['tables'] as &$t) {
        if ($t['name'] === $name) {
            $t['columns'] = $columns;
            return $db->save_campaign_settings($campId, $s);
        }
    }
    
    $errMsg = "Stats table $name not found in campaign $campId settings";
    add_error_log($errMsg);
    trigger_error($errMsg, E_USER_ERROR);
    exit;
}

function save_stats_table(int $campId, string $tableName,array $tableConfig): bool
{
    global $db;
    $s = $db->get_campaign_settings($campId);
    if (empty($s)) {
        add_error_log("Save stats table: campaign $campId not found");
        return false;
    }

    // Find if table with this name already exists
    $existingTableIndex = -1;
    foreach ($s['statistics']['tables'] as $index => &$t) {
        if ($t['name'] === $tableName) {
            $existingTableIndex = $index;
            break;
        }
    }

    $existingColumns = $existingTableIndex >= 0 ? ($s['statistics']['tables'][$existingTableIndex]['columns'] ?? []) : [];
    $allColumnNames = array_merge([['field' => 'group', 'width' => 100]], $tableConfig['columns']);
    // Create new table object
    $table = [
        'name' => $tableConfig['name'], 
        'columns' => get_new_columns($existingColumns, $allColumnNames),
        'groupby' => $tableConfig['groupby'],
        'filters' => $tableConfig['filters'] ?? [],
        'orderby' => $tableConfig['orderby'] ?? [],
        'mvt' => $tableConfig['mvt'] ?? []
    ];

    // Update or add the table
    if ($existingTableIndex >= 0) {
        $s['statistics']['tables'][$existingTableIndex] = $table;
    } else {
        $s['statistics']['tables'][] = $table;
    }

    // Save settings
    return $db->save_campaign_settings($campId, $s);

}

function delete_stats_table($campId, $tableName): bool {
    global $db;
    $s = $db->get_campaign_settings($campId);
    if (empty($s)) {
        add_error_log("Delete stats table: campaign $campId not found");
        return false;
    }

    // Find and remove the table
    $found = false;
    foreach ($s['statistics']['tables'] as $index => $table) {
        if ($table['name'] === $tableName) {
            array_splice($s['statistics']['tables'], $index, 1);
            $found = true;
            break;
        }
    }

    if (!$found) {
        add_error_log("Delete stats table: table $tableName not found in campaign $campId");
        return false;
    }

    // Save settings
    return $db->save_campaign_settings($campId, $s);
}

function share_stats_table(int $sourceCampId, string $tableName, array $targetCampIds): bool|int
{
    global $db;

    $sourceSettings = $db->get_campaign_settings($sourceCampId);
    if (empty($sourceSettings['statistics']['tables']) || !is_array($sourceSettings['statistics']['tables'])) {
        return false;
    }

    $sourceTable = null;
    foreach ($sourceSettings['statistics']['tables'] as $table) {
        if (($table['name'] ?? '') === $tableName) {
            $sourceTable = $table;
            break;
        }
    }
    if ($sourceTable === null) {
        return false;
    }

    $copied = 0;
    foreach ($targetCampIds as $targetCampIdRaw) {
        $targetCampId = (int)$targetCampIdRaw;
        if ($targetCampId <= 0 || $targetCampId === $sourceCampId) {
            continue;
        }

        $targetSettings = $db->get_campaign_settings($targetCampId);
        if (empty($targetSettings)) {
            continue;
        }
        if (!isset($targetSettings['statistics']) || !is_array($targetSettings['statistics'])) {
            $targetSettings['statistics'] = [];
        }
        if (!isset($targetSettings['statistics']['tables']) || !is_array($targetSettings['statistics']['tables'])) {
            $targetSettings['statistics']['tables'] = [];
        }

        $replaced = false;
        foreach ($targetSettings['statistics']['tables'] as $i => $table) {
            if (($table['name'] ?? '') === $tableName) {
                $targetSettings['statistics']['tables'][$i] = $sourceTable;
                $replaced = true;
                break;
            }
        }
        if (!$replaced) {
            $targetSettings['statistics']['tables'][] = $sourceTable;
        }

        if ($db->save_campaign_settings($targetCampId, $targetSettings)) {
            $copied++;
        }
    }

    return $copied;
}
