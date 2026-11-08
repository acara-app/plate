<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateDailyMealEstimates;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Store the meal estimate accuracy counts of each UTC day, which meal-estimates:report combines')]
#[Signature('meal-estimates:aggregate-daily
        {--days=7 : Re-aggregate the last N UTC days, so late corrections are counted}
        {--to= : Last UTC day to aggregate (Y-m-d), defaults to yesterday}')]
final class AggregateDailyMealEstimatesCommand extends Command
{
    public function handle(AggregateDailyMealEstimates $aggregate): int
    {
        $to = is_string($this->option('to')) ? CarbonImmutable::parse($this->option('to'), 'UTC')->startOfDay() : CarbonImmutable::yesterday('UTC');
        $from = $to->subDays(max(1, (int) $this->option('days')) - 1);

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $aggregate->handle($day);
        }

        $this->info("Stored meal estimate accuracy for {$from->toDateString()} → {$to->toDateString()} (UTC days).");

        return self::SUCCESS;
    }
}
