<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/admin/timezones.php';

final class TimezoneOptionsTest extends TestCase
{
    public function testOffsetLabelsAlwaysIncludeHoursAndMinutes(): void
    {
        $this->assertSame('UTC+00:00', format_timezone_offset_label(0));
        $this->assertSame('UTC+02:00', format_timezone_offset_label(2 * 3600));
        $this->assertSame('UTC-03:00', format_timezone_offset_label(-3 * 3600));
        $this->assertSame('UTC+05:30', format_timezone_offset_label((5 * 3600) + (30 * 60)));
    }

    public function testOptionLabelKeepsIanaNameAndAddsOffsetForTheRequestedDate(): void
    {
        $at = new DateTimeImmutable('2026-07-24T12:00:00+00:00');

        $this->assertSame(
            'Europe/Samara (UTC+04:00)',
            get_timezone_option_label('Europe/Samara', $at)
        );
        $this->assertSame('Invalid/Timezone', get_timezone_option_label('Invalid/Timezone', $at));
    }

    public function testOptionLabelUsesSeasonalAndFractionalOffsets(): void
    {
        $winter = new DateTimeImmutable('2026-01-15T12:00:00+00:00');
        $summer = new DateTimeImmutable('2026-07-15T12:00:00+00:00');

        $this->assertSame(
            'America/New_York (UTC-05:00)',
            get_timezone_option_label('America/New_York', $winter)
        );
        $this->assertSame(
            'America/New_York (UTC-04:00)',
            get_timezone_option_label('America/New_York', $summer)
        );
        $this->assertSame(
            'Asia/Kathmandu (UTC+05:45)',
            get_timezone_option_label('Asia/Kathmandu', $summer)
        );
    }

    public function testTimezoneOptionValueRemainsTheIanaIdentifier(): void
    {
        $samara = array_values(array_filter(
            get_timezone_options(),
            static fn(array $option): bool => $option['value'] === 'Europe/Samara'
        ));

        $this->assertCount(1, $samara);
        $this->assertSame('Europe/Samara', $samara[0]['value']);
        $this->assertSame('UTC+04:00', $samara[0]['short']);
        $this->assertSame('Europe/Samara (UTC+04:00)', $samara[0]['label']);
    }
}
