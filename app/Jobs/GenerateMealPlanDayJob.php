<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\PreviousDayContext;
use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use App\Workflows\MealPlanPipeline\BuildPreviousDaysContextStep;
use App\Workflows\MealPlanPipeline\FinalizeMealPlanDayStep;
use App\Workflows\MealPlanPipeline\GenerateDayMealsStep;
use App\Workflows\MealPlanPipeline\MealPlanDayContext;
use App\Workflows\MealPlanPipeline\SaveDayMealsStep;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Pipeline;
use Throwable;

#[Timeout(300)]
#[Backoff(30)]
#[Tries(2)]
#[UniqueFor(300)]
final class GenerateMealPlanDayJob implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MealPlan $mealPlan,
        public readonly int $dayNumber,
    ) {}

    public function uniqueId(): string
    {
        return 'meal-plan-day:'.$this->mealPlan->id.':'.$this->dayNumber;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->uniqueId()),
        ];
    }

    public function handle(): void
    {
        Pipeline::send(new MealPlanDayContext(
            user: $this->mealPlan->user,
            mealPlan: $this->mealPlan,
            dayNumber: $this->dayNumber,
            totalDays: $this->mealPlan->duration_days,
            previousDaysContext: new PreviousDayContext,
        ))->through([
            BuildPreviousDaysContextStep::class,
            GenerateDayMealsStep::class,
            SaveDayMealsStep::class,
            FinalizeMealPlanDayStep::class,
        ])->thenReturn();
    }

    public function failed(Throwable $exception): void
    {
        $dayStatusKey = sprintf('day_%d_status', $this->dayNumber);

        $this->mealPlan->update([
            'metadata' => array_merge($this->mealPlan->metadata ?? [], [
                $dayStatusKey => MealPlanGenerationStatus::Failed->value,
            ]),
        ]);
    }
}
