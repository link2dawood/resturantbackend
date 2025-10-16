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

            return $carbon->format('m-d-Y');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format date to US format with time (MM-DD-YYYY h:i A)
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

            return $carbon->format('m-d-Y h:i A');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format date to US short format (M d, Y)
     */
    public static function toUSShort($date)
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

            return $carbon->format('M d, Y');
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Format date to display format with slashes (MM/DD/YYYY)
     */
    public static function toUSDisplay($date)
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

            return $carbon->format('m/d/Y');
        } catch (\Exception $e) {
            return '';
        }
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
