<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User-facing date formats (US: month-day-year with dashes)
    |--------------------------------------------------------------------------
    */

    'display' => env('DATE_DISPLAY_FORMAT', 'm-d-Y'),

    'display_datetime' => env('DATE_DISPLAY_DATETIME_FORMAT', 'm-d-Y g:i A'),

    'display_datetime_24h' => 'm-d-Y H:i',

    'display_datetime_seconds' => 'm-d-Y H:i:s',

];
