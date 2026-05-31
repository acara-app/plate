<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use Laravel\Ai\Attributes\Timeout;

#[Timeout(120)]
final class FitnessSpecialist extends SpecialistAgent
{
    public function name(): string
    {
        return 'fitness_specialist';
    }

    public function description(): string
    {
        return 'Delegate fitness and wellness questions to a specialist that can build workout programs, suggest wellness routines (sleep, stress, mobility, recovery), and report the user\'s fitness goals. Pass a complete, self-contained task that includes any relevant user context (experience level, available equipment, goals, constraints) — the specialist cannot see the chat history.';
    }

    protected function promptView(): string
    {
        return 'ai.prompts.fitness-specialist';
    }

    protected function toolClasses(): array
    {
        return [
            SuggestWorkoutRoutine::class,
            SuggestWellnessRoutine::class,
            GetFitnessGoals::class,
        ];
    }
}
