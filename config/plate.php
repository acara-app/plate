<?php

declare(strict_types=1);

use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetDailyServingsByCalorie;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthEntries;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Ai\Tools\SuggestSingleMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Ai\Tools\UpdateHouseholdContext;
use App\Ai\Tools\UpdateUserBiometrics;
use App\Ai\Tools\UpdateUserProfileAttributes;
use Laravel\Ai\Providers\Tools\WebSearch;

return [
    'enable_premium_upgrades' => env('PLATE_ENABLE_PREMIUM_UPGRADES', false),
    'telegram_bot_username' => env('TELEGRAM_BOT_USERNAME', 'AcaraPlate_bot'),

    'tools' => [
        SuggestSingleMeal::class,
        GetUserProfile::class,
        CreateMealPlan::class,
        GetCalorieLevelGuideline::class,
        GetDailyServingsByCalorie::class,
        PredictGlucoseSpike::class,
        SuggestWellnessRoutine::class,
        SuggestWorkoutRoutine::class,
        GetHealthGoals::class,
        GetHealthEntries::class,
        LogHealthEntry::class,
        GetFitnessGoals::class,
        GetDietReference::class,
        EnrichAttributeMetadata::class,
        UpdateUserProfileAttributes::class,
        UpdateUserBiometrics::class,
        UpdateHouseholdContext::class,
    ],

    'image_tools' => [
        AnalyzePhoto::class,
    ],

    'meal_plan_tools' => [
        GetDietReference::class,
    ],

    'provider_tools' => [
        WebSearch::class,
    ],

    // Converts internal cost units to user-facing credits (1 unit = 1,000 credits).
    'credit_multiplier' => 1_000,

    // Limits are stored as internal cost units; displayed as credits to users.
    'ai_usage_limits' => [
        'rolling' => [
            'limit' => 0.5,
            'period_hours' => 24,
        ],
        'weekly' => [
            'limit' => 2.5,
            'period_days' => 7,
        ],
        'monthly' => [
            'limit' => 5,
            'period_days' => 30,
        ],
    ],
];
