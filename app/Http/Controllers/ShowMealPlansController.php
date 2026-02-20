<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\MealPlanGenerationStatus;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Workflows\MealPlanDayWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Workflow\WorkflowStub;

final readonly class ShowMealPlansController
{
    public function __construct(
        #[CurrentUser] private User $user
    ) {
        //
    }

    public function __invoke(Request $request): Response
    {
        $mealPlan = $this->user->mealPlans()
            ->latest()
            ->first();

        if (! $mealPlan) {
            return Inertia::render('meal-plans/show', [
                'mealPlan' => null,
                'currentDay' => null,
                'navigation' => null,
            ]);
        }

        /** @var string $timezone */
        $timezone = $request->session()->get('timezone', 'UTC');
        $now = CarbonImmutable::now($timezone);

        $dayOfWeek = $now->dayOfWeekIso;
        $defaultDay = $dayOfWeek <= $mealPlan->duration_days ? $dayOfWeek : 1;

        $currentDayNumber = $request->integer('day', $defaultDay);
        $currentDayNumber = max(1, min($mealPlan->duration_days, $currentDayNumber));

        $mealPlan->load(['meals' => function (mixed $query) use ($currentDayNumber): void {
            /** @var HasMany<Meal, MealPlan> $query */
            $query->where('day_number', $currentDayNumber)
                ->orderBy('sort_order')
                ->oldest();
        }]);

        /** @var Collection<int, Meal> $dayMeals */
        $dayMeals = $mealPlan->meals;

        $dailyStats = [
            'total_calories' => $dayMeals->sum('calories'),
            'protein' => $dayMeals->sum('protein_grams'),
            'carbs' => $dayMeals->sum('carbs_grams'),
            'fat' => $dayMeals->sum('fat_grams'),
        ];

        $avgMacros = null;
        if ($mealPlan->macronutrient_ratios) {
            $avgMacros = $mealPlan->macronutrient_ratios;
        } elseif ($dayMeals->whereNotNull('protein_grams')->isNotEmpty()) {
            $totalProteinCals = $dayMeals->sum(fn (Meal $m): float => ($m->protein_grams ?? 0) * 4);
            $totalCarbsCals = $dayMeals->sum(fn (Meal $m): float => ($m->carbs_grams ?? 0) * 4);
            $totalFatCals = $dayMeals->sum(fn (Meal $m): float => ($m->fat_grams ?? 0) * 9);
            $totalMacroCals = $totalProteinCals + $totalCarbsCals + $totalFatCals;

            if ($totalMacroCals > 0) {
                $avgMacros = [
                    'protein' => round(($totalProteinCals / $totalMacroCals) * 100, 1),
                    'carbs' => round(($totalCarbsCals / $totalMacroCals) * 100, 1),
                    'fat' => round(($totalFatCals / $totalMacroCals) * 100, 1),
                ];
            }
        }

        $dayName = $dayMeals->first()?->getDayName() ?? 'Day ' . $currentDayNumber;

        $formattedMealPlan = [
            'id' => $mealPlan->id,
            'name' => $mealPlan->name,
            'description' => $mealPlan->description,
            'type' => $mealPlan->type->value,
            'duration_days' => $mealPlan->duration_days,
            'target_daily_calories' => $mealPlan->target_daily_calories,
            'macronutrient_ratios' => $avgMacros,
            'metadata' => $mealPlan->metadata,
            'created_at' => $mealPlan->created_at->toISOString(),
        ];

        $dayNeedsGeneration = $dayMeals->isEmpty();
        $dayStatus = $this->getDayStatus($mealPlan, $currentDayNumber, $dayMeals->isEmpty());

        // Auto-trigger generation for pending days
        if ($dayNeedsGeneration && $dayStatus === MealPlanGenerationStatus::Pending->value) {
            $mealPlan->update([
                'metadata' => array_merge($mealPlan->metadata ?? [], [
                    sprintf('day_%d_status', $currentDayNumber) => MealPlanGenerationStatus::Generating->value,
                ]),
            ]);

            WorkflowStub::make(MealPlanDayWorkflow::class)
                ->start($mealPlan, $currentDayNumber);

            $dayStatus = MealPlanGenerationStatus::Generating->value;
        }

        $currentDay = [
            'day_number' => $currentDayNumber,
            'day_name' => $dayName,
            'needs_generation' => $dayNeedsGeneration,
            'status' => $dayStatus,
            'meals' => $dayMeals->map(fn (Meal $meal): array => [
                'id' => $meal->id,
                'type' => $meal->type->value,
                'name' => $meal->name,
                'description' => $meal->description,
                'preparation_instructions' => $meal->preparation_instructions,
                'ingredients' => $meal->ingredients,
                'portion_size' => $meal->portion_size,
                'calories' => (float) $meal->calories,
                'protein_grams' => $meal->protein_grams ? (float) $meal->protein_grams : null,
                'carbs_grams' => $meal->carbs_grams ? (float) $meal->carbs_grams : null,
                'fat_grams' => $meal->fat_grams ? (float) $meal->fat_grams : null,
                'preparation_time_minutes' => $meal->preparation_time_minutes,
                'macro_percentages' => $meal->macroPercentages(),
            ]),
            'daily_stats' => $dailyStats,
        ];

        $navigation = [
            'has_previous' => true, // Always enabled with looping
            'has_next' => true, // Always enabled with looping
            'previous_day' => $currentDayNumber > 1 ? $currentDayNumber - 1 : $mealPlan->duration_days,
            'next_day' => $currentDayNumber < $mealPlan->duration_days ? $currentDayNumber + 1 : 1,
            'total_days' => $mealPlan->duration_days,
        ];

        return Inertia::render('meal-plans/show', [
            'mealPlan' => $formattedMealPlan,
            'currentDay' => $currentDay,
            'navigation' => $navigation,
        ]);
    }

    /**
     * Get the generation status for a specific day.
     */
    private function getDayStatus(MealPlan $mealPlan, int $dayNumber, bool $isEmpty): string
    {
        /** @var array<string, mixed> $metadata */
        $metadata = $mealPlan->metadata ?? [];

        $dayStatusKey = sprintf('day_%d_status', $dayNumber);
        if (isset($metadata[$dayStatusKey]) && is_string($metadata[$dayStatusKey])) {
            return $metadata[$dayStatusKey];
        }

        if (! $isEmpty) {
            return MealPlanGenerationStatus::Completed->value;
        }

        if (($metadata['status'] ?? '') === MealPlanGenerationStatus::Generating->value) {
            return MealPlanGenerationStatus::Generating->value;
        }

        return MealPlanGenerationStatus::Pending->value;
    }
}
