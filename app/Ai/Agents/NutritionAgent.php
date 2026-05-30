<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Timeout;

#[Timeout(120)]
final class NutritionAgent extends SpecialistAgent
{
    public function name(): string
    {
        return 'nutrition_specialist';
    }

    public function description(): string
    {
        return 'Delegate nutrition and meal questions to a specialist that can suggest single meals, look up diet-specific reference material, USDA calorie-level guidelines, and daily serving tables. Pass a complete, self-contained task that includes any relevant user context (allergies, diet type, calorie target, what they asked) — the specialist cannot see the chat history. Do NOT use it to create multi-day meal plans; use create_meal_plan for that.';
    }

    protected function promptView(): string
    {
        return 'ai.prompts.nutrition-specialist';
    }

    protected function toolConfigKey(): string
    {
        return 'plate.nutrition_tools';
    }
}
