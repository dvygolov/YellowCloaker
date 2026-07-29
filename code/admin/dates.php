<?php

class Dates{
    public static function get_time_range(string $timezone): array
    {
        date_default_timezone_set($timezone);
        $dtz = new DateTimeZone($timezone);
        $startdate = Dates::get_start_date($dtz);
        $enddate = Dates::get_end_date($dtz);
        $startdate->setTime(0, 0, 0);
        $enddate->setTime(23, 59, 59);

        return [$startdate->getTimestamp(), $enddate->getTimestamp()];
    }

    public static function get_calend_dates(): array
    {
        $today = (new DateTime("now"))->format('d.m.y');
        $calendsd = $_GET['startdate'] ?? $today;
        $calended = $_GET['enddate'] ?? $today;
        return [$calendsd, $calended];
    }
    
    private static function get_start_date(DateTimeZone $dtz):DateTime
    {
        $startdate = isset($_GET['startdate']) ?
            DateTime::createFromFormat('d.m.y', $_GET['startdate'], $dtz) :
            new DateTime("now", $dtz);
        return $startdate;
    }

    private static function get_end_date(DateTimeZone $dtz):DateTime
    {
        $enddate = isset($_GET['enddate']) ?
            DateTime::createFromFormat('d.m.y', $_GET['enddate'], $dtz) :
            new DateTime("now", $dtz);
        return $enddate;
    }
}