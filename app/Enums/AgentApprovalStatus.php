<?php

declare(strict_types=1);

namespace App\Enums;

enum AgentApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Executing = 'executing';
    case Executed = 'executed';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function canApprove(): bool
    {
        return $this === self::Pending;
    }

    public function canReject(): bool
    {
        return $this === self::Pending;
    }

    public function isInFlight(): bool
    {
        return in_array($this, [self::Approved, self::Executing], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Executed, self::Failed, self::Rejected, self::Expired], true);
    }
}
