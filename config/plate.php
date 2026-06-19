<?php

declare(strict_types=1);

use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Approvals\LogHealthEntryApprovalExecutor;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\UpdateHouseholdContext;
use App\Ai\Tools\UpdateUserBiometrics;
use App\Ai\Tools\UpdateUserProfileAttributes;
use Laravel\Ai\Providers\Tools\WebSearch;

return [
    'enable_premium_upgrades' => (bool) env('PLATE_ENABLE_PREMIUM_UPGRADES', false),

    'premium_rollout' => [
        'allowlist' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('PLATE_PREMIUM_ROLLOUT_ALLOWLIST', '')),
        ))),
        'percentage' => (int) env('PLATE_PREMIUM_ROLLOUT_PERCENTAGE', 0),
    ],

    'chat' => [
        'temporary_retention_hours' => (int) env('CHAT_TEMPORARY_RETENTION_HOURS', 48),
    ],

    'health_sync' => [
        'app_store_url' => (string) env('HEALTH_SYNC_APP_STORE_URL', 'https://apps.apple.com/us/app/acara-health-sync/id6761504525'),
        'minimum_ios_version' => (string) env('HEALTH_SYNC_MIN_IOS_VERSION', '18.0'),
    ],

    'food_photo_analyzer' => [
        'model' => (string) env('FOOD_PHOTO_ANALYZER_MODEL', 'gemini-3.5-flash'),

        'reference_lookup' => [
            'enabled' => (bool) env('FOOD_REFERENCE_LOOKUP_ENABLED', true),
            'match_threshold' => (float) env('FOOD_REFERENCE_MATCH_THRESHOLD', 0.5),

            'embeddings' => [
                'enabled' => (bool) env('FOOD_REFERENCE_EMBEDDINGS_ENABLED', false),
                'dimensions' => (int) env('FOOD_REFERENCE_EMBEDDINGS_DIMENSIONS', 1536),
                'threshold' => (float) env('FOOD_REFERENCE_EMBEDDINGS_THRESHOLD', 0.8),
            ],
        ],
    ],

    'tools' => [
        GetUserProfile::class,
        LogHealthEntry::class,
        EnrichAttributeMetadata::class,
        UpdateUserProfileAttributes::class,
        UpdateUserBiometrics::class,
        UpdateHouseholdContext::class,
    ],

    'shared_tools' => [
        GetDietReference::class,
    ],

    'sub_agents' => [
        MealPlanSpecialist::class,
        NutritionSpecialist::class,
        GlucoseSpikeSpecialist::class,
        HealthSpecialist::class,
        FitnessSpecialist::class,
    ],

    'image_tools' => [
        AnalyzePhoto::class,
    ],

    'provider_tools' => [
        WebSearch::class,
    ],

    'approvals' => [
        'executors' => [
            'log_health_entry' => LogHealthEntryApprovalExecutor::class,
        ],
        'ttl_hours' => [
            'default' => 24,
            'log_health_entry' => 24,
        ],
    ],

    'credit_multiplier' => 1_000,

    'model_pricing' => [
        'default' => ['input' => 0.50, 'output' => 2.00, 'reasoning' => 0.0, 'cache_read' => 0.25],
        'models' => [
            'gpt-5-mini' => ['input' => 0.15, 'output' => 0.60, 'reasoning' => 0.0, 'cache_read' => 0.075],
            'gpt-5-nano' => ['input' => 0.10, 'output' => 0.40, 'reasoning' => 0.0, 'cache_read' => 0.05],
            'gpt-5.4-mini' => ['input' => 0.75, 'output' => 4.50, 'reasoning' => 0.0, 'cache_read' => 0.075],
            'gemini-3-flash-preview' => ['input' => 0.50, 'output' => 3.00, 'reasoning' => 0.0, 'cache_read' => 0.05],
            'gemini-3.5-flash' => ['input' => 0.50, 'output' => 3.00, 'reasoning' => 0.0, 'cache_read' => 0.05],
            'gemini-3.1-pro-preview' => ['input' => 2.00, 'output' => 12.00, 'reasoning' => 0.0, 'cache_read' => 0.20],
        ],
    ],

    'ai_usage_preflight' => [
        'token_budget' => [
            'input' => 2_000,
            'output' => 1_000,
        ],
        'fallback_estimate' => 0.01,
    ],

    'tier_limits' => [
        'free' => [
            'rolling' => ['limit' => 0.40, 'period_hours' => 24],
            'weekly' => ['limit' => 1.40, 'period_days' => 7],
        ],
        'basic' => [
            'rolling' => ['limit' => 1.50, 'period_hours' => 24],
            'weekly' => ['limit' => 6.00, 'period_days' => 7],
        ],
        'plus' => [
            'rolling' => ['limit' => 3.00, 'period_hours' => 24],
            'weekly' => ['limit' => 12.00, 'period_days' => 7],
        ],
    ],
];
