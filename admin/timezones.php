<?php

function format_timezone_offset_label(int $offsetSeconds): string
{
    $sign = $offsetSeconds >= 0 ? '+' : '-';
    $absOffset = abs($offsetSeconds);
    $hours = intdiv($absOffset, 3600);
    $minutes = intdiv($absOffset % 3600, 60);

    return sprintf('UTC%s%02d:%02d', $sign, $hours, $minutes);
}

function get_timezone_option_label(string $timezone, ?DateTimeImmutable $at = null): string
{
    try {
        $tz = new DateTimeZone($timezone);
        $moment = $at ?? new DateTimeImmutable('now');
        $offsetLabel = format_timezone_offset_label($tz->getOffset($moment));

        return $timezone . ' (' . $offsetLabel . ')';
    } catch (Exception) {
        return $timezone;
    }
}

function get_timezone_options(): array
{
    static $options = null;
    if ($options !== null) {
        return $options;
    }

    $now = new DateTimeImmutable('now');
    $options = [];

    foreach (timezone_identifiers_list() as $zone) {
        $tz = new DateTimeZone($zone);
        $offset = $tz->getOffset($now);
        $offsetLabel = format_timezone_offset_label($offset);
        $options[] = [
            'value' => $zone,
            'offset' => $offset,
            'short' => $offsetLabel,
            'label' => $zone . ' (' . $offsetLabel . ')',
        ];
    }

    usort($options, static function (array $a, array $b): int {
        if ($a['offset'] === $b['offset']) {
            return strcasecmp($a['value'], $b['value']);
        }

        return $a['offset'] <=> $b['offset'];
    });

    return $options;
}

function get_timezone_short_label(string $timezone): string
{
    try {
        $tz = new DateTimeZone($timezone);
        return format_timezone_offset_label($tz->getOffset(new DateTimeImmutable('now', $tz)));
    } catch (Exception) {
        return $timezone;
    }
}
