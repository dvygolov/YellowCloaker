<?php

final class CampaignDomainException extends RuntimeException
{
}

final class RuntimeCampaignCache
{
    /** @return array{available: bool, campaign: array<string, mixed>|false} */
    public static function findCampaign(string $requestHost): array
    {
        $domainsPath = self::runtimeDirectory() . DIRECTORY_SEPARATOR . 'domains.php';
        $domains = self::loadArray($domainsPath);
        if ($domains === null || !is_array($domains['exact'] ?? null) || !is_array($domains['wildcard'] ?? null)) {
            return ['available' => false, 'campaign' => false];
        }

        try {
            $host = self::parseHost($requestHost, false);
        } catch (CampaignDomainException) {
            return ['available' => true, 'campaign' => false];
        }

        $campaignId = $domains['exact'][$host['normalized']] ?? null;
        if ($campaignId === null && !$host['ip']) {
            $labels = explode('.', $host['host']);
            for ($i = 1, $count = count($labels); $i < $count; $i++) {
                $suffix = implode('.', array_slice($labels, $i)) . $host['port_suffix'];
                if (isset($domains['wildcard'][$suffix])) {
                    $campaignId = $domains['wildcard'][$suffix];
                    break;
                }
            }
        }

        if (!is_int($campaignId) && !ctype_digit((string)$campaignId)) {
            return ['available' => true, 'campaign' => false];
        }
        $campaign = self::loadArray(
            self::runtimeDirectory() . DIRECTORY_SEPARATOR . 'campaign-' . (int)$campaignId . '.php'
        );
        if ($campaign === null || !is_array($campaign['settings'] ?? null)) {
            return ['available' => false, 'campaign' => false];
        }
        return ['available' => true, 'campaign' => $campaign];
    }

    /** @param array<string, mixed> $settings @return array<string, mixed> */
    public static function normalizeSettingsDomains(array $settings): array
    {
        $normalized = [];
        foreach (($settings['domains'] ?? []) as $domain) {
            if (!is_string($domain)) {
                throw new CampaignDomainException('Domain must be a string.');
            }
            $normalized[] = self::parseRule($domain)['normalized'];
        }
        $settings['domains'] = array_values($normalized);

        if (is_array($settings['white']['domainfilter']['domains'] ?? null)) {
            foreach ($settings['white']['domainfilter']['domains'] as &$domainSettings) {
                if (!is_array($domainSettings) || !isset($domainSettings['domain'])) {
                    continue;
                }
                try {
                    $domainSettings['domain'] = self::parseRule((string)$domainSettings['domain'])['normalized'];
                } catch (CampaignDomainException) {
                    // Stale domain-specific settings do not participate in routing.
                }
            }
            unset($domainSettings);
        }
        return $settings;
    }

    /** @param array<string, mixed> $settings */
    public static function validateCandidate(object $db, int $campaignId, array $settings): void
    {
        $rows = $db->get_campaign_runtime_rows();
        $found = false;
        foreach ($rows as &$row) {
            if ((int)($row['id'] ?? 0) !== $campaignId) {
                continue;
            }
            $row['settings'] = $settings;
            $found = true;
            break;
        }
        unset($row);
        if (!$found) {
            $rows[] = [
                'id' => $campaignId,
                'name' => 'Campaign ' . $campaignId,
                'settings' => $settings,
            ];
        }
        self::buildDomainIndex($rows);
    }

    public static function rebuild(object $db): void
    {
        $rows = $db->get_campaign_runtime_rows();
        $domainIndex = self::buildDomainIndex($rows);
        $directory = self::runtimeDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create runtime cache directory.');
        }

