<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\AggregateDailyMealEstimates;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Recompute the meal estimate accuracy counts of every UTC day still kept in meal_estimates, which meal-estimates:report combines')]
#[Signature('meal-estimates:aggregate-daily')]
final class AggregateDailyMealEstimatesCommand extends Command
{
    public function handle(AggregateDailyMealEstimates $aggregate): int
    {
        $aggregate->handle();

        $this->info('Stored daily meal estimate accuracy for every day still kept in meal_estimates.');

        return self::SUCCESS;
    }
}
