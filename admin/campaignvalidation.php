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
