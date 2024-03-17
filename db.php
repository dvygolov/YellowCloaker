<?php
require_once __DIR__ . "/cookies.php";
require_once __DIR__ . "/logging.php";

class Db
{
    private $dbPath = __DIR__ . '/clicks.db';

    public function __construct()
    {
        if (!file_exists($this->dbPath)) {
            $this->create_new_db();
        }
    }


    public function get_white_clicks($startdate, $enddate, $config): array
    {
        // Prepare SQL query to select blocked clicks within the date range
        $query = "SELECT * FROM blocked WHERE time BETWEEN :startDate AND :endDate AND config = :config";

        $db = $this->open_db(true);
        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':startDate', $startdate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $enddate, SQLITE3_INTEGER);
        $stmt->bindValue(':config', $config, SQLITE3_TEXT);

        // Execute the query and fetch the results
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Get Blocked Clicks: $startdate $enddate $config $errorMessage");
            return [];
        }
        // Initialize an array to hold the results
        $blockedClicks = [];

        // Fetch each row and add it to the array
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['params'])) {
                $row['params'] = json_decode($row['params'], true);
            }
            $blockedClicks[] = $row;
        }
        $db->close();

        // Return the array of blocked clicks
        return $blockedClicks;
    }

    public function get_black_clicks($startdate, $enddate, $config): array
    {
        // Prepare SQL query to select blocked clicks within the date range
        $query = "SELECT id, time, ip, country, os, isp, ua, subid, preland, land, params FROM clicks WHERE time BETWEEN :startDate AND :endDate AND config = :config";

        $db = $this->open_db(true);
        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':startDate', $startdate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $enddate, SQLITE3_INTEGER);
        $stmt->bindValue(':config', $config, SQLITE3_TEXT);

        // Execute the query and fetch the results
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Get Black Clicks: $startdate $enddate $config $errorMessage");
            return [];
        }

        // Initialize an array to hold the results
        $clicks = [];

        // Fetch each row and add it to the array
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['params'])) {
                $row['params'] = json_decode($row['params'], true);
            }
            $clicks[] = $row;
        }

        $db->close();
        // Return the array of blocked clicks
        return $clicks;
    }

    public function get_clicks_by_subid($subid, $config): array
    {
        if (empty($subid)) {
            return [];
        }
        $query = "SELECT * FROM clicks WHERE subid = :subid AND config = :config";

        $db = $this->open_db(true);
        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':subid', $subid, SQLITE3_TEXT);
        $stmt->bindValue(':config', $config, SQLITE3_TEXT);

        // Execute the query
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $this->db->lastErrorMsg();
            add_log("errors", "Get Clicks By Subid: $subid, $config $errorMessage");
            return [];
        }

        $clicks = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['params'])) {
                $row['params'] = json_decode($row['params'], true);
            }
            $clicks[] = $row;
        }

        return $clicks;
    }

    public function get_leads($startdate, $enddate, $config): array
    {
        // Prepare SQL query to select leads within the date range and configuration
        $query = "SELECT * FROM clicks WHERE time BETWEEN :startDate AND :endDate AND config = :config AND status IS NOT NULL";

        // Prepare statement
        $stmt = $this->db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':startDate', $startdate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $enddate, SQLITE3_INTEGER);
        $stmt->bindValue(':config', $config, SQLITE3_TEXT);

        // Execute the query and fetch the results
        $result = $stmt->execute();
        if ($result === false) {
            $errorMessage = $this->db->lastErrorMsg();
            add_log("errors", "Get Leads: $startdate, $enddate, $config $errorMessage");
        }


        // Initialize an array to hold the results
        $leads = [];

        // Fetch each row and add it to the array
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $leads[] = $row;
        }

        // Return the array of leads
        return $leads;
    }

    public function getStatisticsData(
    $selectedFields,
    $groupByFields,
    $configName,
    $startDate,
    $endDate,
    $timezone
    ) {
        $baseQuery =
        "SELECT %s FROM clicks WHERE config = :configName AND time BETWEEN :startDate AND :endDate";
        $selectParts = [];
        $groupByParts = [];
        $orderByParts = [];

        // Process selected fields
        foreach ($selectedFields as $field) {
            switch ($field) {
                case 'clicks':
                    $selectParts[] = "COUNT(*) AS clicks";
                    break;
                case 'uniques':
                    $selectParts[] = "COUNT(DISTINCT subid) AS uniques";
                    break;
                case 'uniques_ratio':
                    $selectParts[] = "(COUNT(DISTINCT subid)*1.0/COUNT(*) * 100.0) AS uniques_ratio";
                    break;
                case 'conversion':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN id END) AS conversion";
                    break;
                case 'purchase':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN id END) AS purchase";
                    break;
                case 'hold':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Lead' THEN id END) AS hold";
                    break;
                case 'reject':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Reject' THEN id END) AS reject";
                    break;
                case 'trash':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN status = 'Trash' THEN id END) AS trash";
                    break;
                case 'lpclicks':
                    $selectParts[] = "COUNT(DISTINCT CASE WHEN lpclick = 1 THEN id END) AS lpclicks";
                    break;
                case 'lpctr':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN lpclick = 1 THEN id END) * 100.0 / COUNT(*)) AS lpctr";
                    break;
                case 'cra':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN id END) * 100.0 / COUNT(*)) AS cra";
                    break;
                case 'crs':
                    $selectParts[] = "(COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN id END) * 100.0 / COUNT(*)) AS crs";
                    break;
                case 'appt':
                    $selectParts[] = "CASE
                            WHEN COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN id END) = 0
                                 OR (COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN id END) - COUNT(DISTINCT CASE WHEN status = 'Trash' THEN id END)) = 0
                            THEN 0
                            ELSE (COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN id END) * 100.0 / (COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN id END) - COUNT(DISTINCT CASE WHEN status = 'Trash' THEN id END)))
                       END AS appt";
                    break;
                case 'app':
                    $selectParts[] = "CASE
                            WHEN COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN id END) = 0
                                 OR COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN id END) = 0
                            THEN 0
                            ELSE (COUNT(DISTINCT CASE WHEN status = 'Purchase' THEN id END) * 100.0 / COUNT(DISTINCT CASE WHEN status IS NOT NULL THEN id END))
                       END AS app";
                    break;
                case 'epc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(id)) AS epc";
                    break;
                case 'epuc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(DISTINCT(subid))) AS epc";
                    break;
                case 'revenue':
                    $selectParts[] = "SUM(payout) AS revenue";
                    break;
            }
        }

        // Process group by fields
        foreach ($groupByFields as $field) {
            if ($field === 'date') {
                $selectParts[] = "strftime('%Y-%m-%d', datetime(time, 'unixepoch')) AS date";
                $groupByParts[] = "date";
                $orderByParts[] = "date";
            } elseif (in_array($field, ['preland', 'land', 'isp', 'country', 'os'])) {
                $selectParts[] = $field;
                $groupByParts[] = $field;
                $orderByParts[] = $field;
            } else {
                // JSON fields
                $jsonExtract = "COALESCE(json_extract(params, '$." . $field . "'), 'unknown') AS " . $field;
                $selectParts[] = $jsonExtract;
                $groupByParts[] = $field;
                $orderByParts[] = $field;
            }
        }

        // Construct the SQL query
        $selectClause = implode(', ', $selectParts);
        $groupByClause = !empty($groupByParts) ? "GROUP BY " . implode(', ', $groupByParts) : '';
        $orderByClause = !empty($orderByParts) ? "ORDER BY " . implode(', ', $orderByParts) : '';
        $sqlQuery = sprintf($baseQuery, $selectClause) . " " . $groupByClause . " " . $orderByClause;

        // Prepare and execute the query
        $stmt = $this->db->prepare($sqlQuery);
        if ($stmt === false) {
            // Prepare failed, get and display the error message
            $errorMessage = $this->db->lastErrorMsg();
            add_log("errors", "Error preparing statistics statement: $errorMessage");
            return [];
        }
        $stmt->bindValue(':configName', $configName, SQLITE3_TEXT);
        $stmt->bindValue(':startDate', $startDate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $endDate, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if ($result === false) {
            // Prepare failed, get and display the error message
            $errorMessage = $this->db->lastErrorMsg();
            add_log("errors", "Error executing statistics statement: $errorMessage");
            return [];
        }

        $treeData = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->addRow($treeData, $row, $selectedFields, $groupByFields);
        }

        //$this->countTotals($treeData, $newGroupIndex, $selectedFields, $groupByFields);
        return $treeData;
    }

    private function addRow(&$treeData, $row, $columns, $groupBy)
    {
        $children = &$treeData;
        $i = 0;
        while ($i < count($groupBy)) {
            $curGroup = $groupBy[$i];
            $lastChild = count($children) === 0 ? null : $children[count($children) - 1];
            //if row will be the first child or
            //if new row has group value that differs from the last child's group value
            if ($lastChild === null || $lastChild['group'] !== $row[$curGroup]) {

                if (count($children) !== 0) {
                    //count totals for previous levels
                    $j = $i;
                    $totChildren = &$children;
                    $totParents = [];
                    while ($j < count($groupBy) - 1) {
                        $parent = &$totChildren[count($totChildren) - 1];
                        $totChildren = &$parent['_children'];
                        $totParents[] = &$parent;
                        $j++;
                    }
                    while ($j > $i) {
                        $parent = array_pop($totParents);
                        $totals = $this->countTotals($totChildren, $columns);
                        $parent = array_merge($parent, $totals);
                        $j--;
                    }
                }

                $children[] = ['group' => $row[$curGroup], '_children' => []];
                $lastChild = &$children[count($children) - 1];

                unset($row[$curGroup]); //current group became a new level, we should remove it from row

                //if we are at the last group by level - we need to add all the row data here
                if ($i === count($groupBy) - 1) {
                    unset($lastChild['_children']); //child-free node! it will have 'group' only
                    $lastChild = array_merge($lastChild, $row);
                }
            }
            $children = &$lastChild['_children'];
            $i++;
        }
    }
    private function countTotals(array $children, array $columns)
    {
        // If we have only one child row
        if (count($children) === 1) {
            // Filter the array to include only keys present in the $columns array
            $filtered = array_intersect_key($children[0], array_flip($columns));
            return $filtered;
        }

        //if we have many children - sum their values
        $sumArray = [];
        foreach ($children as $child) {
            // Iterate over each key-value pair in the current array
            foreach ($child as $key => $value) {
                if (in_array($key, ['_children', 'group', 'uniques_ratio', 'lpctr', 'cra', 'crs', 'appt', 'app', 'epc', 'epuc']))
                    continue;
                if (!isset($sumArray[$key])) {
                    $sumArray[$key] = 0;
                }
                $sumArray[$key] += $value;
            }
        }

        if (in_array('uniques_ratio', $columns))
            $sumArray['uniques_ratio'] =
            $sumArray['clicks'] === 0 ? 0 : $sumArray['uniques'] * 1.0 / $sumArray['clicks'] * 100;
        if (in_array('lpctr', $columns))
            $sumArray['lpctr'] =
            $sumArray['clicks'] === 0 ? 0 : $sumArray['lpclicks'] * 1.0 / $sumArray['clicks'] * 100.0;
        if (in_array('cra', $columns))
            $sumArray['cra'] =
            $sumArray['clicks'] === 0 ? 0 : $sumArray['conversion'] * 1.0 / $sumArray['clicks'] * 100.0;
        if (in_array('crs', $columns))
            $sumArray['crs'] =
            $sumArray['clicks'] === 0 ? 0 : $sumArray['purchase'] * 1.0 / $sumArray['clicks'] * 100.0;
        if (in_array('appt', $columns))
            $sumArray['appt'] = $sumArray['conversion'] - $sumArray['trash'] === 0 ? 0 :
            $sumArray['purchase'] * 1.0 / ($sumArray['conversion'] - $sumArray['trash']) * 100.0;
        if (in_array('app', $columns))
            $sumArray['app'] =
            $sumArray['conversion'] === 0 ? 0 : $sumArray['purchase'] * 1.0 / $sumArray['conversion'] * 100.0;
        if (in_array('epc', $columns))
            $sumArray['epc'] = $sumArray['clicks'] === 0 ? 0 : $sumArray['revenue'] * 1.0 / $sumArray['clicks'];
        if (in_array('epuc', $columns))
            $sumArray['epuc'] = $sumArray['uniques'] === 0 ? 0 : $sumArray['revenue'] * 1.0 / $sumArray['uniques'] * 100;
        return $sumArray;
    }

    public function add_white_click($data, $reason, $config)
    {
        // Prepare click data
        $click = $this->prepare_click_data($data, $reason, $config);

        // Prepare SQL insert statement
        $query = "INSERT INTO blocked (time, ip, country, os, isp, ua, reason, params, config) VALUES (:time, :ip, :country, :os, :isp, :ua, :reason, :params, :config)";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        // Bind parameters
        foreach ($click as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $result = $stmt->execute();
        if ($result === false) {
            // Prepare failed, get and display the error message
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't add white click: $errorMessage:" . json_encode($click));
        }
        $db->close();
    }

    public function add_black_click($subid, $data, $preland, $land, $config)
    {
        // Prepare click data with the provided data and configuration
        $click = $this->prepare_click_data($data, '', $config); // Assuming '' is used as a placeholder for 'reason'
        $click['subid'] = $subid;
        $click['preland'] = empty($preland) ? 'unknown' : $preland;
        $click['land'] = empty($land) ? 'unknown' : $land;

        // Prepare the SQL INSERT statement for the 'clicks' table
        $query = "INSERT INTO clicks (config, time, ip, country, os, isp, ua, subid, preland, land, params, lpclick, status) VALUES (:config, :time, :ip, :country, :os, :isp, :ua, :subid, :preland, :land, :params, 0, NULL)";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        // Bind parameters from the $click array to the prepared statement
        foreach ($click as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        // Manually bind the lpclick and status parameters
        // Assuming lpclick is false and status is NULL for a new "black" click
        $stmt->bindValue(':lpclick', 0, SQLITE3_INTEGER); // lpclick set to false (0)
        $stmt->bindValue(':status', NULL, SQLITE3_NULL);

        $result = $stmt->execute();
        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't add black click: $errorMessage:" . json_encode($click));
        }
        $db->close();
    }

    public function add_lead($subid, $name, $phone, $status = 'Lead')
    {
        $updateQuery = "UPDATE clicks SET status = :status, name = :name, phone = :phone WHERE id = (SELECT id FROM clicks WHERE subid = :subid ORDER BY time DESC LIMIT 1)";

        $db = $this->open_db();
        $stmt = $db->prepare($updateQuery);
        $stmt->bindValue(':subid', $subid, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);

        $result = $stmt->execute();
        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't add lead: $errorMessage: $subid, $name, $phone, $status");
        }
        $db->close();
    }

    public function update_status($subid, $status, $payout): bool
    {
        if (!$this->subid_exists($subid))
            return false;

        $updateQuery = "UPDATE clicks SET status = :status, payout = :payout WHERE id = (SELECT id FROM clicks WHERE subid = :subid ORDER BY time DESC LIMIT 1)";

        $db = $this->open_db();
        $stmt = $db->prepare($updateQuery);
        $stmt->bindValue(':subid', $subid, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':payout', $payout, SQLITE3_FLOAT);

        $result = $stmt->execute();
        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't update status: $errorMessage: $subid, $status, $payout");
        }
        $db->close();
        return $result === false ? false : true;
    }

    public function add_lpctr($subid): bool
    {
        $updateQuery = "UPDATE clicks SET lpclick = 1 WHERE id = (SELECT id FROM clicks WHERE subid = :subid ORDER BY time DESC LIMIT 1)";

        $db = $this->open_db();
        $stmt = $db->prepare($updateQuery);
        $stmt->bindValue(':subid', $subid, SQLITE3_TEXT);
        $result = $stmt->execute();
        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't update lpctr: $errorMessage: $subid");
        }
        $db->close();
        return $result === false ? false : true;
    }

    private function subid_exists($subid): bool
    {
        $db = $this->open_db(true);
        $stmt = $db->prepare('SELECT COUNT(*) AS count FROM clicks WHERE subid = :subid');
        $stmt->bindParam(':subid', $subid);
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't check is subid exists: $errorMessage: $subid");
            die("Couldn't check is subid exists: $errorMessage: $subid");
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return ($row['count'] > 0);
    }

    private function prepare_click_data($data, $reason, $config)
    {
        $queryarr = [];
        if (!empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryarr);
        }

        return [
        "time" => (new DateTime())->getTimestamp(),
        "ip" => $data['ip'],
        "country" => $data['country'],
        "os" => $data['os'],
        "isp" => str_replace(',', ' ', $data['isp']),
        "ua" => str_replace(',', ' ', $data['ua']),
        "reason" => $reason,
        "params" => json_encode($queryarr),
        "config" => $config
        ];
    }

    private function open_db(bool $readOnly = false): SQLite3
    {
        $db = new SQLite3($this->dbPath, $readOnly ? SQLITE3_OPEN_READONLY : SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);
        return $db;
    }

    private function create_new_db()
    {
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS clicks (
                id INTEGER PRIMARY KEY,
                config TEXT NOT NULL,
                time INTEGER NOT NULL,
                ip TEXT NOT NULL,
                country TEXT NOT NULL,
                os TEXT NOT NULL,
                isp TEXT NOT NULL,
                ua TEXT NOT NULL,
                subid TEXT NOT NULL,
                preland TEXT,
                land TEXT,
                params TEXT,
                leaddata TEXT,
                lpclick BOOLEAN,
                status TEXT,
                payout NUMERIC DEFAULT 0
            );
            CREATE INDEX IF NOT EXISTS idx_config ON clicks (config);
            CREATE INDEX IF NOT EXISTS idx_subid ON clicks (subid);
            CREATE INDEX IF NOT EXISTS idx_time ON clicks (time);
            CREATE INDEX IF NOT EXISTS idx_date ON clicks (date(time, 'unixepoch'));

            CREATE TABLE IF NOT EXISTS blocked (
                id INTEGER PRIMARY KEY,
                config TEXT NOT NULL,
                time INTEGER,
                ip TEXT NOT NULL,
                country TEXT,
                os TEXT,
                isp TEXT,
                ua TEXT,
                params TEXT,
                reason TEXT
            );
            CREATE INDEX IF NOT EXISTS idx_bconfig ON blocked (config);
            CREATE INDEX IF NOT EXISTS idx_btime ON blocked (time);
            PRAGMA journal_mode = wal;
            ";
        $db = $this->open_db();
        $db->exec($createTableSQL);
        $db->close();
    }
}
