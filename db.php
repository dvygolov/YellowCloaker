<?php
require_once __DIR__ . "/cookies.php";
require_once __DIR__ . "/logging.php";
require_once __DIR__ . "/settings.php";
class Db
{
    private $dbPath;

    public function __construct()
    {
        global $cloSettings;
        $this->dbPath=  __DIR__ . '/'.$cloSettings['dbFileName'];
        if (!file_exists($this->dbPath)) {
            $this->create_new_db();
        }
    }


    public function get_white_clicks($startdate, $enddate, $campId): array
    {
        // Prepare SQL query to select blocked clicks within the date range
        $query = "SELECT * FROM blocked WHERE time BETWEEN :startDate AND :endDate AND campaign_id = :campid ORDER BY time DESC";

        $db = $this->open_db(true);
        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':startDate', $startdate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $enddate, SQLITE3_INTEGER);
        $stmt->bindValue(':campid', $campId, SQLITE3_INTEGER);

        // Execute the query and fetch the results
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Get Blocked Clicks: $startdate $enddate $campId $errorMessage");
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

    public function get_black_clicks($startdate, $enddate, $campId): array
    {
        // Prepare SQL query to select blocked clicks within the date range
        $query = "SELECT * FROM clicks WHERE time BETWEEN :startDate AND :endDate AND campaign_id = :campid ORDER BY time DESC";

        $db = $this->open_db(true);
        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':startDate', $startdate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $enddate, SQLITE3_INTEGER);
        $stmt->bindValue(':campid', $campId, SQLITE3_INTEGER);

