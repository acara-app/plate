<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\StartMealPlanGeneration;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\HasSkills;

#[MaxSteps(4)]
#[Timeout(120)]
final class MealPlanSpecialist extends SpecialistAgent implements HasSkills
{
    public function name(): string
    {
        return 'meal_plan_specialist';
    }

    public function description(): string
    {
        return 'Delegate explicit multi-day meal plan requests to a specialist that creates the user\'s structured meal plan. Pass a complete, self-contained task with requested day count, dietary constraints, goals, and any custom preferences — the specialist cannot see the chat history.';
    }

    /** @return list<string> */
    public function skills(): array
    {
        return [resource_path('skills/diets')];
    }

    protected function promptView(): string
    {
        return 'ai.prompts.meal-plan-specialist';
    }

    protected function toolClasses(): array
    {
        return [
            StartMealPlanGeneration::class,
        ];
    }

    protected function includesSharedTools(): bool
    {
        return true;
    }
}
