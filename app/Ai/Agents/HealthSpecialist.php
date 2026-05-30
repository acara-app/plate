<?php

declare(strict_types=1);

namespace App\Ai\Agents;

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
        return 'Delegate personal health questions to a specialist that can read the user\'s logged health data and daily summaries, report health goals, predict glucose spikes for foods, and answer Health Sync / Apple Health companion-app questions. Pass a complete, self-contained task — the specialist cannot see the chat history. It does NOT log entries; use log_health_entry directly for that.';
    }

    protected function promptView(): string
    {
        return 'ai.prompts.health-specialist';
    }

    protected function toolConfigKey(): string
    {
        return 'plate.health_tools';
    }
}