        // Execute the query and fetch the results
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Get Black Clicks: $startdate $enddate $campId $errorMessage");
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
        return $clicks;
    }

    public function get_clicks_by_subid($subid, $firstOnly = false): array
    {
        if (empty($subid)) {
            return [];
        }
        $query = "SELECT * FROM clicks WHERE subid = :subid ORDER BY time DESC";
        if ($firstOnly)
            $query .= " LIMIT 1";

        $db = $this->open_db(true);
        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':subid', $subid, SQLITE3_TEXT);

        // Execute the query
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Get Clicks By Subid: $subid, $errorMessage");
            $db->close();
            return [];
        }

        $clicks = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['params'])) {
                $row['params'] = json_decode($row['params'], true);
            }
            $clicks[] = $row;
        }

        $db->close();
        return $clicks;
    }

    public function get_leads($startdate, $enddate, $campId): array
    {
        // Prepare SQL query to select leads within the date range and configuration
        $query = "SELECT * FROM clicks WHERE time BETWEEN :startDate AND :endDate AND campaign_id = :campid AND status IS NOT NULL ORDER BY time DESC";

        $db = $this->open_db();

        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters to the prepared statement
        $stmt->bindValue(':startDate', $startdate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $enddate, SQLITE3_INTEGER);
        $stmt->bindValue(':campid', $campId, SQLITE3_INTEGER);

        // Execute the query and fetch the results
        $result = $stmt->execute();
        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Get Leads: $startdate, $enddate, $campId $errorMessage");
            $db->close();
            return [];
        }

        // Initialize an array to hold the results
        $leads = [];

        // Fetch each row and add it to the array
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $leads[] = $row;
        }

        $db->close();
        return $leads;
    }

    public function get_statistics(
        $selectedFields,
        $groupByFields,
        $campId,
        $startDate,
        $endDate,
        $timezone
    ) {
        $baseQuery =
        "SELECT %s FROM clicks WHERE campaign_id = :campid AND time BETWEEN :startDate AND :endDate";
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
                case 'cpc':
                    $selectParts[] = "(SUM(cost) * 1.0 / COUNT(id)) AS cpc";
                    break;
                case 'costs':
                    $selectParts[] = "SUM(cost) AS costs";
                    break;
                case 'epc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(id)) AS epc";
                    break;
                case 'epuc':
                    $selectParts[] = "(SUM(payout) * 1.0 / COUNT(DISTINCT(subid))) AS epuc";
                    break;
                case 'revenue':
                    $selectParts[] = "SUM(payout) AS revenue";
                    break;
                case 'profit':
                    $selectParts[] = "(SUM(payout) - SUM(cost)) as profit";
                    break;
                case 'roi':
                    $selectParts[] = "((SUM(payout) - SUM(cost))*1.0 / SUM(cost) * 100.0) as roi";
                    break;
            }
        }

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
            } elseif (in_array($field, ['preland', 'land', 'isp', 'country', 'lang', 'os'])) {
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

        $db = $this->open_db(true);
        // Prepare and execute the query
        $stmt = $db->prepare($sqlQuery);
        if ($stmt === false) {
            // Prepare failed, get and display the error message
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Error preparing statistics statement: $errorMessage");
            $db->close();
            return [];
        }
        $stmt->bindValue(':campid', $campId, SQLITE3_INTEGER);
        $stmt->bindValue(':startDate', $startDate, SQLITE3_INTEGER);
        $stmt->bindValue(':endDate', $endDate, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if ($result === false) {
            // Prepare failed, get and display the error message
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Error executing statistics statement: $errorMessage");
            $db->close();
            return [];
        }

        $treeData = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->add_row($treeData, $row, $selectedFields, $groupByFields);
        }

        //$this->countTotals($treeData, $newGroupIndex, $selectedFields, $groupByFields);
        $db->close();
        return $treeData;
    }
    //TODO: totals correct counting
    private function add_row(&$treeData, $row, $columns, $groupBy)
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
                        $totals = $this->count_totals($totChildren, $columns);
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
    private function count_totals(array $children, array $columns)
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
                if (
                in_array($key, [
                '_children',
                'group',
                'uniques_ratio',
                'lpctr',
                'cra',
                'crs',
                'appt',
                'app',
                'cpc',
                'epc',
                'epuc'
                ])
                )
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

        if (in_array('cpc', $columns))
            $sumArray['cpc'] = $sumArray['clicks'] === 0 ? 0 : $sumArray['costs'] * 1.0 / $sumArray['clicks'];
        if (in_array('epc', $columns))
            $sumArray['epc'] = $sumArray['clicks'] === 0 ? 0 : $sumArray['revenue'] * 1.0 / $sumArray['clicks'];
        if (in_array('epuc', $columns))
            $sumArray['epuc'] = $sumArray['uniques'] === 0 ? 0 : $sumArray['revenue'] * 1.0 / $sumArray['uniques'] * 100;

        return $sumArray;
    }

    public function add_white_click($data, $reason, $campId)
    {
        // Prepare click data
        $click = $this->prepare_click_data($data, $campId);
        $click['reason'] = $reason;

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

    public function add_black_click($subid, $data, $preland, $land, $campId)
    {
        // Prepare click data with the provided data and configuration
        $click = $this->prepare_click_data($data, $campId);
        $click['subid'] = $subid;
        $click['preland'] = empty($preland) ? 'unknown' : $preland;
        $click['land'] = empty($land) ? 'unknown' : $land;

        // Prepare the SQL INSERT statement for the 'clicks' table
        $query = "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, client, clientver, device, brand, model, isp, ua, subid, preland, land, params, cost, lpclick, status) VALUES (:campid, :time, :ip, :country, :lang, :os, :osver, :client, :clientver, :device, :brand, :model, :isp, :ua, :subid, :preland, :land, :params, :cpc, 0, NULL)";

        $db = $this->open_db();
        $stmt = $db->prepare($query);
        if ($stmt === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't prepare black click: $errorMessage:" . json_encode($click));
        }

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

    private function prepare_click_data($data, $campId): array
    {
        $data["time"] = (new DateTime())->getTimestamp();
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

    public function add_campaign($name)
    {
        $query = "INSERT INTO campaigns (name, settings) VALUES (:name, :settings)";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        $settingsJson = file_get_contents(__DIR__.'/default.json');

        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':settings', $settingsJson, SQLITE3_TEXT);

        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't add campaign $name: $errorMessage");
            $db->close();
            return false;
        }

        $newCampaignId = $db->lastInsertRowID();
        $db->close();
        return $newCampaignId;
    }

    public function clone_campaign($id)
    {
        // SQL query to clone campaign using a single command
        $query = "INSERT INTO campaigns (name, settings)
                  SELECT name || ' (Clone)', settings FROM campaigns WHERE id = :id";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        // Bind the original campaign ID to the query
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't clone campaign $id: $errorMessage");
            $db->close();
            return false;
        }

        $newCampaignId = $db->lastInsertRowID();
        $db->close();
        return $newCampaignId;
    }

    public function get_campaign_settings($id)
    {
        $query = "SELECT settings FROM campaigns WHERE id = :id";

        $db = $this->open_db(true);
        $stmt = $db->prepare($query);

        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't fetch campaign $id: $errorMessage");
            $db->close();
            return [];
        }

        $arr = $result->fetchArray(SQLITE3_ASSOC);
        $db->close();

        $settings = json_decode($arr['settings'], true);

        return $settings;
    }

    public function get_campaign_by_domain($domain)
    {
        $query = "SELECT * FROM campaigns";

        $db = $this->open_db(true);
        $stmt = $db->prepare($query);
        if ($stmt === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't prepare fetch campaigns query for domain $domain: $errorMessage");
            $db->close();
            return null;
        }
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't fetch campaigns for domain $domain: $errorMessage");
            $db->close();
            return null;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['settings'])) {
                $settings = json_decode($row['settings'], true);
                if (isset($settings['domains']) && $this->match_domain($settings['domains'], $domain)) {
                    $db->close();
                    $row['settings'] = json_decode($row['settings'], true);
                    return $row;
                }
            }
        }

        $db->close();
        return null;
    }

    private function match_domain($domains, $domainToMatch)
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

    public function rename_campaign($id, $name)
    {
        $query = "UPDATE campaigns SET name = :name WHERE id = :id";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);

        $result = $stmt->execute();
        $db->close();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't rename campaign $id to $name: $errorMessage");
            return false;
        }
        return true;
    }

    public function save_campaign_settings($id, $settings)
    {
        $query = "UPDATE campaigns SET settings = :settings WHERE id = :id";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        $settingsJson = json_encode($settings);

        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':settings', $settingsJson, SQLITE3_TEXT);

        $result = $stmt->execute();
        $db->close();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't save campaign's $id settings: $errorMessage, $settingsJson");
            return false;
        }
        return true;
    }

    public function delete_campaign($id)
    {
        $query = "DELETE FROM campaigns WHERE id = :id";

        $db = $this->open_db();
        $stmt = $db->prepare($query);

        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $db->close();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't delete campaign $id: $errorMessage");
            return false;
        }
        return true;
    }

    public function get_campaigns()
    {
        $query = "SELECT id,name FROM campaigns";

        $db = $this->open_db(true);
        $stmt = $db->prepare($query);
        $result = $stmt->execute();

        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            add_log("errors", "Couldn't fetch campaigns: $errorMessage");
            $db->close();
            return [];
        }

        $campaigns = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!empty($row['settings'])) {
                $row['settings'] = json_decode($row['settings'], true);
            }
            $campaigns[] = $row;
        }
        $db->close();

        return $campaigns;
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
            CREATE TABLE campaigns
            (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                settings TEXT NOT NULL
            );

            CREATE TABLE IF NOT EXISTS clicks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_id INTEGER,
                time INTEGER NOT NULL,
                ip TEXT NOT NULL,
                country TEXT NOT NULL,
                lang TEXT NOT NULL,
                os TEXT NOT NULL,
                osver TEXT NOT NULL,
                device TEXT NOT NULL,
                brand TEXT NOT NULL,
                model TEXT NOT NULL,
                isp TEXT NOT NULL,
                client TEXT NOT NULL,
                clientver TEXT NOT NULL,
                ua TEXT NOT NULL,
                subid TEXT NOT NULL,
                preland TEXT,
                land TEXT,
                params TEXT,
                leaddata TEXT,
                lpclick BOOLEAN,
                status TEXT,
                cost NUMERIC DEFAULT 0,
                payout NUMERIC DEFAULT 0,
                FOREIGN KEY (campaign_id)  REFERENCES campaigns (id) ON DELETE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_subid ON clicks (subid);
            CREATE INDEX IF NOT EXISTS idx_time ON clicks (time);
            CREATE INDEX IF NOT EXISTS idx_date ON clicks (date(time, 'unixepoch'));
            CREATE INDEX IF NOT EXISTS idx_country ON clicks (country);
            CREATE INDEX IF NOT EXISTS idx_lang ON clicks (lang);
            CREATE INDEX IF NOT EXISTS idx_lang ON clicks (client);
            CREATE INDEX IF NOT EXISTS idx_lang ON clicks (clientver);
            CREATE INDEX IF NOT EXISTS idx_lang ON clicks (device);
            CREATE INDEX IF NOT EXISTS idx_lang ON clicks (brand);
            CREATE INDEX IF NOT EXISTS idx_lang ON clicks (model);
            CREATE INDEX IF NOT EXISTS idx_os ON clicks (os);
            CREATE INDEX IF NOT EXISTS idx_os ON clicks (osver);
            CREATE INDEX IF NOT EXISTS idx_isp ON clicks (isp);


            CREATE TABLE IF NOT EXISTS blocked (
                id INTEGER PRIMARY KEY,
                campaign_id INTEGER,
                time INTEGER NOT NULL,
                ip TEXT NOT NULL,
                country TEXT,
                lang TEXT,
                os TEXT,
                osver TEXT,
                device TEXT,
                brand TEXT,
                model TEXT,
                isp TEXT,
                client TEXT,
                clientver TEXT,
                ua TEXT,
                params TEXT,
                reason TEXT,
                FOREIGN KEY (campaign_id)  REFERENCES campaigns (id) ON DELETE CASCADE
            );
            CREATE INDEX IF NOT EXISTS idx_btime ON blocked (time);
            PRAGMA journal_mode = wal;
            ";
        $db = new SQLite3($this->dbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5000);
        $result = $db->exec($createTableSQL);
        if ($result === false) {
            $errorMessage = $db->lastErrorMsg();
            die("Can't create DB: $errorMessage");
        }
        $db->close();
    }
}
