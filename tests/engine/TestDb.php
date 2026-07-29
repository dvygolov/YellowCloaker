<?php

/**
 * Testable Db subclass that uses a temporary SQLite file.
 * Overrides the constructor to skip require_once calls and use a temp DB path.
 */
class TestDb extends Db
{
    private string $testDbPath;

    public function __construct(string $dbPath)
    {
        $this->testDbPath = $dbPath;

        // Use reflection to set the private dbPath without calling parent constructor
        $ref = new ReflectionClass(Db::class);
        $prop = $ref->getProperty('dbPath');
        $prop->setAccessible(true);
        $prop->setValue($this, $dbPath);
    }

    public function initSchema(): void
    {
        $schemaSQL = file_get_contents(__DIR__ . '/../../code/db/db.sql');
        $db = new SQLite3($this->testDbPath, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
        $db->exec($schemaSQL);
        // Insert common settings (required by schema)
        $db->exec("INSERT INTO common (settings) VALUES ('{}')");
        $db->close();
    }

    public function seedClicks(array $clicks): void
    {
        $db = new SQLite3($this->testDbPath, SQLITE3_OPEN_READWRITE);
        foreach ($clicks as $c) {
            $pathRaw = $c['path'] ?? '[]';
            $path = is_array($pathRaw) ? $pathRaw : (json_decode((string)$pathRaw, true) ?: []);
            $step = (int)($c['step'] ?? 0);

            $stmt = $db->prepare(
                "INSERT INTO clicks (campaign_id, time, ip, country, lang, os, osver, client, clientver, device, brand, model, isp, ua, userid, unique_hash, unique_flags, clickid, flow, path, step, params, cost, payout, status)
                 VALUES (:campaign_id, :time, :ip, :country, :lang, :os, :osver, :client, :clientver, :device, :brand, :model, :isp, :ua, :userid, :unique_hash, :unique_flags, :clickid, :flow, :path, :step, :params, :cost, :payout, :status)"
            );
            $campaignId = $c['campaign_id'] ?? 1;
            $userid = $c['userid'] ?? $c['subid'] ?? 'u1';
            $flow = $c['flow'] ?? 'Flow 1';
            $stmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
            $stmt->bindValue(':time', $c['time'] ?? 1700000000, SQLITE3_INTEGER);
            $stmt->bindValue(':ip', $c['ip'] ?? '1.2.3.4', SQLITE3_TEXT);
            $stmt->bindValue(':country', $c['country'] ?? 'US', SQLITE3_TEXT);
            $stmt->bindValue(':lang', $c['lang'] ?? 'en', SQLITE3_TEXT);
            $stmt->bindValue(':os', $c['os'] ?? 'Windows', SQLITE3_TEXT);
            $stmt->bindValue(':osver', $c['osver'] ?? 10.0, SQLITE3_FLOAT);
            $stmt->bindValue(':client', $c['client'] ?? 'Chrome', SQLITE3_TEXT);
            $stmt->bindValue(':clientver', $c['clientver'] ?? 120.0, SQLITE3_FLOAT);
            $stmt->bindValue(':device', $c['device'] ?? 'desktop', SQLITE3_TEXT);
            $stmt->bindValue(':brand', $c['brand'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(':model', $c['model'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(':isp', $c['isp'] ?? 'Comcast', SQLITE3_TEXT);
            $stmt->bindValue(':ua', $c['ua'] ?? 'Mozilla/5.0', SQLITE3_TEXT);
            $stmt->bindValue(':userid', $userid, SQLITE3_TEXT);
            $uniqueHash = $c['unique_hash'] ?? null;
            $stmt->bindValue(':unique_hash', $uniqueHash, $uniqueHash === null ? SQLITE3_NULL : SQLITE3_BLOB);
            if (array_key_exists('unique_flags', $c)) {
                $uniqueFlags = $c['unique_flags'];
            } else {
                $campaignStmt = $db->prepare('SELECT 1 FROM clicks WHERE campaign_id = :campaign_id AND userid = :userid LIMIT 1');
                $campaignStmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
                $campaignStmt->bindValue(':userid', $userid, SQLITE3_TEXT);
                $campaignUnique = $campaignStmt->execute()->fetchArray(SQLITE3_NUM) === false;
                $flowStmt = $db->prepare('SELECT 1 FROM clicks WHERE campaign_id = :campaign_id AND flow = :flow AND userid = :userid LIMIT 1');
                $flowStmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
                $flowStmt->bindValue(':flow', $flow, SQLITE3_TEXT);
                $flowStmt->bindValue(':userid', $userid, SQLITE3_TEXT);
                $flowUnique = $flowStmt->execute()->fetchArray(SQLITE3_NUM) === false;
                $uniqueFlags = ($campaignUnique ? 2 : 0) | ($flowUnique ? 1 : 0);
            }
            $stmt->bindValue(':unique_flags', $uniqueFlags, $uniqueFlags === null ? SQLITE3_NULL : SQLITE3_INTEGER);
            $clickid = $c['clickid'] ?? $c['subid'] ?? 'c1';
            $stmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $stmt->bindValue(':flow', $flow, SQLITE3_TEXT);
            $stmt->bindValue(':path', json_encode($path), SQLITE3_TEXT);
            $stmt->bindValue(':step', $step, SQLITE3_INTEGER);
            $stmt->bindValue(':params', $c['params'] ?? '{}', SQLITE3_TEXT);
            $stmt->bindValue(':cost', $c['cost'] ?? 0, SQLITE3_FLOAT);
            $stmt->bindValue(':payout', $c['payout'] ?? 0, SQLITE3_FLOAT);
            $stmt->bindValue(':status', $c['status'] ?? null, SQLITE3_TEXT);
            $stmt->execute();

            $status = trim((string)($c['status'] ?? ''));
            if ($status !== '') {
                $conversionStmt = $db->prepare(
                    "INSERT INTO conversions (clickid, campaign_id, flow, step, time, status, raw_status, source, tid, payout, currency, is_initial, changes_status, status_occurrence)
                     VALUES (:clickid, :campaign_id, :flow, :step, :time, :status, :raw_status, 'postback', NULL, :payout, 'USD', 1, 1, 1)"
                );
                $conversionStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
                $conversionStmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
                $conversionStmt->bindValue(':flow', $flow, SQLITE3_TEXT);
                $conversionStmt->bindValue(':step', $step, SQLITE3_INTEGER);
                $conversionStmt->bindValue(':time', $c['conversion_time'] ?? $c['time'] ?? 1700000000, SQLITE3_INTEGER);
                $conversionStmt->bindValue(':status', $status, SQLITE3_TEXT);
                $conversionStmt->bindValue(':raw_status', $c['raw_status'] ?? $status, SQLITE3_TEXT);
                $conversionStmt->bindValue(':payout', $c['payout'] ?? 0, SQLITE3_FLOAT);
                $conversionStmt->execute();
            }

            $maxStep = min($step, max(0, count($path) - 1));
            for ($si = 0; $si <= $maxStep; $si++) {
                $variant = $path[$si] ?? '';
                if (!is_string($variant) || $variant === '') {
                    continue;
                }
                $stepStmt = $db->prepare(
                    "INSERT OR IGNORE INTO click_steps (clickid, step, variant, time, mvt, events) "
                    . "VALUES (:clickid, :step, :variant, :time, :mvt, :events)"
                );
                $stepStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
                $stepStmt->bindValue(':step', $si, SQLITE3_INTEGER);
                $stepStmt->bindValue(':variant', $variant, SQLITE3_TEXT);
                $stepStmt->bindValue(':time', $c['time'] ?? 1700000000, SQLITE3_INTEGER);
                $stepMvt = is_array($c['step_mvt'][$si] ?? null) ? $c['step_mvt'][$si] : [];
                $stepStmt->bindValue(':mvt', json_encode((object)$stepMvt), SQLITE3_TEXT);
                $stepEvents = is_array($c['step_events'][$si] ?? null) ? $c['step_events'][$si] : [];
                $stepStmt->bindValue(':events', $stepEvents === [] ? '{}' : json_encode($stepEvents), SQLITE3_TEXT);
                $stepStmt->execute();
            }
        }
        $db->close();
    }

    public function seedCampaign(int $id = 1, string $name = 'Test', array $settings = []): void
    {
        $db = new SQLite3($this->testDbPath, SQLITE3_OPEN_READWRITE);
        $statement = $db->prepare(
            'INSERT INTO campaigns (id, name, settings) VALUES (:id, :name, :settings)'
        );
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $statement->bindValue(':name', $name, SQLITE3_TEXT);
        $statement->bindValue(':settings', json_encode($settings), SQLITE3_TEXT);
        $statement->execute();
        $db->close();
    }

    public function seedConversions(array $conversions): void
    {
        $db = new SQLite3($this->testDbPath, SQLITE3_OPEN_READWRITE);
        foreach ($conversions as $conversion) {
            $clickid = (string)$conversion['clickid'];
            $status = (string)$conversion['status'];
            $countStmt = $db->prepare('SELECT COUNT(*) FROM conversions WHERE clickid = :clickid AND status = :status COLLATE NOCASE');
            $countStmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $countStmt->bindValue(':status', $status, SQLITE3_TEXT);
            $count = (int)$countStmt->execute()->fetchArray(SQLITE3_NUM)[0];

            $stmt = $db->prepare(
                "INSERT INTO conversions (clickid, campaign_id, flow, step, time, status, raw_status, source, tid, tid_parameter, payout, currency, is_initial, changes_status, status_occurrence)
                 VALUES (:clickid, :campaign, :flow, :step, :time, :status, :raw_status, :source, :tid, :tid_parameter, :payout, :currency, :is_initial, :changes_status, :occurrence)"
            );
            $stmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $stmt->bindValue(':campaign', $conversion['campaign_id'] ?? 1, SQLITE3_INTEGER);
            $stmt->bindValue(':flow', $conversion['flow'] ?? 'Flow 1', SQLITE3_TEXT);
            $step = $conversion['step'] ?? $db->querySingle(
                "SELECT step FROM clicks WHERE clickid = '" . SQLite3::escapeString($clickid) . "'"
            );
            $stmt->bindValue(':step', max(0, (int)$step), SQLITE3_INTEGER);
            $stmt->bindValue(':time', $conversion['time'] ?? 1700000000, SQLITE3_INTEGER);
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':raw_status', $conversion['raw_status'] ?? $status, SQLITE3_TEXT);
            $stmt->bindValue(':source', $conversion['source'] ?? 'postback', SQLITE3_TEXT);
            $tid = $conversion['tid'] ?? null;
            $stmt->bindValue(':tid', $tid, $tid === null ? SQLITE3_NULL : SQLITE3_TEXT);
            $tidParameter = $tid === null ? null : ($conversion['tid_parameter'] ?? 'tid');
            $stmt->bindValue(
                ':tid_parameter',
                $tidParameter,
                $tidParameter === null ? SQLITE3_NULL : SQLITE3_TEXT
            );
            $stmt->bindValue(':payout', $conversion['payout'] ?? 0, SQLITE3_FLOAT);
            $stmt->bindValue(':currency', $conversion['currency'] ?? 'USD', SQLITE3_TEXT);
            $stmt->bindValue(':is_initial', !empty($conversion['is_initial']) ? 1 : 0, SQLITE3_INTEGER);
            $changesStatus = !array_key_exists('changes_status', $conversion) || $conversion['changes_status'];
            $stmt->bindValue(':changes_status', $changesStatus ? 1 : 0, SQLITE3_INTEGER);
            $stmt->bindValue(':occurrence', $conversion['status_occurrence'] ?? ($count + 1), SQLITE3_INTEGER);
            $stmt->execute();

            $updateSql = $changesStatus
                ? 'UPDATE clicks SET status = :status, payout = COALESCE(payout, 0) + :payout WHERE clickid = :clickid'
                : 'UPDATE clicks SET payout = COALESCE(payout, 0) + :payout WHERE clickid = :clickid';
            $update = $db->prepare($updateSql);
            if ($changesStatus) $update->bindValue(':status', $status, SQLITE3_TEXT);
            $update->bindValue(':payout', $conversion['payout'] ?? 0, SQLITE3_FLOAT);
            $update->bindValue(':clickid', $clickid, SQLITE3_TEXT);
            $update->execute();
        }
        $db->close();
    }

    public function fetchConversions(string $clickid): array
    {
        $db = new SQLite3($this->testDbPath, SQLITE3_OPEN_READONLY);
        $stmt = $db->prepare('SELECT * FROM conversions WHERE clickid = :clickid ORDER BY id');
        $stmt->bindValue(':clickid', $clickid, SQLITE3_TEXT);
        $result = $stmt->execute();
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;
        $db->close();
        return $rows;
    }

    public function cleanup(): void
    {
        if (file_exists($this->testDbPath)) {
            // Close any WAL files
            $db = new SQLite3($this->testDbPath, SQLITE3_OPEN_READWRITE);
            $db->exec('PRAGMA wal_checkpoint(TRUNCATE)');
            $db->close();
            @unlink($this->testDbPath);
            @unlink($this->testDbPath . '-wal');
            @unlink($this->testDbPath . '-shm');
        }
    }
}
