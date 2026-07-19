<?php

final class UniquenessSettings implements JsonSerializable
{
    public const METHODS = ['ip', 'ip_ua', 'cookie', 'cookie_ip', 'cookie_ip_ua', 'get'];

    public bool $enabled;
    public string $method;
    public int $ttlHours;
    public string $getParameter;

    public static function fromArray(array $settings): self
    {
        $result = new self();
        $result->enabled = filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOL);
        $method = (string)($settings['method'] ?? 'cookie_ip_ua');
        $result->method = in_array($method, self::METHODS, true) ? $method : 'cookie_ip_ua';
        $result->ttlHours = max(1, min(720, (int)($settings['ttl_hours'] ?? 24)));
        $result->getParameter = is_string($settings['get_parameter'] ?? null)
            ? $settings['get_parameter']
            : '';
        return $result;
    }

    public function usesCookie(): bool
    {
        return in_array($this->method, ['cookie', 'cookie_ip', 'cookie_ip_ua'], true);
    }

    public function jsonSerialize(): array
    {
        return [
            'enabled' => $this->enabled,
            'method' => $this->method,
            'ttl_hours' => $this->ttlHours,
            'get_parameter' => $this->getParameter,
        ];
    }
}

final class UniquenessIdentity
{
    public function __construct(
        public readonly string $method,
        public readonly ?string $hash,
        public readonly string $userid,
        public readonly bool $lookupByUserid,
        public readonly bool $missingGetParameter,
        public readonly mixed $rawGetValue = null
    ) {
    }
}

final class UniquenessService
{
    public static function prepareIdentity(
        UniquenessSettings $settings,
        array $clickParams,
        string $incomingUserid
    ): UniquenessIdentity {
        $ip = self::canonicalIp((string)($clickParams['ip'] ?? ''));
        $ua = (string)($clickParams['ua'] ?? '');

        return match ($settings->method) {
            'ip' => new UniquenessIdentity('ip', self::hash('ip' . $ip), '', false, false),
            'ip_ua' => new UniquenessIdentity(
                'ip_ua',
                self::hash('ip_ua' . self::lengthPrefixed($ip) . self::lengthPrefixed($ua)),
                '',
                false,
                false
            ),
            'cookie' => new UniquenessIdentity(
                'cookie',
                null,
                $incomingUserid !== '' ? $incomingUserid : generate_userid(),
                true,
                false
            ),
            'cookie_ip' => new UniquenessIdentity(
                'cookie_ip',
                self::hash('ip' . $ip),
                $incomingUserid !== '' ? $incomingUserid : generate_userid(),
                $incomingUserid !== '',
                false
            ),
            'cookie_ip_ua' => new UniquenessIdentity(
                'cookie_ip_ua',
                self::hash('ip_ua' . self::lengthPrefixed($ip) . self::lengthPrefixed($ua)),
                $incomingUserid !== '' ? $incomingUserid : generate_userid(),
                $incomingUserid !== '',
                false
            ),
            'get' => self::prepareGetIdentity($settings, $clickParams),
        };
    }

