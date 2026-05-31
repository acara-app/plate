<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\GetHealthData;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetHealthSummary;
use App\Ai\Tools\GetHealthSyncSupport;
use Laravel\Ai\Attributes\Timeout;

#[Timeout(120)]
final class HealthSpecialist extends SpecialistAgent
{
    public function name(): string
    {
        return 'health_specialist';
    }

    public function description(): string
    {
        return 'Delegate personal health questions to a specialist that can read the user\'s logged health data and daily summaries, report health goals, and answer Health Sync / Apple Health companion-app questions. Pass a complete, self-contained task — the specialist cannot see the chat history. It does NOT log entries; use log_health_entry directly for that.';
    }

    protected function promptView(): string
    {
        return 'ai.prompts.health-specialist';
    }

    protected function toolClasses(): array
    {
        return [
            GetHealthData::class,
            GetHealthSummary::class,
            GetHealthGoals::class,
            GetHealthSyncSupport::class,
        ];
    }
}
