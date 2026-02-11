<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Ai\SystemPromptProvider;
use App\Enums\DietType;
use App\Services\SystemPromptProviders\BalancedMealPlanSystemProvider;
use App\Services\SystemPromptProviders\DashMealPlanSystemProvider;
use App\Services\SystemPromptProviders\KetoMealPlanSystemProvider;
use App\Services\SystemPromptProviders\LowCarbMealPlanSystemProvider;
use App\Services\SystemPromptProviders\MediterraneanMealPlanSystemProvider;
use App\Services\SystemPromptProviders\PaleoMealPlanSystemProvider;
use App\Services\SystemPromptProviders\VeganMealPlanSystemProvider;
use App\Services\SystemPromptProviders\VegetarianMealPlanSystemProvider;

final readonly class SystemPromptProviderResolver
{
    /**
     * Resolve the appropriate SystemPromptProvider for the given DietType.
     */
    public function resolve(DietType $dietType): SystemPromptProvider
    {
        return match ($dietType) {
            DietType::Mediterranean => new MediterraneanMealPlanSystemProvider($dietType),
            DietType::LowCarb => new LowCarbMealPlanSystemProvider($dietType),
            DietType::Keto => new KetoMealPlanSystemProvider($dietType),
            DietType::Dash => new DashMealPlanSystemProvider($dietType),
            DietType::Vegetarian => new VegetarianMealPlanSystemProvider($dietType),
            DietType::Vegan => new VeganMealPlanSystemProvider($dietType),
            DietType::Paleo => new PaleoMealPlanSystemProvider($dietType),
            DietType::Balanced => new BalancedMealPlanSystemProvider($dietType),
        };
    }
}
