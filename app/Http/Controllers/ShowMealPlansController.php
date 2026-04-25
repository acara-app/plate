<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\MealResponseData;
use App\Enums\DietType;
use App\Enums\MealPlanGenerationStatus;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
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

        $userDietType = $this->user->profile?->calculated_diet_type?->value
            ?? DietType::Balanced->value;
        $dietTypes = DietType::toArray();

        if (! $mealPlan) {
            return Inertia::render('meal-plans/show', [
                'mealPlan' => null,
                'currentDay' => null,
                'navigation' => null,
                'userDietType' => $userDietType,
                'dietTypes' => $dietTypes,
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

        $avgMacros = $mealPlan->macroRatiosForDay($dayMeals);

        $dayName = $dayMeals->first()?->getDayName() ?? 'Day '.$currentDayNumber;

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
            'meals' => $dayMeals->map(fn (Meal $meal): MealResponseData => MealResponseData::fromMeal($meal)),
            'daily_stats' => $dailyStats,
        ];

        $navigation = [
            'has_previous' => true,
            'has_next' => true,
            'previous_day' => $currentDayNumber > 1 ? $currentDayNumber - 1 : $mealPlan->duration_days,
            'next_day' => $currentDayNumber < $mealPlan->duration_days ? $currentDayNumber + 1 : 1,
            'total_days' => $mealPlan->duration_days,
        ];

        return Inertia::render('meal-plans/show', [
            'mealPlan' => $formattedMealPlan,
            'currentDay' => $currentDay,
            'navigation' => $navigation,
            'userDietType' => $userDietType,
            'dietTypes' => $dietTypes,
        ]);
    }

    private function getDayStatus(MealPlan $mealPlan, int $dayNumber, bool $isEmpty): string
    {
        /** @var array<string, mixed> $metadata */
        $metadata = $mealPlan->metadata ?? [];

        $dayStatusKey = sprintf('day_%d_status', $dayNumber);
        if (isset($metadata[$dayStatusKey]) && is_string($metadata[$dayStatusKey])) {
            if ($metadata[$dayStatusKey] === MealPlanGenerationStatus::Generating->value
                && $this->isStaleGenerating($mealPlan)) {
                return MealPlanGenerationStatus::Failed->value;
            }

            return $metadata[$dayStatusKey];
        }

        if (! $isEmpty) {
            return MealPlanGenerationStatus::Completed->value;
        }

        $overallStatus = $metadata['status'] ?? '';

        if ($overallStatus === MealPlanGenerationStatus::Generating->value) {
            if ($this->isStaleGenerating($mealPlan)) {
                return MealPlanGenerationStatus::Failed->value;
            }

            return MealPlanGenerationStatus::Generating->value;
        }

        if ($overallStatus === MealPlanGenerationStatus::Failed->value) {
            return MealPlanGenerationStatus::Failed->value;
        }

        return MealPlanGenerationStatus::Pending->value;
    }

    private function isStaleGenerating(MealPlan $mealPlan): bool
    {
        return $mealPlan->updated_at->isBefore(now()->subMinutes(30));
    }
}
