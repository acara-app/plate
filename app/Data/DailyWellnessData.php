<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use Spatie\LaravelData\Data;

final class DailyWellnessData extends Data
{
    public function __construct(
        public int $steps,
        public int $stepGoal,
        public ?float $sleepHours,
        public bool $sleepSynced,
        public ?SleepQuality $sleepQuality,
        public ?StressLevel $stressLevel,
        public int $waterMl,
        public int $waterSyncedMl,
        public int $waterGoalMl,
        public ?float $syncedExerciseMinutes,
    ) {}
}
