<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetDailyServingsByCalorie;
use App\Ai\Tools\SuggestMeal;
use Laravel\Ai\Attributes\Timeout;

#[Timeout(120)]
final class NutritionSpecialist extends SpecialistAgent
{
    public function name(): string
    {
        return 'nutrition_specialist';
    }

    public function description(): string
    {
        return 'Delegate nutrition and meal questions to a specialist that can suggest single meals, look up diet-specific reference material, USDA calorie-level guidelines, and daily serving tables. Pass a complete, self-contained task that includes any relevant user context (allergies, diet type, calorie target, what they asked) — the specialist cannot see the chat history. Do NOT use it to create multi-day meal plans; use meal_plan_specialist for that.';
    }

    protected function promptView(): string
    {
        return 'ai.prompts.nutrition-specialist';
    }

    protected function toolClasses(): array
    {
        return [
            SuggestMeal::class,
            GetCalorieLevelGuideline::class,
            GetDailyServingsByCalorie::class,
        ];
    }

    protected function includesSharedTools(): bool
    {
        return true;
    }
}
