<?php

require_once __DIR__ . "/../cookies.php";
require_once __DIR__ . "/../logging.php";
require_once __DIR__ . "/../settings.php";
require_once __DIR__ . "/../paths.php";

class Db
{
    private const DERIVED_STATS_DEPENDENCIES = [
        'uniques_ratio' => ['clicks', 'uniques'],
        'cra' => ['clicks', 'conversion'],
        'crs' => ['clicks', 'purchase'],
        'appt' => ['purchase', 'conversion', 'trash'],
        'app' => ['purchase', 'conversion'],
        'roi' => ['revenue', 'costs'],
        'epc' => ['revenue', 'clicks'],
        'uepc' => ['revenue', 'uniques'],
        'cpc' => ['costs', 'clicks'],
        'ucpc' => ['costs', 'uniques'],
        'ec' => ['revenue', 'conversion'],
        'cpa' => ['costs', 'conversion'],
        'profit' => ['revenue', 'costs'],
    ];

    private $dbPath;
    private ?SQLite3 $readDb = null;
    private ?SQLite3 $writeDb = null;

    public function __construct()
    {
        global $cloSettings;
        $this->dbPath = __DIR__ . '/' . $cloSettings['dbConnection'];
        if (!file_exists($this->dbPath)) {
            $created = $this->create_new_db();
            if (!$created)
                die("Couldn't create the SQLite database! Read logs for additional info.");
        }
        $this->ensure_schema_migrations();
    }

