<?php

namespace App\Helpers;

use Carbon\Carbon;
class DateFormatter
{
    /**
     * Format date to US format (MM-DD-YYYY)
     */
    public static function toUS($date)
    {
        if (! $date) {
            return '';
        }

        try {
            if (is_string($date)) {
                $carbon = Carbon::parse($date);
            } elseif ($date instanceof Carbon) {
                $carbon = $date;
            } else {
                return '';
            }

            return $carbon->format((string) config('dates.display', 'm-d-Y'));
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format date to US format with time (uses config dates.display_datetime)
     */
    public static function toUSWithTime($date)
    {
        if (! $date) {
            return '';
        }

        try {
            if (is_string($date)) {
                $carbon = Carbon::parse($date);
            } elseif ($date instanceof Carbon) {
                $carbon = $date;
            } else {
                return '';
            }

            return $carbon->format((string) config('dates.display_datetime', 'm-d-Y g:i A'));
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format date to configured display format (default MM-DD-YYYY).
     */
    public static function toUSShort($date)
    {
        return self::toUS($date);
    }

    /**
     * Same as {@see toUS()} — aligns with config `dates.display`.
     */
    public static function toUSDisplay($date)
    {
        return self::toUS($date);
    }

    public static function displayFormat(): string
    {
        return (string) config('dates.display', 'm-d-Y');
    }

    public static function displayDateTimeFormat(): string
    {
        return (string) config('dates.display_datetime', 'm-d-Y g:i A');
    }

    /**
     * Convert MM-DD-YYYY to YYYY-MM-DD for backend
     */
    public static function fromUS($dateString)
    {
        if (! $dateString) {
            return null;
        }

        try {
            // Check if already in ISO format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                return $dateString;
            }

            // Handle MM-DD-YYYY format
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateString)) {
                $parts = explode('-', $dateString);

                return $parts[2].'-'.$parts[0].'-'.$parts[1];
            }

            // Try to parse and convert
            $carbon = Carbon::parse($dateString);

            return $carbon->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
