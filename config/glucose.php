<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Glucose Thresholds
    |--------------------------------------------------------------------------
    |
    | These values define the default thresholds for glucose readings in mg/dL.
    | Users can override these values in their notification settings.
    |
    */

    'hyperglycemia_threshold' => env('GLUCOSE_HYPERGLYCEMIA_THRESHOLD', 140),

    'hypoglycemia_threshold' => env('GLUCOSE_HYPOGLYCEMIA_THRESHOLD', 70),

    /*
    |--------------------------------------------------------------------------
    | Analysis Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for glucose analysis and notifications.
    |
    */

    'high_readings_percent_trigger' => env('GLUCOSE_HIGH_READINGS_TRIGGER', 30),

    'analysis_window_days' => env('GLUCOSE_ANALYSIS_WINDOW_DAYS', 7),
];
