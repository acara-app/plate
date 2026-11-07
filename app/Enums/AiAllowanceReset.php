<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

enum AiAllowanceReset: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    public function periodStart(CarbonImmutable $now): CarbonImmutable
    {
        return match ($this) {
            self::Daily => $now->startOfDay(),
            self::Monthly => $now->startOfMonth(),
        };
    }

    public function nextReset(CarbonImmutable $periodStart): CarbonImmutable
    {
        return match ($this) {
            self::Daily => $periodStart->addDay(),
            self::Monthly => $periodStart->addMonth(),
        };
    }
}
