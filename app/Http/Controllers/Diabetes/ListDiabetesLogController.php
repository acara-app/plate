<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Actions\GetUserDiabetesLogsAction;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\InsulinType;
use App\Models\DiabetesLog;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;

final readonly class ListDiabetesLogController
{
    public function __construct(
        private GetUserDiabetesLogsAction $getUserDiabetesLogs,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke()
    {
        $user = $this->currentUser;

        $logs = $this->getUserDiabetesLogs->handle($user);

        return Inertia::render('diabetes-log/index', [
            'logs' => $logs,
            'glucoseReadingTypes' => collect(GlucoseReadingType::cases())->map(fn (GlucoseReadingType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
            'insulinTypes' => collect(InsulinType::cases())->map(fn (InsulinType $type): array => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ]),
            'glucoseUnit' => $user->profile?->units_preference->value ?? GlucoseUnit::MmolL->value,
            'recentMedications' => $this->getRecentMedications($user),
            'recentInsulins' => $this->getRecentInsulins($user),
            'todaysMeals' => $this->getTodaysMeals($user),
        ]);
    }

    /**
     * Get recent unique medications from user's diabetes logs for quick-add chips.
     *
     * @return array<int, array{name: string, dosage: string, label: string}>
     */
    private function getRecentMedications(User $user): array
    {
        /** @var array<int, array{name: string, dosage: string, label: string}> */
        return $user->diabetesLogs()
            ->whereNotNull('medication_name')
            ->whereNotNull('medication_dosage')
            ->latest()
            ->get(['medication_name', 'medication_dosage'])
            ->unique(fn (DiabetesLog $log): string => "{$log->medication_name}|{$log->medication_dosage}")
            ->take(5)
            ->map(fn (DiabetesLog $log): array => [
                'name' => (string) $log->medication_name,
                'dosage' => (string) $log->medication_dosage,
                'label' => "{$log->medication_name} {$log->medication_dosage}",
            ])
            ->values()
            ->all();
    }

    /**
     * Get recent unique insulin entries from user's diabetes logs for quick-add chips.
     *
     * @return array<int, array{units: float, type: string, label: string}>
     */
    private function getRecentInsulins(User $user): array
    {
        /** @var array<int, array{units: float, type: string, label: string}> */
        return $user->diabetesLogs()
            ->whereNotNull('insulin_units')
            ->whereNotNull('insulin_type')
            ->latest()
            ->get(['insulin_units', 'insulin_type'])
            ->unique(fn (DiabetesLog $log): string => "{$log->insulin_units}|{$log->insulin_type?->value}")
            ->take(5)
            ->map(fn (DiabetesLog $log): array => [
                'units' => (float) $log->insulin_units,
                'type' => (string) $log->insulin_type?->value,
                'label' => "{$log->insulin_units}u {$log->insulin_type?->value}",
            ])
            ->values()
            ->all();
    }

    /**
     * Get today's meals from active meal plan for quick carb import.
     *
     * @return array<int, array{id: int, name: string, type: string, carbs: float, label: string}>
     */
    private function getTodaysMeals(User $user): array
    {
        $mealPlan = $user->mealPlans()
            ->latest()
            ->first();

        if ($mealPlan === null) {
            return [];
        }

        // Calculate what day of the meal plan today is
        $startDate = $mealPlan->created_at->startOfDay();
        $today = today();
        $dayNumber = (int) $startDate->diffInDays($today) + 1;

        // Clamp to valid range (edge case: meal plan older than duration_days)
        if ($dayNumber < 1 || $dayNumber > $mealPlan->duration_days) {
            $dayNumber = (($dayNumber - 1) % $mealPlan->duration_days) + 1; // @codeCoverageIgnore
        }

        /** @var array<int, array{id: int, name: string, type: string, carbs: float, label: string}> */
        return $mealPlan->mealsForDay($dayNumber)
            ->map(fn (Meal $meal): array => [
                'id' => $meal->id,
                'name' => (string) $meal->name,
                'type' => ucfirst((string) $meal->type->value),
                'carbs' => (float) ($meal->carbs_grams ?? 0),
                'label' => ucfirst((string) $meal->type->value).' - '.($meal->carbs_grams ?? 0).'g carbs',
            ])
            ->values()
            ->all();
    }
}
