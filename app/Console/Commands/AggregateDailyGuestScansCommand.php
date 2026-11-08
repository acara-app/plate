<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Billing\AggregateDailyGuestScans;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Recompute the anonymous daily totals of how guests use their free scans, for every UTC day whose guest scan records are still kept')]
#[Signature('snap:aggregate-guest-scans')]
final class AggregateDailyGuestScansCommand extends Command
{
    public function handle(AggregateDailyGuestScans $aggregate): int
    {
        $aggregate->handle();

        $this->info('Stored daily guest scan totals for every day whose guest scan records are still kept.');

        return self::SUCCESS;
    }
}
