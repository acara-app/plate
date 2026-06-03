<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentStreamStatus: string
{
    case Queued = 'queued';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }
}
