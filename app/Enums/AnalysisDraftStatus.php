<?php

declare(strict_types=1);

namespace App\Enums;

enum AnalysisDraftStatus: string
{
    case Restored = 'restored';
    case Expired = 'expired';
    case Invalid = 'invalid';
    case Consumed = 'consumed';
    case ClaimedByOther = 'claimed_by_other';
}
