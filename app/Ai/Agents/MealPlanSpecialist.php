<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Timeout;

#[Timeout(120)]
final class MealPlanSpecialist extends SpecialistAgent
{
    public function name(): string
    {
        return 'meal_plan_specialist';
    }

    public function description(): string
    {
        return 'Delegate explicit multi-day meal plan requests to a specialist that creates the user\'s structured meal plan. Pass a complete, self-contained task with requested day count, dietary constraints, goals, and any custom preferences — the specialist cannot see the chat history.';
    }

    protected function promptView(): string
    {
        return 'ai.prompts.meal-plan-specialist';
    }

    protected function toolConfigKey(): string
    {
        return 'plate.meal_plan_tools';
    }
}
