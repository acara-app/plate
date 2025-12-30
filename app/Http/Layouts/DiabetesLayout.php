<?php

declare(strict_types=1);

namespace App\Http\Layouts;

use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\InsulinType;
use App\Models\DiabetesLog;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class DiabetesLayout
{
    /**
     * Valid time periods for filtering logs.
     *
     * @var array<string, int>
     */
    public const array TIME_PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    /**
     * Get common props for diabetes log views.
     *
     * @return array{
     *     glucoseReadingTypes: Collection<int, array{value: string, label: string}>,
     *     insulinTypes: Collection<int, array{value: string, label: string}>,
     *     glucoseUnit: string,
     *     recentMedications: array<int, array{name: string, dosage: string, label: string}>,
     *     recentInsulins: array<int, array{units: float, type: string, label: string}>,
     *     todaysMeals: array<int, array{id: int, name: string, type: string, carbs: float, label: string}>
     * }
     */
    public static function props(User $user): array
    {
        return [
            'glucoseReadingTypes' => collect(GlucoseReadingType::cases())->map(fn (GlucoseReadingType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
            'insulinTypes' => collect(InsulinType::cases())->map(fn (InsulinType $type): array => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ]),
            'glucoseUnit' => $user->profile?->units_preference->value ?? GlucoseUnit::MmolL->value,
            'recentMedications' => self::getRecentMedications($user),
            'recentInsulins' => self::getRecentInsulins($user),
            'todaysMeals' => self::getTodaysMeals($user),
        ];
    }

    /**
     * Get filtered logs and calculated summary for dashboard.
     *
     * @return array{
     *     logs: Collection<int, DiabetesLog>,
     *     timePeriod: string,
     *     summary: array<string, mixed>
     * }
     */
    public static function dashboardData(User $user, string $timePeriod = '30d'): array
    {
        // Validate and default time period
        if (! array_key_exists($timePeriod, self::TIME_PERIODS)) {
            $timePeriod = '30d';
        }

        $days = self::TIME_PERIODS[$timePeriod];
        $cutoffDate = now()->subDays($days);
        $logs = $user->diabetesLogs()
            ->where('measured_at', '>=', $cutoffDate)
            ->latest('measured_at')
            ->get();

        $allLogs = $user->diabetesLogs()
            ->latest('measured_at')
            ->get();

        return [
            'logs' => $logs,
            'timePeriod' => $timePeriod,
            'summary' => self::calculateSummary($logs, $allLogs),
        ];
    }

    /**
     * Get recent unique medications from user's diabetes logs for quick-add chips.
     *
     * @return array<int, array{name: string, dosage: string, label: string}>
     */
    public static function getRecentMedications(User $user): array
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
    public static function getRecentInsulins(User $user): array
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
    public static function getTodaysMeals(User $user): array
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

    /**
     * Calculate all summary statistics for the dashboard.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @param  Collection<int, DiabetesLog>  $allLogs
     * @return array<string, mixed>
     */
    private static function calculateSummary(Collection $logs, Collection $allLogs): array
    {
        return [
            'glucoseStats' => self::calculateGlucoseStats($logs),
            'insulinStats' => self::calculateInsulinStats($logs),
            'carbStats' => self::calculateCarbStats($logs),
            'exerciseStats' => self::calculateExerciseStats($logs),
            'weightStats' => self::calculateWeightStats($logs),
            'bpStats' => self::calculateBloodPressureStats($logs),
            'medicationStats' => self::calculateMedicationStats($logs),
            'a1cStats' => self::calculateA1cStats($logs),
            'streakStats' => self::calculateStreak($allLogs),
            'dataTypes' => self::calculateDataTypes($logs),
        ];
    }

    /**
     * Calculate glucose statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, avg: float, min: float, max: float}
     */
    private static function calculateGlucoseStats(Collection $logs): array
    {
        $glucoseLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->glucose_value !== null);
        $values = $glucoseLogs->pluck('glucose_value')->filter()->values();

        if ($values->isEmpty()) {
            return ['count' => 0, 'avg' => 0, 'min' => 0, 'max' => 0];
        }

        return [
            'count' => $values->count(),
            'avg' => round((float) ($values->avg()), 1),
            'min' => round((float) ($values->min()), 1), // @phpstan-ignore argument.type
            'max' => round((float) ($values->max()), 1), // @phpstan-ignore argument.type
        ];
    }

    /**
     * Calculate insulin statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, total: float, bolusCount: int, basalCount: int}
     */
    private static function calculateInsulinStats(Collection $logs): array
    {
        $insulinLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->insulin_units !== null);

        return [
            'count' => $insulinLogs->count(),
            'total' => round((float) ($insulinLogs->sum('insulin_units')), 1), // @phpstan-ignore argument.type
            'bolusCount' => $insulinLogs->filter(fn (DiabetesLog $log): bool => $log->insulin_type === InsulinType::Bolus)->count(),
            'basalCount' => $insulinLogs->filter(fn (DiabetesLog $log): bool => $log->insulin_type === InsulinType::Basal)->count(),
        ];
    }

    /**
     * Calculate carb statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, total: float, uniqueDays: int, avgPerDay: float}
     */
    private static function calculateCarbStats(Collection $logs): array
    {
        $carbLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->carbs_grams !== null);
        $total = $carbLogs->sum('carbs_grams');
        $uniqueDays = $carbLogs->map(fn (DiabetesLog $log) => $log->measured_at->toDateString())->unique()->count();

        $totalFloat = (float) $total; // @phpstan-ignore argument.type

        return [
            'count' => $carbLogs->count(),
            'total' => round($totalFloat, 1),
            'uniqueDays' => $uniqueDays,
            'avgPerDay' => $uniqueDays > 0 ? round($totalFloat / $uniqueDays) : 0,
        ];
    }

    /**
     * Calculate exercise statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, totalMinutes: int, types: array<int, string>}
     */
    private static function calculateExerciseStats(Collection $logs): array
    {
        $exerciseLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->exercise_duration_minutes !== null);

        /** @var array<int, string> $types */
        $types = $exerciseLogs->pluck('exercise_type')->filter()->unique()->take(2)->values()->all();

        return [
            'count' => $exerciseLogs->count(),
            'totalMinutes' => (int) ($exerciseLogs->sum('exercise_duration_minutes')), // @phpstan-ignore argument.type
            'types' => $types,
        ];
    }

    /**
     * Calculate weight statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, latest: float|null, previous: float|null, trend: string|null, diff: float|null}
     */
    private static function calculateWeightStats(Collection $logs): array
    {
        $weightLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->weight !== null)
            ->sortByDesc('measured_at')
            ->values();

        $latest = $weightLogs->first()?->weight;
        $previous = $weightLogs->skip(1)->first()?->weight;
        $trend = null;
        $diff = null;

        if ($latest !== null && $previous !== null) {
            if ($latest > $previous) {
                $trend = 'up';
            } elseif ($latest < $previous) {
                $trend = 'down';
            } else {
                $trend = 'stable';
            }
            $diff = round(abs($latest - $previous), 1);
        }

        return [
            'count' => $weightLogs->count(),
            'latest' => $latest,
            'previous' => $previous,
            'trend' => $trend,
            'diff' => $diff,
        ];
    }

    /**
     * Calculate blood pressure statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, latestSystolic: int|null, latestDiastolic: int|null}
     */
    private static function calculateBloodPressureStats(Collection $logs): array
    {
        $bpLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->blood_pressure_systolic !== null && $log->blood_pressure_diastolic !== null)
            ->sortByDesc('measured_at')
            ->values();

        $latest = $bpLogs->first();

        return [
            'count' => $bpLogs->count(),
            'latestSystolic' => $latest?->blood_pressure_systolic,
            'latestDiastolic' => $latest?->blood_pressure_diastolic,
        ];
    }

    /**
     * Calculate medication statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, uniqueMedications: array<int, string>}
     */
    private static function calculateMedicationStats(Collection $logs): array
    {
        $medicationLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->medication_name !== null);

        /** @var array<int, string> $uniqueMedications */
        $uniqueMedications = $medicationLogs->pluck('medication_name')->filter()->unique()->take(2)->values()->all();

        return [
            'count' => $medicationLogs->count(),
            'uniqueMedications' => $uniqueMedications,
        ];
    }

    /**
     * Calculate A1C statistics.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{count: int, latest: float|null}
     */
    private static function calculateA1cStats(Collection $logs): array
    {
        $a1cLogs = $logs->filter(fn (DiabetesLog $log): bool => $log->a1c_value !== null)
            ->sortByDesc('measured_at')
            ->values();

        return [
            'count' => $a1cLogs->count(),
            'latest' => $a1cLogs->first()?->a1c_value,
        ];
    }

    /**
     * Calculate logging streak (consecutive days).
     *
     * @param  Collection<int, DiabetesLog>  $allLogs
     * @return array{currentStreak: int, activeDays: int}
     */
    private static function calculateStreak(Collection $allLogs): array
    {
        if ($allLogs->isEmpty()) {
            return ['currentStreak' => 0, 'activeDays' => 0];
        }

        // Get unique dates with logs
        $uniqueDates = $allLogs->map(fn (DiabetesLog $log) => $log->measured_at->toDateString())
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        $activeDays = $uniqueDates->count();

        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        $streak = 0;
        $checkDate = today();

        // Start from today or yesterday if today has no logs
        if ($uniqueDates->doesntContain($today)) {
            if ($uniqueDates->doesntContain($yesterday)) {
                return ['currentStreak' => 0, 'activeDays' => $activeDays];
            }
            $checkDate = today()->subDay();
        }

        // Count consecutive days backwards
        for ($i = 0; $i < 365; $i++) {
            $dateStr = $checkDate->toDateString();
            if ($uniqueDates->contains($dateStr)) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }

        return ['currentStreak' => $streak, 'activeDays' => $activeDays];
    }

    /**
     * Calculate data type flags for conditional rendering.
     *
     * @param  Collection<int, DiabetesLog>  $logs
     * @return array{hasGlucose: bool, hasInsulin: bool, hasCarbs: bool, hasExercise: bool, hasMultipleFactors: bool}
     */
    private static function calculateDataTypes(Collection $logs): array
    {
        $hasGlucose = $logs->contains(fn (DiabetesLog $log): bool => $log->glucose_value !== null);
        $hasInsulin = $logs->contains(fn (DiabetesLog $log): bool => $log->insulin_units !== null);
        $hasCarbs = $logs->contains(fn (DiabetesLog $log): bool => $log->carbs_grams !== null);
        $hasExercise = $logs->contains(fn (DiabetesLog $log): bool => $log->exercise_duration_minutes !== null);

        $factorCount = array_filter([$hasGlucose, $hasInsulin, $hasCarbs, $hasExercise]);

        return [
            'hasGlucose' => $hasGlucose,
            'hasInsulin' => $hasInsulin,
            'hasCarbs' => $hasCarbs,
            'hasExercise' => $hasExercise,
            'hasMultipleFactors' => count($factorCount) > 1,
        ];
    }
}
