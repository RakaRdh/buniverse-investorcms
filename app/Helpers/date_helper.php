<?php

if (!function_exists('date2ID')) {
    /**
     * Convert DateTime string/timestamp to formal Indonesian editorial date format
     *
     * @param string|int|null $datetime
     * @param string $format 'full', 'medium', 'short', 'time', 'day_only', 'date_only'
     * @param string $timezone 'WIB', 'WITA', 'WIT'
     * @return string
     */
    function date2ID($datetime = null, string $format = 'full', string $timezone = 'WIB'): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        if (!$timestamp) {
            return '-';
        }

        $days = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];

        $months = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $shortMonths = [
            1  => 'Jan',
            2  => 'Feb',
            3  => 'Mar',
            4  => 'Apr',
            5  => 'Mei',
            6  => 'Jun',
            7  => 'Jul',
            8  => 'Agu',
            9  => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des',
        ];

        $dayNameEnglish = date('l', $timestamp);
        $dayName = $days[$dayNameEnglish] ?? $dayNameEnglish;

        $dayNumber = date('j', $timestamp);
        $monthNumber = (int)date('n', $timestamp);
        $year = date('Y', $timestamp);
        $time = date('H:i', $timestamp);
        $timeWithSec = date('H:i:s', $timestamp);

        $monthName = $months[$monthNumber] ?? date('F', $timestamp);
        $shortMonthName = $shortMonths[$monthNumber] ?? date('M', $timestamp);

        return match ($format) {
            // Contoh: Senin, 10 September 2026, 14:15 WIB
            'full' => "{$dayName}, {$dayNumber} {$monthName} {$year}, {$time} {$timezone}",

            // Contoh: 10 September 2026, 14:15 WIB
            'standard' => "{$dayNumber} {$monthName} {$year}, {$time} {$timezone}",

            // Contoh: 10 Sep 2026, 14:15
            'medium' => "{$dayNumber} {$shortMonthName} {$year}, {$time}",

            // Contoh: 10 Sep 2026
            'short' => "{$dayNumber} {$shortMonthName} {$year}",

            // Contoh: 10 September 2026
            'date_only' => "{$dayNumber} {$monthName} {$year}",

            // Contoh: Senin, 10 September 2026
            'day_date' => "{$dayName}, {$dayNumber} {$monthName} {$year}",

            // Contoh: Senin
            'day_only' => $dayName,

            // Contoh: 14:15 WIB
            'time' => "{$time} {$timezone}",

            default => "{$dayNumber} {$monthName} {$year}",
        };
    }
}
