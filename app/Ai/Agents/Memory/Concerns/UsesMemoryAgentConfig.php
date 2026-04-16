<?php

declare(strict_types=1);

namespace App\Ai\Agents\Memory\Concerns;

trait UsesMemoryAgentConfig
{
    public function provider(): string
    {
        $configured = config('memory.ai_agent.provider');

        return is_string($configured) && $configured !== '' ? $configured : 'gemini';
    }

    public function model(): ?string
    {
        $configured = config('memory.ai_agent.model');

        return is_string($configured) && $configured !== '' ? $configured : null;
    }
}
