<?php

declare(strict_types=1);

return [
    'premium' => [
        'max_tokens' => (int) env('SNAP_PREMIUM_MAX_TOKENS', 4096),
    ],
    'free_trial_scans' => 2,
    'other_ai_monthly_usd' => 0.50,
    'cost_alert_usd' => 0.03,
    'journey_days' => 7,
];