        $activeFiles = [];
        try {
            foreach ($rows as $row) {
                $campaignId = (int)$row['id'];
                $settings = self::normalizeSettingsDomains($row['settings']);
                $path = $directory . DIRECTORY_SEPARATOR . 'campaign-' . $campaignId . '.php';
                self::writePhpArray($path, [
                    'id' => $campaignId,
                    'name' => (string)$row['name'],
                    'settings' => $settings,
                ]);
                $activeFiles[$path] = true;
            }

            foreach (glob($directory . DIRECTORY_SEPARATOR . 'campaign-*.php') ?: [] as $path) {
                if (!isset($activeFiles[$path])) {
                    self::removePhpFile($path);
                }
            }

            self::writePhpArray($directory . DIRECTORY_SEPARATOR . 'domains.php', $domainIndex);
        } catch (Throwable $e) {
            self::invalidateDomainsSnapshot();
            throw $e;
        }
    }

    public static function invalidateDomainsSnapshot(): void
    {
        self::removePhpFile(self::runtimeDirectory() . DIRECTORY_SEPARATOR . 'domains.php');
    }

    public static function matches(array $domains, string $requestHost): bool
    {
        $rows = [[
            'id' => 1,
            'name' => 'Campaign 1',
            'settings' => ['domains' => $domains],
        ]];
        $index = self::buildDomainIndex($rows);
        try {
            $host = self::parseHost($requestHost, false);
        } catch (CampaignDomainException) {
            return false;
        }
        if (isset($index['exact'][$host['normalized']])) {
            return true;
        }
        if ($host['ip']) {
            return false;
        }
        $labels = explode('.', $host['host']);
        for ($i = 1, $count = count($labels); $i < $count; $i++) {
            if (isset($index['wildcard'][implode('.', array_slice($labels, $i)) . $host['port_suffix']])) {
                return true;
            }
        }
        return false;
    }

    /** @param array<int, array<string, mixed>> $rows @return array{exact: array<string, int>, wildcard: array<string, int>} */
    private static function buildDomainIndex(array $rows): array
    {
        $rules = [];
        $index = ['exact' => [], 'wildcard' => []];
        foreach ($rows as $row) {
            $campaignId = (int)($row['id'] ?? 0);
            $campaignName = trim((string)($row['name'] ?? '')) ?: 'Campaign ' . $campaignId;
            $settings = is_array($row['settings'] ?? null) ? $row['settings'] : [];
            foreach (($settings['domains'] ?? []) as $rawDomain) {
                if (!is_string($rawDomain)) {
                    throw new CampaignDomainException("Campaign '{$campaignName}' contains an invalid domain.");
                }
                try {
                    $rule = self::parseRule($rawDomain);
                } catch (CampaignDomainException $e) {
                    throw new CampaignDomainException("Campaign '{$campaignName}': " . $e->getMessage(), 0, $e);
                }
                $rule['campaign_id'] = $campaignId;
                $rule['campaign_name'] = $campaignName;
                foreach ($rules as $existing) {
                    if (!self::rulesOverlap($rule, $existing)) {
                        continue;
                    }
                    throw new CampaignDomainException(
                        "Domain '{$rule['normalized']}' in campaign '{$campaignName}' conflicts with "
                        . "'{$existing['normalized']}' in campaign '{$existing['campaign_name']}'."
                    );
                }
                $rules[] = $rule;
                if ($rule['wildcard']) {
                    $index['wildcard'][$rule['host'] . $rule['port_suffix']] = $campaignId;
                } else {
                    $index['exact'][$rule['normalized']] = $campaignId;
                }
            }
        }
        return $index;
    }

    /** @return array{normalized: string, host: string, port_suffix: string, wildcard: bool, ip: bool} */
    private static function parseRule(string $value): array
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            throw new CampaignDomainException('Domain can not be empty.');
        }
        $wildcard = str_starts_with($value, '*.');
        if (str_contains($value, '*') && !$wildcard) {
            throw new CampaignDomainException("Wildcard is allowed only at the beginning, for example '*.example.com'.");
        }
        $hostValue = $wildcard ? substr($value, 2) : $value;
        $parsed = self::parseHost($hostValue, $wildcard);
        if ($wildcard && $parsed['ip']) {
            throw new CampaignDomainException('Wildcard can not be used with an IP address.');
        }
        if ($wildcard && !str_contains($parsed['host'], '.')) {
            throw new CampaignDomainException('Wildcard domain must contain a registrable hostname.');
        }
        $parsed['wildcard'] = $wildcard;
        $parsed['normalized'] = ($wildcard ? '*.' : '') . $parsed['normalized'];
        return $parsed;
    }

    /** @return array{normalized: string, host: string, port_suffix: string, wildcard: bool, ip: bool} */
    private static function parseHost(string $value, bool $wildcard): array
    {
        $value = strtolower(trim($value));
        if ($value === '' || preg_match('~[\\s/?#@]~u', $value) === 1 || str_contains($value, '://')) {
            throw new CampaignDomainException('Use a hostname without scheme, path or spaces.');
        }

        $host = '';
        $port = null;
        if (str_starts_with($value, '[')) {
            if (!preg_match('/^\[([^\]]+)](?::([0-9]+))?$/', $value, $match)) {
                throw new CampaignDomainException('Invalid IPv6 host or port.');
            }
            $host = $match[1];
            $port = $match[2] ?? null;
        } elseif (substr_count($value, ':') > 1) {
            $host = $value;
        } elseif (!preg_match('/^([^:]+)(?::([0-9]+))?$/', $value, $match)) {
            throw new CampaignDomainException('Invalid host or port.');
        } else {
            $host = $match[1];
            $port = $match[2] ?? null;
        }

        $host = rtrim($host, '.');
        if ($host === '') {
            throw new CampaignDomainException('Domain can not be empty.');
        }
        if ($port !== null && ((int)$port < 1 || (int)$port > 65535)) {
            throw new CampaignDomainException('Port must be between 1 and 65535.');
        }

        $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
        if (!$isIp && $host !== 'localhost') {
            if (strlen($host) > 253 || preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/', $host) !== 1) {
                throw new CampaignDomainException('Invalid hostname.');
            }
        }
        if ($wildcard && ($host === 'localhost' || $isIp)) {
            throw new CampaignDomainException('Wildcard requires a hostname.');
        }

        $portSuffix = $port === null ? '' : ':' . (int)$port;
        $normalizedHost = str_contains($host, ':') ? '[' . $host . ']' : $host;
        return [
            'normalized' => $normalizedHost . $portSuffix,
            'host' => $host,
            'port_suffix' => $portSuffix,
            'wildcard' => false,
            'ip' => $isIp,
        ];
    }

    /** @param array<string, mixed> $left @param array<string, mixed> $right */
    private static function rulesOverlap(array $left, array $right): bool
    {
        if ($left['port_suffix'] !== $right['port_suffix']) {
            return false;
        }
        if (!$left['wildcard'] && !$right['wildcard']) {
            return $left['host'] === $right['host'];
        }
        if ($left['wildcard'] && $right['wildcard']) {
            return $left['host'] === $right['host']
                || str_ends_with($left['host'], '.' . $right['host'])
                || str_ends_with($right['host'], '.' . $left['host']);
        }
        $wildcard = $left['wildcard'] ? $left : $right;
        $exact = $left['wildcard'] ? $right : $left;
        return $exact['host'] !== $wildcard['host']
            && str_ends_with($exact['host'], '.' . $wildcard['host']);
    }

    /** @return array<string, mixed>|null */
    private static function loadArray(string $path): ?array
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        try {
            $value = include $path;
        } catch (Throwable) {
            return null;
        }
        return is_array($value) ? $value : null;
    }

    /** @param array<string, mixed> $value */
    private static function writePhpArray(string $path, array $value): void
    {
        $content = "<?php\n\nreturn " . var_export($value, true) . ";\n";
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
        if (file_put_contents($temporary, $content, LOCK_EX) === false) {
            throw new RuntimeException('Failed to write runtime cache file.');
        }
        @chmod($temporary, 0644);

        $replaced = null;
        if (DIRECTORY_SEPARATOR === '\\' && is_file($path)) {
            $replaced = $path . '.replacing';
            @unlink($replaced);
            if (!@rename($path, $replaced)) {
                @unlink($temporary);
                throw new RuntimeException('Failed to prepare runtime cache replacement.');
            }
        }
        if (!@rename($temporary, $path)) {
            if ($replaced !== null && is_file($replaced)) {
                @rename($replaced, $path);
            }
            @unlink($temporary);
            throw new RuntimeException('Failed to publish runtime cache file.');
        }
        if ($replaced !== null) {
            @unlink($replaced);
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    private static function removePhpFile(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    private static function runtimeDirectory(): string
    {
        $path = rtrim(get_cache_path('runtime'), '/\\');
        $absolute = DIRECTORY_SEPARATOR === '\\'
            ? preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1
            : str_starts_with($path, '/');
        if ($absolute) {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }
        return __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}
