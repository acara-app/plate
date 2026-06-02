<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\PreviousDayContext;
use App\Enums\DietType;
use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use App\Models\User;
use App\Workflows\MealPlanPipeline\FinalizeInitialMealPlanStep;
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
use Illuminate\Support\Facades\Pipeline;
use Throwable;

#[Timeout(1800)]
#[Backoff(30)]
#[Tries(2)]
#[UniqueFor(1800)]
final class GenerateInitialMealPlanJob implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $user,
        public readonly MealPlan $mealPlan,
        public readonly ?GlucoseAnalysisData $glucoseAnalysis = null,
        public readonly ?DietType $dietType = null,
    ) {}

    public function uniqueId(): string
    {
        return 'meal-plan-init:'.$this->mealPlan->id;
    }

    public function handle(): void
    {
        Pipeline::send(new MealPlanDayContext(
            user: $this->user,
            mealPlan: $this->mealPlan,
            dayNumber: 1,
            totalDays: $this->mealPlan->duration_days,
            previousDaysContext: new PreviousDayContext,
            glucoseAnalysis: $this->glucoseAnalysis,
            dietType: $this->dietType,
        ))->through([
            GenerateDayMealsStep::class,
            SaveDayMealsStep::class,
            FinalizeInitialMealPlanStep::class,
        ])->thenReturn();
    }

    public function failed(Throwable $exception): void
    {
        $this->mealPlan->update([
            'metadata' => array_merge($this->mealPlan->metadata ?? [], [
                'status' => MealPlanGenerationStatus::Failed->value,
            ]),
        ]);
    }
}
