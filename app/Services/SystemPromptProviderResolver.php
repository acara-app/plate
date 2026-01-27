<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Contracts\SystemPromptProvider;
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
            DietType::Mediterranean => new MediterraneanMealPlanSystemProvider(),
            DietType::LowCarb => new LowCarbMealPlanSystemProvider(),
            DietType::Keto => new KetoMealPlanSystemProvider(),
            DietType::Dash => new DashMealPlanSystemProvider(),
            DietType::Vegetarian => new VegetarianMealPlanSystemProvider(),
            DietType::Vegan => new VeganMealPlanSystemProvider(),
            DietType::Paleo => new PaleoMealPlanSystemProvider(),
            DietType::Balanced => new BalancedMealPlanSystemProvider(),
        };
    }
}