    private function ensure_schema_migrations(): void
    {
        $db = new SQLite3($this->dbPath, SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);

        $columns = [];
        $result = $db->query("PRAGMA table_info(clicks)");
        while ($row = $result?->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'] ?? '';
        }

        if (!in_array('events', $columns, true)) {
            $db->exec("ALTER TABLE clicks ADD COLUMN events TEXT DEFAULT '{}'");
        }

        $db->exec(
            "CREATE TABLE IF NOT EXISTS click_event_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                clickid TEXT NOT NULL,
                time INTEGER NOT NULL,
                step_index INTEGER NOT NULL,
                event_name TEXT NOT NULL,
                event_value NUMERIC NOT NULL,
                FOREIGN KEY (clickid) REFERENCES clicks (clickid) ON DELETE CASCADE
            )"
        );
        $db->exec('CREATE INDEX IF NOT EXISTS idx_event_clickid_time ON click_event_log (clickid,time)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_event_name_time ON click_event_log (event_name,time)');
        $db->close();
    }

    private static function decode_click_row(array &$click): void
    {
        if (array_key_exists('path', $click) && is_string($click['path']) && $click['path'] !== '') {
            $decodedPath = json_decode($click['path'], true);
            $click['path'] = is_array($decodedPath) ? $decodedPath : [];
        }

        foreach (['params', 'events'] as $jsonField) {
            if (!array_key_exists($jsonField, $click)) {
                continue;
            }
            if (empty($click[$jsonField])) {
                $click[$jsonField] = [];
                continue;
            }
            $decoded = json_decode($click[$jsonField], true);
            $click[$jsonField] = is_array($decoded) ? $decoded : [];
        }
    }

    public static function normalize_stats_columns_config(array $columns): array
    {
        $normalized = [];
        foreach ($columns as $column) {
            if (is_string($column)) {
                $normalized[] = ['field' => $column, 'width' => -1];
                continue;
            }
            if (!is_array($column)) {
                continue;
            }

            $rawField = $column['field'] ?? '';
            if (!is_string($rawField)) {
                continue;
            }
            $field = trim($rawField);
            if ($field === '') {
                continue;
            }

            $normalizedColumn = ['field' => $field, 'width' => (int)($column['width'] ?? -1)];
            if (!empty($column['custom'])) {
                $custom = self::normalize_custom_metric_column($column);
                if ($custom === null) {
                    continue;
                }
                $normalizedColumn = array_merge($normalizedColumn, $custom, ['custom' => true]);
            } elseif (isset($column['title']) && is_string($column['title']) && trim($column['title']) !== '') {
                $normalizedColumn['title'] = trim($column['title']);
            }

            $normalized[] = $normalizedColumn;
        }
        return $normalized;
    }

    public static function normalize_custom_metric_column(array $column): ?array
    {
        $field = trim((string)($column['field'] ?? ''));
        $title = trim((string)($column['title'] ?? ''));
        $formula = strtolower(preg_replace('/\s+/', '', (string)($column['formula'] ?? '')));
        $format = trim((string)($column['format'] ?? 'number'));
        $decimals = max(0, min(8, (int)($column['decimals'] ?? 2)));

        if (!preg_match('/^custom\.[a-z0-9_]+$/', $field) || $title === '' || $formula === '') {
            return null;
        }
        if (!in_array($format, ['number', 'percent', 'currency'], true)) {
            $format = 'number';
        }

        $dependencies = self::extract_formula_dependencies($formula);
        if ($dependencies === null || $dependencies === []) {
            return null;
        }

        return [
            'field' => $field,
            'title' => $title,
            'formula' => $formula,
            'format' => $format,
            'decimals' => $decimals,
        ];
    }

    public static function extract_formula_dependencies(string $formula): ?array
    {
        $tokens = self::tokenize_formula($formula);
        if ($tokens === null) {
            return null;
        }

        $deps = [];
        foreach ($tokens as $token) {
            if ($token['type'] !== 'field') {
                continue;
            }
            $field = $token['value'];
            if (str_starts_with($field, 'custom.')) {
                return null;
            }
            $deps[$field] = $field;
        }

        return array_values($deps);
    }

    private static function tokenize_formula(string $formula): ?array
    {
        $formula = strtolower(preg_replace('/\s+/', '', $formula));
        if ($formula === '') {
            return null;
        }

        $tokens = [];
        $offset = 0;
        $length = strlen($formula);
        while ($offset < $length) {
            $chunk = substr($formula, $offset);
            if (preg_match('/^([A-Za-z][A-Za-z0-9_\.]*|\d+(?:\.\d+)?|[()+\-*\/])/', $chunk, $matches) !== 1) {
                return null;
            }
            $raw = $matches[1];
            $offset += strlen($raw);

            if (preg_match('/^\d+(?:\.\d+)?$/', $raw)) {
                $tokens[] = ['type' => 'number', 'value' => (float)$raw];
            } elseif (in_array($raw, ['+', '-', '*', '/', '(', ')'], true)) {
                $tokens[] = ['type' => 'operator', 'value' => $raw];
            } else {
                if (!preg_match('/^(?:[a-z][a-z0-9_]*|event\.[a-z0-9_]+|custom\.[a-z0-9_]+)$/', $raw)) {
                    return null;
                }
                $tokens[] = ['type' => 'field', 'value' => $raw];
            }
        }

        if (!self::has_valid_formula_syntax($tokens)) {
            return null;
        }

        return $tokens;
    }

    private static function has_valid_formula_syntax(array $tokens): bool
    {
        if ($tokens === []) {
            return false;
        }

        $balance = 0;
        $expectsOperand = true;

        foreach ($tokens as $token) {
            if ($token['type'] === 'number' || $token['type'] === 'field') {
                if (!$expectsOperand) {
                    return false;
                }
                $expectsOperand = false;
                continue;
            }

            $op = $token['value'];
            if ($op === '(') {
                if (!$expectsOperand) {
                    return false;
                }
                $balance++;
                continue;
            }

            if ($op === ')') {
                if ($expectsOperand || $balance === 0) {
                    return false;
                }
                $balance--;
                $expectsOperand = false;
                continue;
            }

            if ($expectsOperand) {
                if ($op === '-') {
                    continue;
                }
                return false;
            }

            $expectsOperand = true;
        }

        return $balance === 0 && !$expectsOperand;
    }

    private static function evaluate_formula(string $formula, array $values): float
    {
        $tokens = self::tokenize_formula($formula);
        if ($tokens === null) {
            return 0.0;
        }

        $output = [];
        $operators = [];
        $precedence = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];

        foreach ($tokens as $index => $token) {
            if ($token['type'] === 'number') {
                $output[] = $token;
                continue;
            }
            if ($token['type'] === 'field') {
                $output[] = ['type' => 'number', 'value' => (float)($values[$token['value']] ?? 0.0)];
                continue;
            }

            $op = $token['value'];
            if ($op === '(') {
                $operators[] = $op;
                continue;
            }
            if ($op === ')') {
                while (!empty($operators) && end($operators) !== '(') {
                    $output[] = ['type' => 'operator', 'value' => array_pop($operators)];
                }
                if (empty($operators)) {
                    return 0.0;
                }
                array_pop($operators);
                continue;
            }

            $previous = $tokens[$index - 1]['value'] ?? null;
            if ($op === '-' && ($index === 0 || in_array($previous, ['+', '-', '*', '/', '('], true))) {
                $output[] = ['type' => 'number', 'value' => 0.0];
            }

            while (!empty($operators)) {
                $top = end($operators);
                if ($top === '(' || ($precedence[$top] ?? 0) < ($precedence[$op] ?? 0)) {
                    break;
                }
                $output[] = ['type' => 'operator', 'value' => array_pop($operators)];
            }
            $operators[] = $op;
        }

        while (!empty($operators)) {
            $op = array_pop($operators);
            if ($op === '(' || $op === ')') {
                return 0.0;
            }
            $output[] = ['type' => 'operator', 'value' => $op];
        }

        $stack = [];
        foreach ($output as $token) {
            if ($token['type'] === 'number') {
                $stack[] = (float)$token['value'];
                continue;
            }
            if (count($stack) < 2) {
                return 0.0;
            }
            $b = array_pop($stack);
            $a = array_pop($stack);
            $stack[] = match ($token['value']) {
                '+' => $a + $b,
                '-' => $a - $b,
                '*' => $a * $b,
                '/' => abs($b) < 1e-12 ? 0.0 : $a / $b,
                default => 0.0,
            };
        }

        if (count($stack) !== 1 || !is_finite($stack[0])) {
            return 0.0;
        }
        return (float)$stack[0];
    }

    private static function split_requested_stats_columns(array $selectedColumns): array
    {
        $normalizedColumns = self::normalize_stats_columns_config($selectedColumns);
        $selectedFields = [];
        $customColumns = [];
        foreach ($normalizedColumns as $column) {
            $field = $column['field'];
            $selectedFields[] = $field;
            if (!empty($column['custom'])) {
                $customColumns[] = $column;
            }
        }

        if ($normalizedColumns === [] && $selectedColumns !== []) {
            foreach ($selectedColumns as $field) {
                if (is_string($field)) {
                    $selectedFields[] = $field;
                }
            }
        }

        return [$selectedFields, $customColumns, $normalizedColumns];
    }

    private static function apply_custom_columns_to_tree(array &$tree, array $customColumns): void
    {
        foreach ($tree as &$row) {
            self::apply_custom_columns_to_row($row, $customColumns);
            if (!empty($row['_children']) && is_array($row['_children'])) {
                self::apply_custom_columns_to_tree($row['_children'], $customColumns);
            }
        }
    }

    private static function apply_custom_columns_to_row(array &$row, array $customColumns): void
    {
        foreach ($customColumns as $column) {
            $row[$column['field']] = round(self::evaluate_formula($column['formula'], $row), 8);
        }
    }

    private static function expand_stats_dependencies(array $fields): array
    {
        $expanded = [];
        $queue = array_values($fields);

        while ($queue !== []) {
            $field = array_shift($queue);
            if (!is_string($field) || $field === '' || in_array($field, $expanded, true)) {
                continue;
            }

            $expanded[] = $field;
            foreach (self::DERIVED_STATS_DEPENDENCIES[$field] ?? [] as $dependency) {
                if (!in_array($dependency, $expanded, true)) {
                    $queue[] = $dependency;
                }
            }
        }

        return $expanded;
    }

    private static function filter_tree_fields(array &$tree, array $allowedFields): void
    {
        foreach ($tree as &$row) {
            $children = [];
            if (isset($row['_children']) && is_array($row['_children'])) {
                $children = $row['_children'];
                self::filter_tree_fields($children, $allowedFields);
            }

            $filtered = [];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $row)) {
                    $filtered[$field] = $row[$field];
                }
            }
            if (array_key_exists('group', $row)) {
                $filtered['group'] = $row['group'];
            }
            if ($children !== []) {
                $filtered['_children'] = $children;
            }
            if (isset($row['_stats_totals']) && is_array($row['_stats_totals'])) {
                $filtered['_stats_totals'] = $row['_stats_totals'];
            }

            $row = $filtered;
        }
    }

    private static function filter_stats_totals_fields(array $totals, array $allowedFields): array
    {
        $filtered = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $totals)) {
                $filtered[$field] = $totals[$field];
            }
        }
        return $filtered;
    }

    private static function attach_stats_totals_to_tree(array &$tree, array $totals): void
    {
        foreach ($tree as &$row) {
            $row['_stats_totals'] = $totals;
            if (!empty($row['_children']) && is_array($row['_children'])) {
                self::attach_stats_totals_to_tree($row['_children'], $totals);
            }
        }
    }

    private static function sort_tree(array &$tree, array $orderby): void
    {
        if (!empty($orderby)) {
            usort($tree, function ($a, $b) use ($orderby) {
                foreach ($orderby as $rule) {
                    $field = $rule['field'] ?? '';
                    $dir = $rule['dir'] ?? 'asc';
                    $va = $a[$field] ?? 0;
                    $vb = $b[$field] ?? 0;
                    $cmp = is_numeric($va) && is_numeric($vb)
                        ? ((float)$va <=> (float)$vb)
                        : strcasecmp((string)$va, (string)$vb);
                    if ($dir === 'desc') {
                        $cmp = -$cmp;
                    }
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                }
                return 0;
            });
        }

        foreach ($tree as &$row) {
            if (!empty($row['_children']) && is_array($row['_children'])) {
                self::sort_tree($row['_children'], $orderby);
            }
        }
    }

    private function create_new_db(): bool
    {
        $db = null;
        try {
            // Read SQL schema and initial settings
            $createTableSQL = @file_get_contents(__DIR__ . "/db.sql");
            if ($createTableSQL === false) {
                throw new Exception("Failed to read database schema file");
            }

            $settingsJson = @file_get_contents(__DIR__ . '/common.json');
            if ($settingsJson === false) {
                throw new Exception("Failed to read common settings file");
            }

            // Initialize database
            $db = new SQLite3($this->dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
            $db->busyTimeout(5000);


            // Create tables
            $result = $db->exec($createTableSQL);
            if ($result === false) {
                throw new Exception($db->lastErrorMsg());
            }

            // Insert initial settings
            $query = "INSERT INTO common (settings) VALUES (:settings)";
            $stmt = $db->prepare($query);

            if ($stmt === false) {
                throw new Exception($db->lastErrorMsg());
            }

            $stmt->bindValue(':settings', $settingsJson, SQLITE3_TEXT);
            $result = $stmt->execute();

            if ($result === false) {
                throw new Exception($db->lastErrorMsg());
            }

            add_log("trace", "Successfully initialized database with schema and common settings");
            return true;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->exec('ROLLBACK');
                add_log("errors", "Failed to initialize database: " . $e->getMessage());
            } else {
                die("Critical error initializing database: " . $e->getMessage());
            }
            return false;
        } finally {
            if (isset($db))
                $db->close();
        }
    }

    private function open_db(bool $readOnly = false): SQLite3
    {
        if ($readOnly && $this->readDb !== null) {
            return $this->readDb;
        }
        if (!$readOnly && $this->writeDb !== null) {
            return $this->writeDb;
        }

        $db = new SQLite3($this->dbPath, $readOnly ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);

        // Optimizations
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA journal_mode = wal');
        $db->exec('PRAGMA mmap_size = 268435456');    // 256MB memory mapping
        $db->exec('PRAGMA cache_size = -64000');      // 64MB cache pages  
        $db->exec('PRAGMA temp_store = MEMORY');      // temporary data in RAM

        if (!$readOnly) {
            $db->exec('PRAGMA synchronous = OFF');    // only for writing
            $this->writeDb = $db;
        } else {
            $this->readDb = $db;
        }

        return $db;
    }
    public function __destruct()
    {
        if ($this->readDb !== null) {
            $this->readDb->close();
            $this->readDb = null;
        }
        if ($this->writeDb !== null) {
            $this->writeDb->close();
            $this->writeDb = null;
        }
    }

    public function get_clicks_paginated(string $filter, int $startdate, int $enddate, ?int $campId, int $page, int $size, string $sortField = 'time', string $sortDir = 'desc', array $filters = [], array $paramColumns = [], string $searchTerm = ''): array
    {
        $allowedSort = ['id','time','ip','country','lang','os','osver','client','clientver','device','brand','model','isp','ua','userid','clickid','flow','path','step','status','payout','reason'];
        // Support sorting by param.* fields via json_extract
        $sortExpr = 'time';
        if (in_array($sortField, $allowedSort)) {
            $sortExpr = $sortField;
        } elseif (str_starts_with($sortField, 'param.')) {
            $key = substr($sortField, 6);
            if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                $sortExpr = "json_extract(params, '\$.$key')";
            }
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $size;

        switch ($filter) {
            case 'blocked':
                $table = 'blocked';
                $where = "time BETWEEN ? AND ? AND campaign_id = ?";
                $bindParams = [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER, $campId => SQLITE3_INTEGER];
                break;
            case 'leads':
                $table = 'clicks';
                $where = "time BETWEEN ? AND ? AND campaign_id = ? AND status IS NOT NULL";
                $bindParams = [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER, $campId => SQLITE3_INTEGER];
                break;
            case 'trafficback':
                $table = 'trafficback';
                $where = "time BETWEEN ? AND ?";
                $bindParams = [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER];
                break;
            default: // allowed
                $table = 'clicks';
                $where = "time BETWEEN ? AND ? AND campaign_id = ?";
                $bindParams = [$startdate => SQLITE3_INTEGER, $enddate => SQLITE3_INTEGER, $campId => SQLITE3_INTEGER];
                break;
        }
        $tableFilterFields = match ($table) {
            'blocked' => ['country', 'lang', 'os', 'osver', 'brand', 'model', 'device', 'isp', 'client', 'clientver', 'reason'],
            'trafficback' => ['country', 'lang', 'os', 'osver', 'brand', 'model', 'device', 'isp', 'client', 'clientver'],
            default => ['country', 'lang', 'os', 'osver', 'brand', 'model', 'device', 'isp', 'client', 'clientver', 'flow', 'step', 'path', 'status'],
        };

        // Build filter WHERE clauses (positional ? placeholders)
        $filterWhere = '';
        // Ordered list of [value, type] for all bind params
        $bindList = [];
        foreach ($bindParams as $val => $type) $bindList[] = [$val, $type];

        if (!empty($filters) && !empty($filters['rules']) && is_array($filters['rules'])) {
            $filterParts = [];
            foreach ($filters['rules'] as $rule) {
                $field = $rule['field'] ?? '';
                $op = $rule['operator'] ?? '';
                $value = $rule['value'] ?? '';
                if (!str_starts_with($field, 'param.') && !in_array($field, $tableFilterFields, true)) {
                    continue;
                }

                $sqlField = self::resolveFilterField($field);
                if ($sqlField === null || !in_array($op, self::FILTER_OPERATORS)) {
                    continue;
                }

                switch ($op) {
                    case '=':
                        $filterParts[] = "$sqlField = ?";
                        $bindList[] = [$value, SQLITE3_TEXT];
                        break;
                    case '!=':
                        $filterParts[] = "$sqlField != ?";
                        $bindList[] = [$value, SQLITE3_TEXT];
                        break;
                    case 'in':
                        $vals = is_array($value) ? $value : array_map('trim', explode(',', $value));
                        $filterParts[] = "$sqlField IN (" . implode(',', array_fill(0, count($vals), '?')) . ")";
                        foreach ($vals as $v) $bindList[] = [$v, SQLITE3_TEXT];
                        break;
                    case 'not_in':
                        $vals = is_array($value) ? $value : array_map('trim', explode(',', $value));
                        $filterParts[] = "$sqlField NOT IN (" . implode(',', array_fill(0, count($vals), '?')) . ")";
                        foreach ($vals as $v) $bindList[] = [$v, SQLITE3_TEXT];
                        break;
                    case 'is_null':
                        $filterParts[] = "($sqlField IS NULL OR $sqlField = '')";
                        break;
                    case 'is_not_null':
                        $filterParts[] = "($sqlField IS NOT NULL AND $sqlField != '')";
                        break;
                }
            }
            $condition = ($filters['condition'] ?? 'AND') === 'OR' ? ' OR ' : ' AND ';
            if (!empty($filterParts)) {
                $filterWhere = ' AND (' . implode($condition, $filterParts) . ')';
            }
        }

        $searchTerm = trim($searchTerm);
        $searchWhere = '';
        if ($searchTerm !== '' && in_array($filter, ['allowed', 'leads'], true)) {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm);
            $likePattern = '%' . $escapedSearch . '%';
            $searchWhere = " AND (userid LIKE ? ESCAPE '\\' OR clickid LIKE ? ESCAPE '\\')";
            $bindList[] = [$likePattern, SQLITE3_TEXT];
            $bindList[] = [$likePattern, SQLITE3_TEXT];
        }

        $countQuery = "SELECT COUNT(*) as total FROM $table WHERE $where$filterWhere$searchWhere";
        $countResult = $this->exec_bind_list_query($countQuery, $bindList, true);
        $total = (int)($countResult['total'] ?? 0);

        $dataQuery = "SELECT * FROM $table WHERE $where$filterWhere$searchWhere ORDER BY $sortExpr COLLATE NOCASE $sortDir LIMIT $size OFFSET $offset";
        $clicks = $this->exec_bind_list_query($dataQuery, $bindList);
        foreach ($clicks as &$click) {
            self::decode_click_row($click);
            // Extract requested param columns
            foreach ($paramColumns as $key) {
                $click["param.$key"] = $click['params'][$key] ?? null;
            }
        }

        return [
            'last_page' => max(1, (int)ceil($total / $size)),
            'data' => $clicks,
        ];
    }

    public function get_click_by_clickid(string $clickid): array
    {
        if (empty($clickid)) {
            add_log("trace", "Skipping click retrieval - empty clickid provided");
            return [];
        }

        $query = "SELECT * FROM clicks WHERE clickid = :clickid ORDER BY time DESC LIMIT 1";
        $clicks = $this->exec_read_query($query, [$clickid => SQLITE3_TEXT]);
        foreach ($clicks as &$click) {
            self::decode_click_row($click);
        }
        return $clicks[0] ?? [];
    }

    private function get_stats_select_parts(array $selectedFields): array
    {
        $selectParts = [];
        // Process selected fields
        foreach ($selectedFields as $field) {
            switch ($field) {
                case 'clicks':
                    $selectParts[] = "COUNT(c.id) AS clicks";
                    break;
                case 'uniques':
                    $selectParts[] = "COUNT(DISTINCT userid) AS uniques";
                    break;
                case 'uniques_ratio':
                    $selectParts[] = "(COUNT(DISTINCT userid)*1.0/COUNT(*) * 100.0) AS uniques_ratio";
                    break;
                case 'cra':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) * 100.0 / COUNT(*)) AS cra";
                    break;
                case 'crs':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) * 100.0 / COUNT(*)) AS crs";
                    break;
                case 'epc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(c.id)) AS epc";
                    break;
                case 'uepc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(DISTINCT(userid))) AS uepc";
                    break;
                case 'cpc':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(c.id)) AS cpc";
                    break;
                case 'ucpc':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(DISTINCT(userid))) AS ucpc";
                    break;
                case 'appt':
                    $selectParts[] = "CASE
                            WHEN COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) = 0
                                 OR (COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) - COUNT(DISTINCT CASE WHEN status = 'Trash' THEN c.id END)) = 0
                            THEN 0
                            ELSE (COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) * 100.0 / (COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) - COUNT(DISTINCT CASE WHEN status = 'Trash' THEN c.id END)))
                       END AS appt";
                    break;
                case 'app':
                    $selectParts[] = "CASE
                            WHEN COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) = 0
                                 OR COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END) = 0
                            THEN 0
                            ELSE (COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN c.id END) * 100.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN c.id END))
                       END AS app";
                    break;
                case 'conversion':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN clickid END) AS conversion";
                    break;
                case 'purchase':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN clickid END) AS purchase";
                    break;
                case 'hold':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Lead' THEN clickid END) AS hold";
                    break;
                case 'reject':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Reject' THEN clickid END) AS reject";
                    break;
                case 'trash':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Trash' THEN clickid END) AS trash";
                    break;
                case 'ec':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN clickid END)) AS ec";
                    break;
                case 'cpa':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN clickid END)) AS cpa";
                    break;
                case 'revenue':
                    $selectParts[] = "SUM(payout) AS revenue";
                    break;
                case 'costs':
                    $selectParts[] = "SUM(cost) AS costs";
                    break;
                case 'profit':
                    $selectParts[] = "(SUM(payout) - SUM(cost)) as profit";
                    break;
                case 'roi':
                    $selectParts[] = "((SUM(payout) - SUM(cost))*1.0 / SUM(cost) * 100.0) as roi";
                    break;
                default:
                    if (str_starts_with($field, 'event.')) {
                        $eventName = substr($field, 6);
                        if (preg_match('/^[a-z0-9_]+$/', $eventName)) {
                            $selectParts[] = "COALESCE(SUM(CAST(json_extract(events, '$.$eventName') AS REAL)), 0) AS \"$field\"";
                        }
                    }
                    break;
            }
        }
        return $selectParts;
    }

    private const FILTERABLE_FIELDS = [
        'country', 'lang', 'os', 'osver', 'brand', 'model', 'device',
        'isp', 'client', 'clientver', 'flow', 'step', 'path', 'status', 'reason'
    ];

    private const FILTER_OPERATORS = ['=', '!=', 'in', 'not_in', 'is_null', 'is_not_null'];

    private static function resolveFilterField(string $field): ?string {
        if (in_array($field, self::FILTERABLE_FIELDS)) {
            return $field;
        }
        if (str_starts_with($field, 'param.')) {
            $key = substr($field, 6);
            if (preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                return "json_extract(params, '\$.$key')";
            }
        }
        return null;
    }

    private function buildFilterWhere(array $filters): array {
        $filterWhere = '';
        $filterBinds = [];
        if (empty($filters) || !isset($filters['rules']) || !is_array($filters['rules'])) {
            return [$filterWhere, $filterBinds];
        }

        $filterParts = [];
        foreach ($filters['rules'] as $i => $rule) {
            $field = $rule['field'] ?? '';
            $op = $rule['operator'] ?? '';
            $value = $rule['value'] ?? '';

            $sqlField = self::resolveFilterField($field);
            if ($sqlField === null || !in_array($op, self::FILTER_OPERATORS)) {
                continue;
            }

            $paramName = ":filter_{$i}";
            switch ($op) {
                case '=':
                    $filterParts[] = "$sqlField = $paramName";
                    $filterBinds[$paramName] = $value;
                    break;
                case '!=':
                    $filterParts[] = "$sqlField != $paramName";
                    $filterBinds[$paramName] = $value;
                    break;
                case 'in':
                    $vals = is_array($value) ? $value : array_map('trim', explode(',', $value));
                    $placeholders = [];
                    foreach ($vals as $vi => $v) {
                        $p = ":filter_{$i}_{$vi}";
                        $placeholders[] = $p;
                        $filterBinds[$p] = $v;
                    }
                    $filterParts[] = "$sqlField IN (" . implode(',', $placeholders) . ")";
                    break;
                case 'not_in':
                    $vals = is_array($value) ? $value : array_map('trim', explode(',', $value));
                    $placeholders = [];
                    foreach ($vals as $vi => $v) {
                        $p = ":filter_{$i}_{$vi}";
                        $placeholders[] = $p;
                        $filterBinds[$p] = $v;
                    }
                    $filterParts[] = "$sqlField NOT IN (" . implode(',', $placeholders) . ")";
                    break;
                case 'is_null':
                    $filterParts[] = "$sqlField IS NULL";
                    break;
                case 'is_not_null':
                    $filterParts[] = "$sqlField IS NOT NULL";
                    break;
            }
        }
        $condition = ($filters['condition'] ?? 'AND') === 'OR' ? ' OR ' : ' AND ';
        if (!empty($filterParts)) {
            $filterWhere = ' AND (' . implode($condition, $filterParts) . ')';
        }

        return [$filterWhere, $filterBinds];
    }

    public function get_statistics(
        array $selectedColumns,
        array $groupByFields,
        int $campId,
        string $startDate,
        string $endDate,
        string $timezone,
        array $filters = [],
        array $orderby = []
    ): array {
        [$selectedFields, $customColumns] = self::split_requested_stats_columns($selectedColumns);
        $queryFields = $selectedFields;
        foreach ($customColumns as $customColumn) {
            foreach (self::extract_formula_dependencies($customColumn['formula']) ?? [] as $dependency) {
                if (!in_array($dependency, $queryFields, true)) {
                    $queryFields[] = $dependency;
                }
            }
        }
        $queryFields = self::expand_stats_dependencies($queryFields);

        $baseQuery =
            "SELECT %s FROM clicks c WHERE campaign_id = :campid AND time BETWEEN :startDate AND :endDate";
        $selectParts = [];
        $groupByParts = [];
        $orderByParts = [];

        $selectParts = $this->get_stats_select_parts($queryFields);

        [$filterWhere, $filterBinds] = $this->buildFilterWhere($filters);

        // Process group by fields
        foreach ($groupByFields as $field) {
            if ($field === 'date') {

                $dateTime = new DateTime('now', new DateTimeZone($timezone));
                // Get the offset in seconds from UTC
                $offsetInSeconds = $dateTime->getOffset();
                // Convert this offset to an SQLite compatible format (HH:MM)
                $hours = floor($offsetInSeconds / 3600);
                $minutes = floor(($offsetInSeconds % 3600) / 60);
                $offsetFormatted = sprintf('%+03d:%02d', $hours, $minutes);

                $selectParts[] =
                    "strftime('%Y-%m-%d', datetime(time, 'unixepoch', '{$offsetFormatted}')) AS date";
                $groupByParts[] = "date";
                $orderByParts[] = "date";
            } elseif (in_array($field, ['country', 'lang', 'os', 'osver', 'brand', 'model', 'device', 'isp', 'client', 'clientver', 'flow', 'step', 'path'])) {
                $selectParts[] = $field;
                $groupByParts[] = $field;
                $orderByParts[] = $field;
            } else {
                // JSON fields — strip param. prefix if present
                $jsonKey = str_starts_with($field, 'param.') ? substr($field, 6) : $field;
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $jsonKey)) continue;
                $alias = $jsonKey;
                $jsonExtract = "COALESCE(json_extract(params, '$." . $jsonKey . "'), 'unknown') AS " . $alias;
                $selectParts[] = $jsonExtract;
                $groupByParts[] = $alias;
                $orderByParts[] = $alias;
            }
        }

        // Construct the SQL query
        if ($selectParts === []) {
            return [];
        }
        $selectClause = implode(', ', $selectParts);
        $groupByClause = !empty($groupByParts) ? "GROUP BY " . implode(', ', $groupByParts) : '';
        $orderByClause = !empty($orderByParts) ? "ORDER BY " . implode(', ', $orderByParts) : '';
        $sqlQuery = sprintf($baseQuery, $selectClause) . $filterWhere . " " . $groupByClause . " " . $orderByClause;

        $db = $this->open_db(true);
        $stmt = $db->prepare($sqlQuery);
        if ($stmt === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Error preparing statistics statement: $errorMessage");
            return [];
        }

        $stmt->bindValue(':campid', $campId, SQLITE3_INTEGER);
        $stmt->bindValue(':startDate', $startDate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $endDate, SQLITE3_INTEGER);
        foreach ($filterBinds as $param => $val) {
            $stmt->bindValue($param, $val, SQLITE3_TEXT);
        }
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Error executing statistics statement: $errorMessage");
            return [];
        }

        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        // Normalize groupby field names: strip param. prefix so they match SQL aliases
        $normalizedGroupBy = array_map(function($f) {
            return str_starts_with($f, 'param.') ? substr($f, 6) : $f;
        }, $groupByFields);

        // Build the tree structure
        $tree = $this->build_tree($rows, $normalizedGroupBy, $queryFields, 0, $orderby);
        if (!empty($customColumns)) {
            $statsTotals = $this->calculate_totals($rows, $queryFields);
            self::apply_custom_columns_to_row($statsTotals, $customColumns);
            $statsTotals = self::filter_stats_totals_fields($statsTotals, $selectedFields);
            self::apply_custom_columns_to_tree($tree, $customColumns);
            self::sort_tree($tree, $orderby);
        }
        self::filter_tree_fields($tree, $selectedFields);
        if (!empty($customColumns)) {
            self::attach_stats_totals_to_tree($tree, $statsTotals);
        }
        return $tree;
    }

    private function build_tree(array $rows, array $groupByFields, array $selectedFields, int $level = 0, array $orderby = []): array
    {
        if (empty($groupByFields) || $level >= count($groupByFields)) {
            // No grouping: recalculate derived metrics to fix SQL NULLs from division by zero
            if ($level === 0 && !empty($rows)) {
                return [$this->calculate_totals($rows, $selectedFields)];
            }
            return $rows;
        }

        $groupField = $groupByFields[$level];
        $groupedData = [];

        // Group rows by current level's field
        foreach ($rows as $row) {
            $groupValue = $row[$groupField];
            // Convert all numeric values to strings to prevent implicit conversions
            if (is_numeric($groupValue)) {
                $groupValue = (string) $groupValue;
            }
            if (!isset($groupedData[$groupValue])) {
                $groupedData[$groupValue] = [];
            }
            $groupedData[$groupValue][] = $row;
        }

        $tree = [];
        foreach ($groupedData as $groupValue => $groupRows) {
            // For leaf nodes or single level grouping
            if ($level >= count($groupByFields) - 1) {
                $totals = $this->calculate_totals($groupRows, $selectedFields);
                $totals['group'] = $groupValue;
                $tree[] = $totals;
            } else {
                $children = $this->build_tree($groupRows, $groupByFields, $selectedFields, $level + 1, $orderby);
                $totals = $this->calculate_totals($groupRows, $selectedFields);
                $node = array_merge(
                    array_diff_key($totals, array_flip($groupByFields)),
                    ['_children' => $children],
                    ['group' => $groupValue]  // Put this last to override any 'group' from totals
                );
                $tree[] = $node;
            }
        }

        if (!empty($orderby)) {
            self::sort_tree($tree, $orderby);
        }

        return $tree;
    }

    private function calculate_totals(array $rows, array $selectedFields): array
    {
        $totals = array_fill_keys($selectedFields, 0);

        foreach ($rows as $row) {
            foreach ($selectedFields as $field) {
                if (isset($row[$field]) && is_numeric($row[$field])) {
                    $totals[$field] += $row[$field];
                }
            }
        }

        // Recalculate derived (non-additive) fields from summed base metrics
        // Percentage metrics: round to 4 decimals (frontend trims to 2)
        if (in_array('uniques_ratio', $selectedFields))
            $totals['uniques_ratio'] = $totals['clicks'] === 0 ? 0 : round($totals['uniques'] * 100.0 / $totals['clicks'], 4);
        if (in_array('cra', $selectedFields))
            $totals['cra'] = $totals['clicks'] === 0 ? 0 : round($totals['conversion'] * 100.0 / $totals['clicks'], 4);
        if (in_array('crs', $selectedFields))
            $totals['crs'] = $totals['clicks'] === 0 ? 0 : round($totals['purchase'] * 100.0 / $totals['clicks'], 4);
        if (in_array('appt', $selectedFields)) {
            $denom = $totals['conversion'] - $totals['trash'];
            $totals['appt'] = $denom === 0 ? 0 : round($totals['purchase'] * 100.0 / $denom, 4);
        }
        if (in_array('app', $selectedFields))
            $totals['app'] = $totals['conversion'] === 0 ? 0 : round($totals['purchase'] * 100.0 / $totals['conversion'], 4);
        if (in_array('roi', $selectedFields))
            $totals['roi'] = $totals['costs'] === 0 ? 0 : round(($totals['revenue'] - $totals['costs']) * 100.0 / $totals['costs'], 4);

        // Money-per-unit metrics: round to 6 decimals (frontend trims to 2-5)
        if (in_array('epc', $selectedFields))
            $totals['epc'] = $totals['clicks'] === 0 ? 0 : round($totals['revenue'] * 1.0 / $totals['clicks'], 6);
        if (in_array('uepc', $selectedFields))
            $totals['uepc'] = $totals['uniques'] === 0 ? 0 : round($totals['revenue'] * 1.0 / $totals['uniques'], 6);
        if (in_array('cpc', $selectedFields))
            $totals['cpc'] = $totals['clicks'] === 0 ? 0 : round($totals['costs'] * 1.0 / $totals['clicks'], 6);
        if (in_array('ucpc', $selectedFields))
            $totals['ucpc'] = $totals['uniques'] === 0 ? 0 : round($totals['costs'] * 1.0 / $totals['uniques'], 6);
        if (in_array('ec', $selectedFields))
            $totals['ec'] = $totals['conversion'] === 0 ? 0 : round($totals['revenue'] * 1.0 / $totals['conversion'], 6);
        if (in_array('cpa', $selectedFields))
            $totals['cpa'] = $totals['conversion'] === 0 ? 0 : round($totals['costs'] * 1.0 / $totals['conversion'], 6);

        // Composite additive metrics: recalculate from base to avoid stale SQL values
        if (in_array('profit', $selectedFields))
            $totals['profit'] = round($totals['revenue'] - $totals['costs'], 6);

        return $totals;
    }

    private function add_click(string $query, array $click): bool
    {
        try {
            $db = $this->open_db();
            $stmt = $db->prepare($query);

            if ($stmt === false) {
                throw new Exception($db->lastErrorMsg());
            }

            foreach ($click as $key => $value) {
                if (!isset($value)) {
                    add_log("warning", "Null value found for field '$key' in click data");
                    $value = '';
                }
                $stmt->bindValue(':' . $key, $value);
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception($db->lastErrorMsg());
            }

            add_log("trace", "Successfully added click for IP: " . ($click['ip'] ?? 'unknown'));
            return true;
        } catch (Exception $e) {
            add_log("errors", "Failed to add click: " . $e->getMessage() . ", Data: " . json_encode($click));
            return false;
        }
    }

    public function add_trafficback_click($data): bool
    {
        $click = $this->prepare_click_data($data);
        $query = "INSERT INTO trafficback (time, ip, country, lang, os, osver, brand, model, isp, client, clientver, ua, params) VALUES (:time, :ip, :country, :lang, :os, :osver, :brand, :model, :isp, :client, :clientver, :ua, :params)";
        return $this->add_click($query, $click);
    }

    public function add_white_click($data, $reason, $campId): bool
    {
        $click = $this->prepare_click_data($data, $campId);
        $click['reason'] = $reason;
        $query = "INSERT INTO blocked (campaign_id, time, ip, country, lang, os, osver, brand, model, isp, client, clientver, ua, reason, params) VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :brand, :model, :isp, :client, :clientver, :ua, :reason, :params)";
        return $this->add_click($query, $click);
    }

    public function add_black_click(string $userid, string $clickid, $data, array $path, string $flow, int $campId): bool
    {
        $click = $this->prepare_click_data($data, $campId);
        $click['userid'] = $userid;
        $click['clickid'] = $clickid;
        $click['flow'] = empty($flow) ? 'unknown' : $flow;
        $click['path'] = json_encode($path);
        $click['step'] = 0;
        $click['status'] = null;

        $query = "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, client, clientver, device, brand, model, isp, ua, userid, clickid, flow, path, step, params, cost, status) VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :client, :clientver, :device, :brand, :model, :isp, :ua, :userid, :clickid, :flow, :path, :step, :params, :cpc, NULL)";

        return $this->add_click($query, $click);
    }

    public function add_click_step(string $clickid, int $step, string $variant): bool
    {
        if (empty($clickid)) {
            add_log("warning", "Skipping step insertion - empty clickid provided");
            return false;
        }
        if ($step < 0) {
            add_log("warning", "Skipping step insertion - invalid step provided: $step");
            return false;
        }

        if (!$this->clickid_exists($clickid)) {
            add_log("warning", "Skipping step insertion - clickid not found: $clickid");
            return false;
        }

        try {
            $db = $this->open_db();
            $db->exec('BEGIN IMMEDIATE');

            $insertStmt = $db->prepare("INSERT OR IGNORE INTO click_steps (clickid, step, variant, time) VALUES (:clickid, :step, :variant, :time)");
            if ($insertStmt === false) {
                throw new Exception('Failed to prepare click_steps insert: ' . $db->lastErrorMsg());
            }
            $insertStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $insertStmt->bindValue(':step', $step, SQLITE3_INTEGER);
            $insertStmt->bindValue(':variant', $variant, SQLITE3_TEXT);
            $insertStmt->bindValue(':time', time(), SQLITE3_INTEGER);
            if ($insertStmt->execute() === false) {
                throw new Exception('Failed to insert click step: ' . $db->lastErrorMsg());
            }

            $updateStmt = $db->prepare("UPDATE clicks SET step = MAX(step, :newStep) WHERE clickid = :clickid");
            if ($updateStmt === false) {
                throw new Exception('Failed to prepare click step update: ' . $db->lastErrorMsg());
            }
            $updateStmt->bindValue(':newStep', $step, SQLITE3_INTEGER);
            $updateStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            if ($updateStmt->execute() === false) {
                throw new Exception('Failed to update click current step: ' . $db->lastErrorMsg());
            }

            $db->exec('COMMIT');
            return true;
        } catch (Exception $e) {
            $this->writeDb?->exec('ROLLBACK');
            add_log('errors', $e->getMessage());
            return false;
        }
    }

    public function update_click_path(string $clickid, array $path): bool
    {
        if (empty($clickid)) {
            add_log("warning", "Skipping path update - empty clickid provided");
            return false;
        }
        $pathJson = json_encode(array_values($path));
        if ($pathJson === false) {
            add_log("warning", "Skipping path update - invalid path JSON for clickid: $clickid");
            return false;
        }

        $query = "UPDATE clicks SET path = :path WHERE clickid = :clickid";
        return $this->exec_update_query($query, [$pathJson => SQLITE3_TEXT, $clickid => SQLITE3_TEXT]);
    }

    public function add_lead(string $clickid, array $leaddata, string $status = 'Lead'): bool
    {
        if (empty($clickid)) {
            add_log("warning", "Skipping lead addition - empty clickid provided");
            return false;
        }

        $updateQuery = "UPDATE clicks SET status = :status, leaddata = :leaddata WHERE id = (SELECT id FROM clicks WHERE clickid = :clickid ORDER BY time DESC LIMIT 1)";
        return $this->exec_update_query($updateQuery, [$status => SQLITE3_TEXT, $leaddata => SQLITE3_TEXT, $clickid => SQLITE3_TEXT]);
    }

    public function update_status(string $clickid, string $status, float $payout): bool
    {
        if (empty($clickid)) {
            add_log("warning", "Skipping status update - empty clickid provided");
            return false;
        }

        if (!$this->clickid_exists($clickid)) {
            add_log("warning", "Skipping status update - clickid not found: $clickid");
            return false;
        }

        if (!is_numeric($payout)) {
            throw new Exception("Invalid payout value: $payout");
        }

        $updateQuery = "UPDATE clicks SET status = :status, payout = :payout WHERE id = (SELECT id FROM clicks WHERE clickid = :clickid ORDER BY time DESC LIMIT 1)";
        return $this->exec_update_query($updateQuery, [$status => SQLITE3_TEXT, $payout => SQLITE3_FLOAT, $clickid => SQLITE3_TEXT]);
    }

    public function update_click_params(int $clickId, array $params): bool
    {
        if (empty($clickId)) {
            add_log("warning", "Skipping params update - empty click ID provided");
            return false;
        }

        $paramsJson = json_encode($params);
        if ($paramsJson === false) {
            add_log("warning", "Failed to encode params to JSON for click ID: $clickId");
            return false;
        }

        $updateQuery = "UPDATE clicks SET params = :params WHERE id = :id";
        return $this->exec_update_query($updateQuery, [$paramsJson => SQLITE3_TEXT, $clickId => SQLITE3_INTEGER]);
    }

    public function add_click_event(string $clickid, string $eventName, float $eventValue): bool
    {
        if ($clickid === '' || !preg_match('/^[a-z0-9_]+$/', $eventName) || !is_finite($eventValue)) {
            return false;
        }

        $db = $this->open_db();
        try {
            $db->exec('BEGIN IMMEDIATE');

            $clickStmt = $db->prepare('SELECT id, step, events FROM clicks WHERE clickid = :clickid ORDER BY time DESC LIMIT 1');
            if ($clickStmt === false) {
                throw new Exception('Failed to prepare click lookup: ' . $db->lastErrorMsg());
            }
            $clickStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $clickRow = $clickStmt->execute()?->fetchArray(SQLITE3_ASSOC) ?: null;
            if (!is_array($clickRow)) {
                throw new Exception('Click not found for clickid ' . $clickid);
            }

            $events = [];
            if (!empty($clickRow['events'])) {
                $decoded = json_decode((string)$clickRow['events'], true);
                if (is_array($decoded)) {
                    $events = $decoded;
                }
            }
            $events[$eventName] = round(((float)($events[$eventName] ?? 0)) + $eventValue, 6);
            $eventsJson = json_encode($events);
            if ($eventsJson === false) {
                throw new Exception('Failed to encode events JSON');
            }

            $insertStmt = $db->prepare('INSERT INTO click_event_log (clickid, time, step_index, event_name, event_value) VALUES (:clickid, :time, :step_index, :event_name, :event_value)');
            if ($insertStmt === false) {
                throw new Exception('Failed to prepare event insert: ' . $db->lastErrorMsg());
            }
            $insertStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $insertStmt->bindValue(':time', time(), SQLITE3_INTEGER);
            $insertStmt->bindValue(':step_index', max(0, (int)($clickRow['step'] ?? 0)), SQLITE3_INTEGER);
            $insertStmt->bindValue(':event_name', $eventName, SQLITE3_TEXT);
            $insertStmt->bindValue(':event_value', $eventValue, SQLITE3_FLOAT);
            if ($insertStmt->execute() === false) {
                throw new Exception('Failed to insert event: ' . $db->lastErrorMsg());
            }

            $updateStmt = $db->prepare('UPDATE clicks SET events = :events WHERE id = :id');
            if ($updateStmt === false) {
                throw new Exception('Failed to prepare events update: ' . $db->lastErrorMsg());
            }
            $updateStmt->bindValue(':events', $eventsJson, SQLITE3_TEXT);
            $updateStmt->bindValue(':id', (int)$clickRow['id'], SQLITE3_INTEGER);
            if ($updateStmt->execute() === false) {
                throw new Exception('Failed to update click events: ' . $db->lastErrorMsg());
            }

            $db->exec('COMMIT');
            return true;
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            add_log('errors', 'Failed to add click event: ' . $e->getMessage());
            return false;
        }
    }

    public function get_event_names(int $campId): array
    {
        $query = 'SELECT DISTINCT cel.event_name AS event_name FROM click_event_log cel INNER JOIN clicks c ON c.clickid = cel.clickid WHERE c.campaign_id = :campid ORDER BY cel.event_name';
        $rows = $this->exec_read_query($query, [$campId => SQLITE3_INTEGER]);
        return array_values(array_filter(array_map(fn($row) => $row['event_name'] ?? null, $rows)));
    }

    public function get_funnel_stats(int $campId, string $flowName, string $status): array
    {
        $query = "SELECT path, COUNT(*) AS impressions, COUNT(CASE WHEN status = :status THEN 1 END) AS conversions FROM clicks WHERE campaign_id = :cid AND flow = :flow GROUP BY path";
        return $this->exec_read_query($query, [$status => SQLITE3_TEXT, $campId => SQLITE3_INTEGER, $flowName => SQLITE3_TEXT]);
    }

    public function get_variant_stats(int $campId, string $flowName, int $stepIndex, string $status): array
    {
        $query = "
            SELECT
                cs.variant AS variant,
                COUNT(*) AS impressions,
                COUNT(CASE WHEN c.status = :status THEN 1 END) AS conversions
            FROM click_steps cs
            INNER JOIN clicks c ON c.clickid = cs.clickid
            WHERE c.campaign_id = :cid AND c.flow = :flow AND cs.step = :step
            GROUP BY cs.variant
        ";
        return $this->exec_read_query($query, [
            $status => SQLITE3_TEXT,
            $campId => SQLITE3_INTEGER,
            $flowName => SQLITE3_TEXT,
            $stepIndex => SQLITE3_INTEGER,
        ]);
    }

    private function clickid_exists(string $clickid): bool
    {
        if (empty($clickid)) {
            add_log("warning", "Empty clickid provided for existence check");
            return false;
        }
        $query = "SELECT COUNT(*) AS count FROM clicks WHERE clickid = :clickid";
        $res = $this->exec_read_query($query, [$clickid => SQLITE3_TEXT], true);
        return $res['count'] > 0;
    }

    private function prepare_click_data($data, $campId = null): array
    {
        $data["time"] = (new DateTime())->getTimestamp();
        if (!is_null($campId))
            $data["campaign_id"] = $campId;

        $query = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $query);
        }

        if (array_key_exists("cpc", $query)) {
            $data["cpc"] = $query["cpc"];
            unset($query["cpc"]);
        }

        $data["params"] = json_encode($query);
        return $data;
    }

    public function add_campaign($name): bool|int
    {
        $query = "INSERT INTO campaigns (name, settings) VALUES (:name, :settings)";

        $settingsJson = file_get_contents(__DIR__ . '/default.json');
        $settings = json_decode($settingsJson, true);
        $settings['apikey'] = $this->generate_api_key();
        $settingsJson = json_encode($settings);
        return $this->exec_write_query($query, [$name => SQLITE3_TEXT, $settingsJson => SQLITE3_TEXT], true);
    }

    private function generate_api_key(): string
    {
        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }

    public function get_campaign_by_apikey(string $apikey): array
    {
        $query = "SELECT * FROM campaigns WHERE settings->>'apikey' = :apikey";
        $camp = $this->exec_read_query($query, [$apikey => SQLITE3_TEXT], true);
        if (isset($camp['settings'])) {
            $camp['settings'] = json_decode($camp['settings'], true);
        }
        return $camp;
    }

    public function clone_campaign($id): bool|int
    {
        $query = "INSERT INTO campaigns (name, settings)
                  SELECT name || ' (Clone)', settings FROM campaigns WHERE id = :id";
        return $this->exec_write_query($query, [$id => SQLITE3_INTEGER], true);
    }

    public function get_campaign_name(int $id): string
    {
        $query = "SELECT name FROM campaigns WHERE id = :id";
        $arr = $this->exec_read_query($query, [$id => SQLITE3_INTEGER], true);
        return $arr['name'] ?? '';
    }

    public function get_campaigns_list(): array
    {
        $query = "SELECT id, name FROM campaigns ORDER BY name COLLATE NOCASE ASC";
        return $this->exec_read_query($query, []);
    }

    public function get_campaign_settings(int $id): array
    {
        $query = "SELECT settings FROM campaigns WHERE id = :id";
        $arr = $this->exec_read_query($query, [$id => SQLITE3_INTEGER], true);
        $settings = json_decode($arr['settings'], true);
        return $settings;
    }

    public function get_campaign_by_domain(): array|bool
    {
        $cPath = get_tds_path(true, false);
        $parsedUrl = parse_url($cPath);
        $domain = isset($parsedUrl['port']) ?
            $parsedUrl['host'] . ":" . $parsedUrl['port'] :
            $parsedUrl['host'];

        $query = "SELECT * FROM campaigns";
        $campaigns = $this->exec_read_query($query, []);
        foreach ($campaigns as $campaign) {
            if (empty($campaign['settings'])) {
                continue;
            }
            $settings = json_decode($campaign['settings'], true);
            if (!isset($settings['domains'])) {
                continue;
            }
            if ($this->match_domain($settings['domains'], $domain)) {
                add_log("trace", "Found matching campaign for domain $domain: " . $campaign['id']);
                $campaign['settings'] = $settings;
                return $campaign;
            }
        }
        return false;
    }

    private function match_domain($domains, $domainToMatch): bool
    {
        foreach ($domains as $domain) {
            if ($domain === $domainToMatch) {
                return true;
            } elseif (strpos($domain, '*') !== false) {
                // Convert wildcard domain to a regex pattern
                $pattern = str_replace('.', '\.', $domain);
                $pattern = str_replace('*', '.*', $pattern);
                if (preg_match('/^' . $pattern . '$/', $domainToMatch)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function rename_campaign(int $id, string $name): bool
    {
        $query = "UPDATE campaigns SET name = :name WHERE id = :id";
        return $this->exec_write_query($query, [$name => SQLITE3_TEXT, $id => SQLITE3_INTEGER]);
    }

    public function save_campaign_settings(int $id, array $settings): bool
    {
        $query = "UPDATE campaigns SET settings = :settings WHERE id = :id";
        $settingsJson = json_encode($settings);
        return $this->exec_write_query($query, [$settingsJson => SQLITE3_TEXT, $id => SQLITE3_INTEGER]);
    }


    public function delete_campaign(int $id): bool
    {
        $query = "DELETE FROM campaigns WHERE id = :id";
        return $this->exec_write_query($query, [$id => SQLITE3_INTEGER]);
    }

    public function get_campaigns($startDate, $endDate, array $selectFields, array $filters = []): array
    {
        $bindList = [];
        $bindList[] = [$startDate, SQLITE3_INTEGER];
        $bindList[] = [$endDate, SQLITE3_INTEGER];

        $filterJoin = '';
        if (!empty($filters) && !empty($filters['rules']) && is_array($filters['rules'])) {
            $filterParts = [];
            foreach ($filters['rules'] as $rule) {
                $field = $rule['field'] ?? '';
                $op = $rule['operator'] ?? '';
                $value = $rule['value'] ?? '';

                $sqlField = self::resolveFilterField($field);
                if ($sqlField === null || !in_array($op, self::FILTER_OPERATORS)) continue;

                if (str_starts_with($sqlField, "json_extract(")) {
                    $sqlField = str_replace("json_extract(params,", "json_extract(c.params,", $sqlField);
                } else {
                    $sqlField = "c.$sqlField";
                }

                switch ($op) {
                    case '=':
                        $filterParts[] = "$sqlField = ?";
                        $bindList[] = [$value, SQLITE3_TEXT];
                        break;
                    case '!=':
                        $filterParts[] = "$sqlField != ?";
                        $bindList[] = [$value, SQLITE3_TEXT];
                        break;
                    case 'in':
                        $vals = is_array($value) ? $value : array_map('trim', explode(',', $value));
                        $filterParts[] = "$sqlField IN (" . implode(',', array_fill(0, count($vals), '?')) . ")";
                        foreach ($vals as $v) $bindList[] = [$v, SQLITE3_TEXT];
                        break;
                    case 'not_in':
                        $vals = is_array($value) ? $value : array_map('trim', explode(',', $value));
                        $filterParts[] = "$sqlField NOT IN (" . implode(',', array_fill(0, count($vals), '?')) . ")";
                        foreach ($vals as $v) $bindList[] = [$v, SQLITE3_TEXT];
                        break;
                    case 'is_null':
                        $filterParts[] = "($sqlField IS NULL OR $sqlField = '')";
                        break;
                    case 'is_not_null':
                        $filterParts[] = "($sqlField IS NOT NULL AND $sqlField != '')";
                        break;
                }
            }
            $condition = ($filters['condition'] ?? 'AND') === 'OR' ? ' OR ' : ' AND ';
            if (!empty($filterParts)) {
                $filterJoin = ' AND (' . implode($condition, $filterParts) . ')';
            }
        }

        $selectClause = implode(',', $this->get_stats_select_parts($selectFields));
        $query = "
        SELECT cmp.id, cmp.name, $selectClause
        FROM campaigns cmp
        LEFT JOIN clicks c ON c.campaign_id=cmp.id AND c.time BETWEEN ? AND ?$filterJoin
        GROUP BY cmp.id";

        $campaigns = $this->exec_bind_list_query($query, $bindList);
        foreach ($campaigns as &$campaign) {
            if (empty($campaign['settings']))
                continue;
            $campaign['settings'] = json_decode($campaign['settings'], true);
        }
        return $campaigns;
    }

    public function get_common_settings(): array
    {
        $query = "SELECT settings FROM common";

        $arr = $this->exec_read_query($query, [], true);
        $settings = json_decode($arr['settings'], true);
        if ($settings === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse common settings JSON: " . json_last_error_msg());
        }
        return $settings;
    }

    public function set_common_settings(array $s): bool
    {
        $query = "UPDATE common SET settings=:settings";
        $settingsJson = json_encode($s);
        if ($settingsJson === false) {
            throw new Exception("Failed to encode settings to JSON: " . json_last_error_msg());
        }
        return $this->exec_write_query($query, [$settingsJson => SQLITE3_TEXT]);
    }


    private function exec_write_query(string $query, array $p, bool $returnId = false): bool|int
    {
        try {
            $db = $this->open_db();
            $db->exec('BEGIN IMMEDIATE');
            $stmt = $db->prepare($query);

            if ($stmt === false) {
                throw new Exception("Error preparing $query: " . $db->lastErrorMsg());
            }

            $keys = array_keys($p);
            foreach ($keys as $index => $key) {
                $bound = $stmt->bindValue($index + 1, $key, $p[$key]);
                if ($bound === false) {
                    throw new Exception("Error binding $key to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();

            if ($result === false) {
                throw new Exception("Error executing $query: " . $db->lastErrorMsg());
            }

            $db->exec('COMMIT');
            add_log("trace", "Successfully executed $query");
            return $returnId ? $db->lastInsertRowID() : true;
        } catch (Exception $e) {
            $this->writeDb?->exec('ROLLBACK');
            add_log("errors", $e->getMessage());
            return false;
        }
    }

    private function exec_update_query(string $query, array $p): bool
    {
        try {
            $db = $this->open_db();
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Failed to prepare $query: " . $db->lastErrorMsg());
            }

            $keys = array_keys($p);
            foreach ($keys as $index => $key) {
                $bound = $stmt->bindValue($index + 1, $key, $p[$key]);
                if ($bound === false) {
                    throw new Exception("Failed to bind $key to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Failed to execute $query: " . $db->lastErrorMsg());
            }

            if ($db->changes() === 0) {
                add_log("errors", "No rows affected when $query");
                return false;
            }
            return true;
        } catch (Exception $e) {
            add_log("errors", $e->getMessage());
            return false;
        }
    }

    private function exec_bind_list_query(string $query, array $bindList, bool $firstOnly = false): array
    {
        try {
            $db = $this->open_db(true);
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Error preparing $query: " . $db->lastErrorMsg());
            }

            foreach ($bindList as $index => $pair) {
                $bound = $stmt->bindValue($index + 1, $pair[0], $pair[1]);
                if ($bound === false) {
                    throw new Exception("Error binding param " . ($index + 1) . " to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Error executing $query: " . $db->lastErrorMsg());
            }

            $arr = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $arr[] = $row;
            }
            return $firstOnly ? $arr[0] ?? [] : $arr;
        } catch (Exception $e) {
            add_error_log($e->getMessage());
            return [];
        }
    }

    private function exec_read_query(string $query, array $p, bool $firstOnly = false): array
    {
        try {
            $db = $this->open_db(true);
            $stmt = $db->prepare($query);
            if ($stmt === false) {
                throw new Exception("Error preparing $query: " . $db->lastErrorMsg());
            }

            $keys = array_keys($p);
            foreach ($keys as $index => $key) {
                $bound = $stmt->bindValue($index + 1, $key, $p[$key]);
                if ($bound === false) {
                    throw new Exception("Error binding $key to $query: " . $db->lastErrorMsg());
                }
            }

            $result = $stmt->execute();
            if ($result === false) {
                throw new Exception("Error executing $query: " . $db->lastErrorMsg());
            }

            $arr = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $arr[] = $row;
            }
            return $firstOnly ? $arr[0] ?? [] : $arr;
        } catch (Exception $e) {
            add_error_log($e->getMessage());
            return [];
        }
    }

}

$db = new Db();
