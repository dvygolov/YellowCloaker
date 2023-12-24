<?php
require_once __DIR__ . '/bases/ipcountry.php';

function select_item(array $items, bool $save_user_flow = false, string $item_type = 'landing', bool $is_folder = true): array
{
    $item = '';

    if ($save_user_flow) {
        $item = get_saved_item($item_type, $items, $is_folder);
    }

    if ($item === '') {
        $item = get_random_item($items, $is_folder);
    }

    ywbsetcookie($item_type, $item, '/');
    return [$item, array_search($item, $items)];
}

function get_saved_item(string $item_type, array $items, bool $is_folder): string
{
    $item = get_cookie($item_type);

    if (!in_array($item, $items, true) || ($is_folder && !is_folder_valid($item))) {
        return '';
    }

    return $item;
}

function is_folder_valid(string $item): bool
{
    return is_dir(__DIR__ . '/' . $item);
}

function get_random_item(array $items, bool $is_folder): string
{
    $random_index = rand(0, count($items) - 1);
    $item = $items[$random_index];

    if ($is_folder) {
        $item = append_country_if_available($item);
    }

    return $item;
}

function append_country_if_available(string $item): string
{
    $country = getcountry();
    if (is_dir(__DIR__ . '/' . $item . $country)) {
        $item .= $country;
    }
    return $item;
}

function select_item_by_index(array $items, int $index, bool $is_folder = true): string
{
    if (!isset($items[$index])) {
        $random_index = rand(0, count($items) - 1);
        $index = $random_index;
    }

    $item = $items[$index];

    if ($is_folder) {
        $item = append_country_if_available($item);
    }

    return $item;
}
