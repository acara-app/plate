<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\AiAgents\GenerateMealPlan;
use App\DataObjects\PreviousDayContext;
use App\Enums\AiModel;
use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use App\Workflows\StoreDayMealsActivity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class GenerateMealDayJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutes per day

    public int $tries = 3;

    public function __construct(
        public readonly MealPlan $mealPlan,
        public readonly int $dayNumber,
        public readonly AiModel $aiModel = AiModel::Gemini25Flash,
    ) {
        //
    }

    public function handle(GenerateMealPlan $generateMealPlan, StoreDayMealsActivity $storeDayMeals): void
    {
        // Mark as generating
        $this->updateStatus(MealPlanGenerationStatus::Generating);

        // Build context from previously generated days
        $previousDaysContext = $this->buildPreviousDaysContext();

        // Generate meals for this day
        $dayMeals = $generateMealPlan->generateForDay(
            $this->mealPlan->user,
            $this->dayNumber,
            $this->aiModel,
            $this->mealPlan->duration_days,
            $previousDaysContext,
        );

        // Store the meals
        $storeDayMeals->execute($this->mealPlan, $dayMeals, $this->dayNumber);

        // Update metadata
        $daysCompleted = max(
            $this->mealPlan->metadata['days_completed'] ?? 0,
            $this->dayNumber
        );

        $isCompleted = $daysCompleted >= $this->mealPlan->duration_days;

        $this->mealPlan->update([
            'metadata' => array_merge($this->mealPlan->metadata ?? [], [
                'days_completed' => $daysCompleted,
                'status' => $isCompleted
                    ? MealPlanGenerationStatus::Completed->value
                    : MealPlanGenerationStatus::Pending->value,
                "day_{$this->dayNumber}_generated_at" => now()->toIso8601String(),
            ]),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->updateStatus(MealPlanGenerationStatus::Failed);
    }

    private function buildPreviousDaysContext(): PreviousDayContext
    {
        $context = new PreviousDayContext;

        // Get all existing meals grouped by day
        $existingMeals = $this->mealPlan->meals()
            ->where('day_number', '<', $this->dayNumber)
            ->get()
            ->groupBy('day_number');

        foreach ($existingMeals as $dayNum => $meals) {
            $mealNames = $meals->pluck('name')->toArray();
            $context->addDayMeals($dayNum, $mealNames);
        }

        return $context;
    }

    private function updateStatus(MealPlanGenerationStatus $status): void
    {
        $this->mealPlan->update([
            'metadata' => array_merge($this->mealPlan->metadata ?? [], [
                'status' => $status->value,
                "day_{$this->dayNumber}_status" => $status->value,
            ]),
        ]);
    }
}
