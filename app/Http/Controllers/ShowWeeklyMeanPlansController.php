<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Enums\MealPlanType;
use App\Jobs\ProcessMealPlanJob;
use App\Models\Meal;
use App\Models\MealPlan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowWeeklyMeanPlansController
{
    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $currentDayNumber = $request->integer('day', CarbonImmutable::now()->dayOfWeekIso);

        $currentDayNumber = max(1, min(7, $currentDayNumber));

        $mealPlan = MealPlan::query()
            ->where('user_id', $user->id)
            ->where('type', MealPlanType::Weekly)
            ->with(['meals' => function (mixed $query) use ($currentDayNumber): void {
                /** @var HasMany<Meal, MealPlan> $query */
                $query->where('day_number', $currentDayNumber)
                    ->orderBy('sort_order')
                    ->oldest();
            }])
            ->latest()
            ->first();

        /** @var \App\Models\JobTracking|null $latestJobTracking */
        $latestJobTracking = $user->jobTrackings()
            ->where('job_type', ProcessMealPlanJob::JOB_TYPE)
            ->whereIn('status', [JobStatus::Pending, JobStatus::Processing])
            ->latest()
            ->first();

        if (! $mealPlan) {

            return Inertia::render('meal-plans/weekly/show-weekly-plan', [
                'mealPlan' => null,
                'currentDay' => null,
                'navigation' => null,
                'jobTracking' => $this->formatJobTracking($latestJobTracking),
                'requiresSubscription' => false,
            ]);
        }

        /** @var Collection<int, Meal> $dayMeals */
        $dayMeals = $mealPlan->meals;

        // Calculate daily stats for current day
        $dailyStats = [
            'total_calories' => $dayMeals->sum('calories'),
            'protein' => $dayMeals->sum('protein_grams'),
            'carbs' => $dayMeals->sum('carbs_grams'),
            'fat' => $dayMeals->sum('fat_grams'),
        ];

        // Calculate average macros for the plan
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

        // Get day name for current day
        $dayName = $dayMeals->first()?->getDayName() ?? "Day {$currentDayNumber}";

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

        $currentDay = [
            'day_number' => $currentDayNumber,
            'day_name' => $dayName,
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

        // Navigation info with looping
        $navigation = [
            'has_previous' => true, // Always enabled with looping
            'has_next' => true, // Always enabled with looping
            'previous_day' => $currentDayNumber > 1 ? $currentDayNumber - 1 : $mealPlan->duration_days,
            'next_day' => $currentDayNumber < $mealPlan->duration_days ? $currentDayNumber + 1 : 1,
            'total_days' => $mealPlan->duration_days,
        ];

        return Inertia::render('meal-plans/weekly/show-weekly-plan', [
            'mealPlan' => $formattedMealPlan,
            'currentDay' => $currentDay,
            'navigation' => $navigation,
            'jobTracking' => $this->formatJobTracking($latestJobTracking),
            'requiresSubscription' => ! $user->hasActiveSubscription(),
        ]);
    }

    /**
     * Format job tracking data for the frontend
     *
     * @param  \App\Models\JobTracking|null  $jobTracking
     * @return array{status: string, progress: int, message: string|null}|null
     */
    private function formatJobTracking(mixed $jobTracking): ?array
    {
        if (! $jobTracking) {
            return null;
        }

        return [
            'status' => $jobTracking->status->value,
            'progress' => (int) $jobTracking->progress,
            'message' => $jobTracking->message,
        ];
    }
}
