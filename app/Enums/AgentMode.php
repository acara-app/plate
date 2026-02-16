<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentMode: string
{
    case Ask = 'ask';
    case CreateMealPlan = 'create-meal-plan';
    case SuggestWellnessRoutine = 'suggest_wellness_routine';
    case SuggestWorkoutRoutine = 'suggest_workout_routine';
}
