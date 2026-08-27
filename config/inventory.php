<?php

return [
    /*
     | Variance color-coding thresholds (percent of total available). Also used
     | to decide which severities raise a large-variance alert.
     */
    'variance' => [
        'green_pct' => (float) env('INVENTORY_VARIANCE_GREEN_PCT', 2.0),
        'yellow_pct' => (float) env('INVENTORY_VARIANCE_YELLOW_PCT', 5.0),
        // Severities that trigger the weekly large-variance alert.
        'alert_on' => ['red'],
    ],
];