    public static function isUnique(
        SQLite3 $db,
        int $campaignId,
        ?string $flow,
        UniquenessIdentity $identity,
        int $ttlCutoff
    ): bool {
        if ($identity->missingGetParameter) {
            return true;
        }

        $byCookie = $identity->method === 'cookie' || $identity->lookupByUserid;
        if ($byCookie) {
            $identityCondition = 'userid = :identity AND userid <> \'\'';
            $identityValue = $identity->userid;
            $identityType = SQLITE3_TEXT;
        } else {
            $identityCondition = 'unique_hash = :identity AND unique_hash IS NOT NULL';
            $identityValue = $identity->hash;
            $identityType = SQLITE3_BLOB;
        }

        $sql = 'SELECT 1 FROM clicks
                WHERE campaign_id = :campaign_id
                  AND ' . $identityCondition . '
                  AND unique_flags IS NOT NULL
                  AND time > :ttl_cutoff';
        if ($flow !== null) {
            $sql .= ' AND flow = :flow';
        }
        $sql .= ' ORDER BY time DESC LIMIT 1';

        $stmt = @$db->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare uniqueness lookup: ' . $db->lastErrorMsg());
        }
        $stmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(':identity', $identityValue, $identityType);
        $stmt->bindValue(':ttl_cutoff', $ttlCutoff, SQLITE3_INTEGER);
        if ($flow !== null) {
            $stmt->bindValue(':flow', $flow, SQLITE3_TEXT);
        }
        $result = @$stmt->execute();
        if ($result === false) {
            throw new RuntimeException('Failed to execute uniqueness lookup: ' . $db->lastErrorMsg());
        }
        return $result->fetchArray(SQLITE3_NUM) === false;
    }

    public static function bothScopesAreUnique(
        SQLite3 $db,
        int $campaignId,
        string $flow,
        UniquenessIdentity $identity,
        int $ttlCutoff
    ): array {
        if ($identity->missingGetParameter) {
            return [true, true];
        }

        $byCookie = $identity->method === 'cookie' || $identity->lookupByUserid;
        if ($byCookie) {
            $campaignIdentity = "campaign_click.userid = :identity AND campaign_click.userid <> ''";
            $flowIdentity = "flow_click.userid = :identity AND flow_click.userid <> ''";
            $identityValue = $identity->userid;
            $identityType = SQLITE3_TEXT;
        } else {
            $campaignIdentity = 'campaign_click.unique_hash = :identity AND campaign_click.unique_hash IS NOT NULL';
            $flowIdentity = 'flow_click.unique_hash = :identity AND flow_click.unique_hash IS NOT NULL';
            $identityValue = $identity->hash;
            $identityType = SQLITE3_BLOB;
        }

        $sql = 'SELECT
                    NOT EXISTS (
                        SELECT 1 FROM clicks AS campaign_click
                        WHERE campaign_click.campaign_id = :campaign_id
                          AND ' . $campaignIdentity . '
                          AND campaign_click.unique_flags IS NOT NULL
                          AND campaign_click.time > :ttl_cutoff
                        LIMIT 1
                    ) AS campaign_unique,
                    NOT EXISTS (
                        SELECT 1 FROM clicks AS flow_click
                        WHERE flow_click.campaign_id = :campaign_id
                          AND flow_click.flow = :flow
                          AND ' . $flowIdentity . '
                          AND flow_click.unique_flags IS NOT NULL
                          AND flow_click.time > :ttl_cutoff
                        LIMIT 1
                    ) AS flow_unique';

        $stmt = @$db->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('Failed to prepare combined uniqueness lookup: ' . $db->lastErrorMsg());
        }
        $stmt->bindValue(':campaign_id', $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(':flow', $flow, SQLITE3_TEXT);
        $stmt->bindValue(':identity', $identityValue, $identityType);
        $stmt->bindValue(':ttl_cutoff', $ttlCutoff, SQLITE3_INTEGER);
        $result = @$stmt->execute();
        if ($result === false) {
            throw new RuntimeException('Failed to execute combined uniqueness lookup: ' . $db->lastErrorMsg());
        }
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row === false) {
            throw new RuntimeException('Combined uniqueness lookup returned no result');
        }
        return [(bool)$row['campaign_unique'], (bool)$row['flow_unique']];
    }

    public static function encodeGetValue(mixed $value): string
    {
        if (is_array($value)) {
            if (array_is_list($value)) {
                $encoded = 'list' . pack('N', count($value));
                foreach ($value as $item) {
                    $encoded .= self::lengthPrefixed(self::encodeGetValue($item));
                }
                return $encoded;
            }

            $items = [];
            foreach ($value as $key => $item) {
                $encodedKey = self::encodeGetValue($key);
                $items[$encodedKey] = self::lengthPrefixed($encodedKey)
                    . self::lengthPrefixed(self::encodeGetValue($item));
            }
            ksort($items, SORT_STRING);
            return 'map' . pack('N', count($items)) . implode('', $items);
        }

        return match (true) {
            is_string($value) => 'string' . self::lengthPrefixed($value),
            is_int($value) => 'int' . self::lengthPrefixed((string)$value),
            is_float($value) => 'float' . self::lengthPrefixed(serialize($value)),
            is_bool($value) => $value ? 'bool1' : 'bool0',
            $value === null => 'null',
            default => 'other' . self::lengthPrefixed(serialize($value)),
        };
    }

    private static function prepareGetIdentity(
        UniquenessSettings $settings,
        array $clickParams
    ): UniquenessIdentity {
        $query = is_array($clickParams['qs'] ?? null) ? $clickParams['qs'] : [];
        $name = $settings->getParameter;
        if (!array_key_exists($name, $query)) {
            return new UniquenessIdentity('get', null, '', false, true);
        }

        $value = $query[$name];
        $canonical = 'get' . self::lengthPrefixed($name) . self::encodeGetValue($value);
        return new UniquenessIdentity('get', self::hash($canonical), '', false, false, $value);
    }

    private static function canonicalIp(string $ip): string
    {
        $packed = @inet_pton(trim($ip));
        if ($packed === false) {
            return strtolower(trim($ip));
        }
        return strtolower((string)inet_ntop($packed));
    }

    private static function lengthPrefixed(string $value): string
    {
        return pack('N', strlen($value)) . $value;
    }

    private static function hash(string $value): string
    {
        return hash('xxh128', $value, true);
    }
}
