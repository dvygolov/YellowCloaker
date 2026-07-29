<?php

require_once __DIR__ . '/../uniqueness.php';

function normalize_uniqueness_input(array &$input): ?string
{
    if (!isset($input['uniqueness']) || !is_array($input['uniqueness'])) {
        return null;
    }

    $settings = &$input['uniqueness'];
    $settings['enabled'] = filter_var($settings['enabled'] ?? false, FILTER_VALIDATE_BOOL);

    $method = $settings['method'] ?? '';
    if (!is_string($method) || !in_array($method, UniquenessSettings::METHODS, true)) {
        return 'Invalid uniqueness method.';
    }

    $ttlRaw = $settings['ttl_hours'] ?? null;
    if (is_int($ttlRaw)) {
        $ttl = $ttlRaw;
    } elseif (is_string($ttlRaw) && preg_match('/^\d+$/', $ttlRaw) === 1) {
        $ttl = (int)$ttlRaw;
    } else {
        return 'Uniqueness TTL must be a whole number from 1 to 720 hours.';
    }
    if ($ttl < 1 || $ttl > 720) {
        return 'Uniqueness TTL must be a whole number from 1 to 720 hours.';
    }
    $settings['ttl_hours'] = $ttl;

    if (!is_string($settings['get_parameter'] ?? '')) {
        return 'GET parameter name must be a string.';
    }
    $settings['get_parameter'] = (string)($settings['get_parameter'] ?? '');
    return null;
}

function normalize_event_input(array &$input): ?string
{
    if (!array_key_exists('events', $input)) {
        return null;
    }
    if (!is_array($input['events'])) {
        return 'Invalid Events settings.';
    }
    if (!class_exists(EventSettings::class, false)) {
        require_once __DIR__ . '/../campaign.php';
    }

    $normalized = [];
    foreach ([
        'scroll' => ['maximum' => 100, 'label' => 'Scroll'],
        'time' => ['maximum' => 86400, 'label' => 'Visible-time'],
    ] as $key => $definition) {
        $section = $input['events'][$key] ?? [];
        if (!is_array($section)) {
            return $definition['label'] . ' event settings must be an object.';
        }
        $enabled = normalize_event_boolean($section['use'] ?? false);
        if ($enabled === null) {
            return $definition['label'] . ' event switch is invalid.';
        }
        $thresholds = $section['thresholds'] ?? [];
        if (is_string($thresholds)) {
            $thresholds = explode(',', $thresholds);
        }
        if (!is_array($thresholds)) {
            if ($enabled) {
                return $definition['label'] . ' thresholds must be a comma-separated list.';
            }
            $thresholds = [];
        }

        $values = [];
        foreach ($thresholds as $threshold) {
            if (is_string($threshold) && trim($threshold) === '') {
                continue;
            }
            if (
                (!is_int($threshold) && !is_string($threshold))
                || preg_match('/^\d+$/', trim((string)$threshold)) !== 1
            ) {
                if ($enabled) {
                    return $definition['label'] . ' thresholds must be whole numbers.';
                }
                continue;
            }
            $value = (int)$threshold;
            if ($value < 1 || $value > $definition['maximum']) {
                if ($enabled) {
                    return $definition['label'] . ' threshold is outside the allowed range.';
                }
                continue;
            }
            $values[$value] = $value;
            if ($enabled && count($values) > EventSettings::MAX_THRESHOLDS) {
                return $definition['label'] . ' supports at most '
                    . EventSettings::MAX_THRESHOLDS . ' thresholds.';
            }
        }
        ksort($values);
        $values = array_slice(array_values($values), 0, EventSettings::MAX_THRESHOLDS);
        if ($enabled && $values === []) {
            return 'Add at least one ' . strtolower($definition['label']) . ' threshold before enabling it.';
        }
        $normalized[$key] = ['use' => $enabled, 'thresholds' => $values];
    }

    $performance = $input['events']['performance'] ?? [];
    if (!is_array($performance)) {
        return 'Performance event settings must be an object.';
    }
    $performanceEnabled = normalize_event_boolean($performance['use'] ?? false);
    if ($performanceEnabled === null) {
        return 'Performance event switch is invalid.';
    }
    $normalized['performance'] = ['use' => $performanceEnabled];

    $custom = $input['events']['custom'] ?? [];
    if (!is_array($custom)) {
        return 'Custom events must be a list.';
    }
    if (count($custom) > EventSettings::MAX_CUSTOM_EVENTS) {
        return 'A campaign supports at most ' . EventSettings::MAX_CUSTOM_EVENTS . ' custom events.';
    }
    $customNames = [];
    foreach ($custom as $name) {
        if (!is_string($name)) {
            return 'Custom event names must be strings.';
        }
        $name = trim($name);
        if (preg_match(EventSettings::EVENT_NAME_PATTERN, $name) !== 1) {
            return 'Custom event names must start with a lowercase letter and use only lowercase letters, numbers, and underscores.';
        }
        if (EventSettings::isReservedEventName($name)) {
            return "Custom event name '{$name}' is reserved.";
        }
        if (isset($customNames[$name])) {
            return "Duplicate custom event name: {$name}.";
        }
        $customNames[$name] = $name;
    }
    $normalized['custom'] = array_values($customNames);
    $input['events'] = $normalized;
    return null;
}

