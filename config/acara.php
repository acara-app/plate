<?php

declare(strict_types=1);

use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Ai\Tools\GetSubscriptionStatus;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\UpdateHouseholdContext;
use App\Ai\Tools\UpdateUserBiometrics;
use App\Ai\Tools\UpdateUserProfileAttributes;
use Laravel\Ai\Providers\Tools\WebSearch;

return [
    'privacy_policy_version' => '2026-11-22',

    'terms_version' => '2026-11-14',

    'chat' => [
        'temporary_retention_hours' => (int) env('CHAT_TEMPORARY_RETENTION_HOURS', 48),
    ],

    'altani' => [
        'context' => [
            'history_limit' => 50,
            'recent_summaries' => 3,
        ],

        'summarization' => [
            'threshold' => 20,
            'buffer' => 25,
            'timeout' => 90,
        ],
    ],

    'snap' => [
        'free_trial_scans' => 2,
        'cost_alert_usd' => 0.03,
        'journey_days' => 7,
        'guest_cookie_days' => 365,
    ],

    'health_sync' => [
        'app_store_url' => (string) env('HEALTH_SYNC_APP_STORE_URL', 'https://apps.apple.com/us/app/acara-health-sync/id6761504525'),
        'minimum_ios_version' => (string) env('HEALTH_SYNC_MIN_IOS_VERSION', '18.0'),
    ],

    'tools' => [
        GetUserProfile::class,
        GetSubscriptionStatus::class,
        LogHealthEntry::class,
        EnrichAttributeMetadata::class,
        UpdateUserProfileAttributes::class,
        UpdateUserBiometrics::class,
        UpdateHouseholdContext::class,
    ],

    'shared_tools' => [],

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

    'credit_multiplier' => 1_000,

    'ai_call_estimate_usd' => 0.02,

    'ai_allowance' => [
        'free' => ['usd' => 0.50, 'resets' => 'monthly'],
        'starter' => ['usd' => 0.75, 'resets' => 'daily'],
        'snap' => ['usd' => 7.50, 'resets' => 'daily'],
        'basic' => ['usd' => 7.50, 'resets' => 'daily'],
        'plus' => ['usd' => 7.50, 'resets' => 'daily'],
    ],
];