function normalize_event_boolean(mixed $value): ?bool
{
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value) && ($value === 0 || $value === 1)) {
        return (bool)$value;
    }
    if (is_string($value) && in_array(strtolower(trim($value)), ['true', 'false', '1', '0'], true)) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    return null;
}

function normalize_conversion_input(array &$input): ?string
{
    if (!isset($input['conversions'])) {
        return null;
    }
    if (!is_array($input['conversions']) || !is_array($input['conversions']['statuses'] ?? null)) {
        return 'Invalid conversion status catalog.';
    }
    if (!class_exists(ConversionSettings::class, false)) {
        require_once __DIR__ . '/../campaign.php';
    }

    try {
        $catalog = ConversionSettings::normalizeStatusCatalog($input['conversions']['statuses']);
    } catch (InvalidArgumentException $e) {
        return $e->getMessage();
    }
    $input['conversions']['statuses'] = $catalog['statuses'];
    $owners = $catalog['owners'];

    $dedup = &$input['conversions']['deduplication'];
    if (!is_array($dedup ?? null)) {
        $dedup = [];
    }
    $dedup['enabled'] = filter_var($dedup['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
    try {
        $dedup['transaction_id_parameters'] = ConversionSettings::normalizeTransactionIdParameters(
            array_key_exists('transaction_id_parameters', $dedup)
                ? $dedup['transaction_id_parameters']
                : ['tid']
        );
    } catch (InvalidArgumentException $e) {
        return $e->getMessage();
    }
    $repeatMode = strtolower(trim((string)($dedup['paid_repeat_without_tid'] ?? 'reject')));
    $dedup['paid_repeat_without_tid'] = in_array($repeatMode, ['reject', 'upsell'], true) ? $repeatMode : 'reject';

    $form = &$input['conversions']['form'];
    if (!is_array($form ?? null)) {
        $form = [];
    }
    $form['enabled'] = filter_var($form['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $form['status'] = trim((string)($form['status'] ?? 'Lead')) ?: 'Lead';
    if (!isset($owners[strtolower($form['status'])])) {
        return 'Form conversion status must exist in the campaign catalog.';
    }
    $form['status'] = $owners[strtolower($form['status'])];

    $site = &$input['conversions']['site'];
    if (!is_array($site ?? null)) {
        $site = [];
    }
    $site['enabled'] = filter_var($site['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

    return null;
}

function normalize_postback_input(array &$input): ?string
{
    if (!array_key_exists('postback', $input)) {
        return null;
    }
    if (!is_array($input['postback'])) {
        return 'Invalid Postbacks settings.';
    }
    if (
        !class_exists(PostbackSettings::class, false)
        || !class_exists(ConversionSettings::class, false)
        || !class_exists(EventSettings::class, false)
    ) {
        require_once __DIR__ . '/../campaign.php';
    }

    $postback = &$input['postback'];
    $pbkey = $postback['pbkey'] ?? [];
    if (!is_array($pbkey)) {
        return 'Invalid key protection settings.';
    }
    $pbkeyEnabled = normalize_event_boolean($pbkey['enabled'] ?? false);
    if ($pbkeyEnabled === null) {
        return 'Key protection switch is invalid.';
    }
    $rawKeys = $pbkey['keys'] ?? [];
    if (is_string($rawKeys)) {
        $rawKeys = explode(',', $rawKeys);
    }
    if (!is_array($rawKeys)) {
        return 'Allowed key values must be a comma-separated list.';
    }
    $keys = [];
    foreach ($rawKeys as $rawKey) {
        if (!is_string($rawKey)) {
            return 'Allowed key values must be strings.';
        }
        $key = trim($rawKey);
        if ($key === '') {
            continue;
        }
        if (strlen($key) > 255 || preg_match('/[\x00-\x1F\x7F]/', $key) === 1) {
            return 'Allowed key values must contain at most 255 characters without control characters.';
        }
        $keys[$key] = $key;
    }
    $keys = array_values($keys);
    if ($pbkeyEnabled && $keys === []) {
        return 'Add at least one allowed key value before enabling Key protection.';
    }
    $postback['pbkey'] = ['enabled' => $pbkeyEnabled, 'keys' => $keys];

    $rawRules = $postback['s2s'] ?? [];
    if (!is_array($rawRules) || !array_is_list($rawRules)) {
        return 'S2S postbacks must be a list.';
    }
    if (count($rawRules) > PostbackSettings::MAX_S2S_POSTBACKS) {
        return 'A campaign supports at most ' . PostbackSettings::MAX_S2S_POSTBACKS . ' S2S postbacks.';
    }

    $conversionSettings = ConversionSettings::fromArray(
        is_array($input['conversions'] ?? null) ? $input['conversions'] : []
    );
    $statusCatalog = [];
    foreach ($conversionSettings->statusNames() as $statusName) {
        $statusCatalog[strtolower($statusName)] = $statusName;
    }
    $eventSettings = EventSettings::fromArray($input['events'] ?? []);
    $eventCatalog = array_fill_keys($eventSettings->getConfiguredEventNames(), true);

    $normalizedRules = [];
    foreach ($rawRules as $index => $rawRule) {
        $label = 'S2S postback #' . ($index + 1);
        if (!is_array($rawRule)) {
            return $label . ' must be an object.';
        }

        $url = $rawRule['url'] ?? null;
        if (!is_string($url)) {
            return $label . ' URL must be a string.';
        }
        $url = trim($url);
        $urlParts = strlen($url) <= PostbackSettings::MAX_S2S_URL_LENGTH
            ? parse_url($url)
            : false;
        $scheme = is_array($urlParts) ? strtolower((string)($urlParts['scheme'] ?? '')) : '';
        if (
            $url === ''
            || preg_match('/[\x00-\x1F\x7F]/', $url) === 1
            || str_contains($url, '\\')
            || preg_match('/%(?![0-9A-Fa-f]{2})/', $url) === 1
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || !in_array($scheme, ['http', 'https'], true)
            || trim((string)($urlParts['host'] ?? '')) === ''
            || isset($urlParts['user'])
            || isset($urlParts['pass'])
        ) {
            return $label . ' URL must be a valid http:// or https:// address.';
        }

        $method = $rawRule['method'] ?? null;
        if (!is_string($method)) {
            return $label . ' method must be GET or POST.';
        }
        $method = strtoupper(trim($method));
        if (!in_array($method, ['GET', 'POST'], true)) {
            return $label . ' method must be GET or POST.';
        }

        $statuses = normalize_postback_trigger_list(
            $rawRule['statuses'] ?? null,
            $statusCatalog,
            $label . ' conversion statuses',
            true
        );
        if (is_string($statuses)) {
            return $statuses;
        }
        $events = normalize_postback_trigger_list(
            $rawRule['events'] ?? null,
            $eventCatalog,
            $label . ' events',
            false
        );
        if (is_string($events)) {
            return $events;
        }
        if ($statuses === [] && $events === []) {
            return $label . ' must select at least one conversion status or event.';
        }

        $normalizedRules[] = [
            'url' => $url,
            'method' => $method,
            'statuses' => $statuses,
            'events' => $events,
        ];
    }
    $postback['s2s'] = $normalizedRules;

    return null;
}

/**
 * @param array<string, string|bool> $catalog
 * @return list<string>|string
 */
function normalize_postback_trigger_list(
    mixed $rawValues,
    array $catalog,
    string $label,
    bool $caseInsensitive
): array|string {
    if (!is_array($rawValues) || !array_is_list($rawValues)) {
        return ucfirst($label) . ' must be a list.';
    }

    $normalized = [];
    foreach ($rawValues as $rawValue) {
        if (!is_string($rawValue)) {
            return ucfirst($label) . ' must contain strings.';
        }
        $value = trim($rawValue);
        if ($value === '') {
            continue;
        }
        $lookup = $caseInsensitive ? strtolower($value) : $value;
        if (!array_key_exists($lookup, $catalog)) {
            return ucfirst($label) . " contains an unavailable value: {$value}.";
        }
        $canonical = $caseInsensitive ? (string)$catalog[$lookup] : $value;
        $dedupeKey = $caseInsensitive ? strtolower($canonical) : $canonical;
        if (!isset($normalized[$dedupeKey])) {
            $normalized[$dedupeKey] = $canonical;
        }
    }
    return array_values($normalized);
}

function find_uniqueness_rule_flows(array $flows): array
{
    $affected = [];
    foreach ($flows as $index => $flow) {
        if (!is_array($flow) || !rules_contain_uniqueness($flow['filters'] ?? [])) {
            continue;
        }
        $name = trim((string)($flow['name'] ?? ''));
        $affected[] = $name !== '' ? $name : 'Flow ' . ($index + 1);
    }
    return array_values(array_unique($affected));
}

function rules_contain_uniqueness(mixed $node): bool
{
    if (!is_array($node)) {
        return false;
    }
    if (($node['id'] ?? null) === 'uniqueness' || ($node['field'] ?? null) === 'uniqueness') {
        return true;
    }
    foreach ($node as $value) {
        if (is_array($value) && rules_contain_uniqueness($value)) {
            return true;
        }
    }
    return false;
}
