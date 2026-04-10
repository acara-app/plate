<?php

declare(strict_types=1);

namespace App\Http\Layouts;

use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthSyncType;
use App\Enums\InsulinType;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\Meal;
use App\Models\User;
use App\Services\BloodPressureDashboardService;
use App\Services\HealthEntryAssembler;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class DiabetesLayout
{
    /**
     * @var array<string, int>
     */
    public const array TIME_PERIODS = [
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    /**
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
     * @return array{
     *     logs: Collection<int, array<string, mixed>>,
     *     timePeriod: string,
     *     summary: array<string, mixed>
     * }
     */
    public static function dashboardData(User $user, string $timePeriod = '30d'): array
    {
        if (! array_key_exists($timePeriod, self::TIME_PERIODS)) {
            $timePeriod = '30d';
        }

        $days = self::TIME_PERIODS[$timePeriod];
        $cutoffDate = now()->subDays($days);

        $samples = $user->healthSyncSamples()
            ->whereIn('type_identifier', self::entryTypeIdentifiers())
            ->where('measured_at', '>=', $cutoffDate)
            ->latest('measured_at')
            ->get();

        $assembler = resolve(HealthEntryAssembler::class);

        return [
            'logs' => $assembler->assemble($samples),
            'timePeriod' => $timePeriod,
            'summary' => self::calculateSummary($samples, $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function dashboardDataFromAggregates(User $user, string $timePeriod = '30d'): array
    {
        if (! array_key_exists($timePeriod, self::TIME_PERIODS)) {
            $timePeriod = '30d';
        }

        $days = self::TIME_PERIODS[$timePeriod];
        $from = now()->subDays($days)->toDateString();
        $to = now()->toDateString();

        /** @var Collection<string, Collection<int, HealthDailyAggregate>> $aggregates */
        $aggregates = $user->healthDailyAggregates()
            ->whereBetween('local_date', [$from, $to])
            ->get()
            ->groupBy('type_identifier');

        if ($aggregates->isEmpty()) {
            return self::dashboardData($user, $timePeriod);
        }

        $samples = $user->healthSyncSamples()
            ->whereIn('type_identifier', self::entryTypeIdentifiers())
            ->where('measured_at', '>=', now()->subDays($days))
            ->latest('measured_at')
            ->limit(200)
            ->get();

        $assembler = resolve(HealthEntryAssembler::class);

        return [
            'logs' => $assembler->assemble($samples),
            'timePeriod' => $timePeriod,
            'summary' => self::calculateSummaryFromAggregates($aggregates, $user),
        ];
    }

    /**
     * @return array<int, array{name: string, dosage: string, label: string}>
     */
    public static function getRecentMedications(User $user): array
    {
        /** @var array<int, array{name: string, dosage: string, label: string}> */
        return $user->healthSyncSamples()
            ->whereIn('type_identifier', [HealthSyncType::Medication->value, HealthSyncType::MedicationDoseEvent->value])
            ->latest()
            ->take(20)
            ->get()
            ->filter(function (HealthSyncSample $s): bool {
                $meta = $s->metadata ?? [];

                return isset($meta['medication_name'], $meta['medication_dosage']);
            })
            ->unique(function (HealthSyncSample $s): string {
                $meta = $s->metadata ?? [];
                $name = is_string($meta['medication_name'] ?? null) ? $meta['medication_name'] : '';
                $dosage = is_string($meta['medication_dosage'] ?? null) ? $meta['medication_dosage'] : '';

                return sprintf('%s|%s', $name, $dosage);
            })
            ->take(5)
            ->map(function (HealthSyncSample $s): array {
                $meta = $s->metadata ?? [];
                $name = is_string($meta['medication_name'] ?? null) ? $meta['medication_name'] : '';
                $dosage = is_string($meta['medication_dosage'] ?? null) ? $meta['medication_dosage'] : '';

                return [
                    'name' => $name,
                    'dosage' => $dosage,
                    'label' => sprintf('%s %s', $name, $dosage),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{units: float, type: string, label: string}>
     */
    public static function getRecentInsulins(User $user): array
    {
        /** @var array<int, array{units: float, type: string, label: string}> */
        return $user->healthSyncSamples()
            ->ofType(HealthSyncType::Insulin)
            ->latest()
            ->take(20)
            ->get()
            ->unique(function (HealthSyncSample $s): string {
                $type = is_string($s->metadata['insulin_type'] ?? null) ? $s->metadata['insulin_type'] : '';

                return sprintf('%s|%s', $s->value, $type);
            })
            ->take(5)
            ->map(function (HealthSyncSample $s): array {
                $type = is_string($s->metadata['insulin_type'] ?? null) ? $s->metadata['insulin_type'] : '';

                return [
                    'units' => $s->value,
                    'type' => $type,
                    'label' => sprintf('%su %s', $s->value, $type),
                ];
            })
            ->values()
            ->all();
    }

    /**
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

        $startDate = $mealPlan->created_at->startOfDay();
        $today = today();
        $dayNumber = (int) $startDate->diffInDays($today) + 1;

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
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array<string, mixed>
     */
    private static function calculateSummaryFromAggregates(Collection $aggregatesByType, User $user): array
    {
        return [
            'glucoseStats' => self::calculateGlucoseStatsFromAggregates($aggregatesByType),
            'insulinStats' => self::calculateInsulinStatsFromAggregates($aggregatesByType),
            'carbStats' => self::calculateCarbStatsFromAggregates($aggregatesByType),
            'exerciseStats' => self::calculateExerciseStatsFromAggregates($aggregatesByType),
            'weightStats' => self::calculateWeightStatsFromAggregates($aggregatesByType),
            'bpStats' => self::calculateBloodPressureStatsFromAggregates($aggregatesByType, $user),
            'medicationStats' => self::calculateMedicationStatsFromAggregates($aggregatesByType),
            'a1cStats' => self::calculateA1cStatsFromAggregates($aggregatesByType),
            'streakStats' => self::calculateStreakFromAggregates($user),
            'dataTypes' => self::calculateDataTypesFromAggregates($aggregatesByType),
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, avg: float, min: float, max: float}
     */
    private static function calculateGlucoseStatsFromAggregates(Collection $aggregatesByType): array
    {
        $glucoseDays = $aggregatesByType->get('bloodGlucose');

        if ($glucoseDays === null || $glucoseDays->isEmpty()) {
            return ['count' => 0, 'avg' => 0, 'min' => 0, 'max' => 0];
        }

        /** @var float $min */
        $min = $glucoseDays->min('value_min');
        /** @var float $max */
        $max = $glucoseDays->max('value_max');

        return [
            'count' => self::totalCount($glucoseDays),
            'avg' => round(self::weightedAverage($glucoseDays), 1),
            'min' => round($min, 1),
            'max' => round($max, 1),
        ];
    }

    /**
     * @param  Collection<int, HealthDailyAggregate>  $days
     */
    private static function weightedAverage(Collection $days): float
    {
        $totalCount = self::totalCount($days);

        if ($totalCount === 0) {
            return 0.0;
        }

        return self::totalSum($days) / $totalCount;
    }

    /**
     * @param  Collection<int, HealthDailyAggregate>  $days
     */
    private static function totalSum(Collection $days): float
    {
        return (float) $days->sum(
            static fn (HealthDailyAggregate $day): float => (float) ($day->value_sum_canonical ?? $day->value_sum ?? 0),
        );
    }

    /**
     * @param  Collection<int, HealthDailyAggregate>  $days
     */
    private static function totalCount(Collection $days): int
    {
        /** @var int $count */
        $count = $days->sum('value_count');

        return $count;
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, total: float, bolusCount: int, basalCount: int}
     */
    private static function calculateInsulinStatsFromAggregates(Collection $aggregatesByType): array
    {
        $insulinDays = $aggregatesByType->get('insulin');

        if ($insulinDays === null) {
            return ['count' => 0, 'total' => 0, 'bolusCount' => 0, 'basalCount' => 0];
        }

        $totalCount = self::totalCount($insulinDays);
        $totalUnits = round(self::totalSum($insulinDays), 1);

        $bolusCount = 0;
        $basalCount = 0;
        foreach ($insulinDays as $day) {
            if ($day->metadata !== null) {
                $meta = $day->metadata;
                if (isset($meta['bolus_count']) && is_numeric($meta['bolus_count'])) {
                    $bolusCount += (int) $meta['bolus_count'];
                }

                if (isset($meta['basal_count']) && is_numeric($meta['basal_count'])) {
                    $basalCount += (int) $meta['basal_count'];
                }
            }
        }

        return [
            'count' => $totalCount,
            'total' => $totalUnits,
            'bolusCount' => $bolusCount,
            'basalCount' => $basalCount,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, total: float, uniqueDays: int, avgPerDay: float}
     */
    private static function calculateCarbStatsFromAggregates(Collection $aggregatesByType): array
    {
        $carbDays = $aggregatesByType->get('carbohydrates');

        if ($carbDays === null) {
            return ['count' => 0, 'total' => 0, 'uniqueDays' => 0, 'avgPerDay' => 0];
        }

        $total = round(self::totalSum($carbDays), 1);
        $uniqueDays = $carbDays->count();

        return [
            'count' => self::totalCount($carbDays),
            'total' => $total,
            'uniqueDays' => $uniqueDays,
            'avgPerDay' => $uniqueDays > 0 ? round($total / $uniqueDays) : 0,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, totalMinutes: int, types: array<int, string>}
     */
    private static function calculateExerciseStatsFromAggregates(Collection $aggregatesByType): array
    {
        $exerciseDays = $aggregatesByType->get('exerciseMinutes');
        $workoutDays = $aggregatesByType->get('workouts');

        $exerciseCount = $exerciseDays ? self::totalCount($exerciseDays) : 0;
        $workoutCount = $workoutDays ? self::totalCount($workoutDays) : 0;
        $exerciseMinutes = $exerciseDays ? (int) self::totalSum($exerciseDays) : 0;
        $workoutMinutes = $workoutDays ? (int) self::totalSum($workoutDays) : 0;

        $types = [];
        if ($exerciseDays !== null) {
            $types[] = 'exerciseMinutes';
        }

        if ($workoutDays !== null) {
            $types[] = 'workouts';
        }

        return [
            'count' => $exerciseCount + $workoutCount,
            'totalMinutes' => $exerciseMinutes + $workoutMinutes,
            'types' => $types,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, latest: float|null, previous: float|null, trend: string|null, diff: float|null}
     */
    private static function calculateWeightStatsFromAggregates(Collection $aggregatesByType): array
    {
        $weightDays = $aggregatesByType->get('weight');

        if ($weightDays === null || $weightDays->isEmpty()) {
            return ['count' => 0, 'latest' => null, 'previous' => null, 'trend' => null, 'diff' => null];
        }

        $sorted = $weightDays->sortByDesc('local_date')->values();
        $latest = $sorted->first()?->value_last;
        $previous = $sorted->count() > 1 ? $sorted->skip(1)->first()?->value_last : null;

        $trend = null;
        $diff = null;

        if ($latest !== null && $previous !== null) {
            $trend = match (true) {
                $latest > $previous => 'up',
                $latest < $previous => 'down',
                default => 'stable',
            };

            $diff = round(abs($latest - $previous), 1);
        }

        /** @var int $weightCount */
        $weightCount = $weightDays->sum('value_count');

        return [
            'count' => $weightCount,
            'latest' => $latest,
            'previous' => $previous,
            'trend' => $trend,
            'diff' => $diff,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, latestSystolic: int|null, latestDiastolic: int|null}
     */
    private static function calculateBloodPressureStatsFromAggregates(Collection $aggregatesByType, User $user): array
    {
        $systolicDays = $aggregatesByType->get('bloodPressureSystolic');
        $diastolicDays = $aggregatesByType->get('bloodPressureDiastolic');

        $systolicCount = $systolicDays ? self::totalCount($systolicDays) : 0;
        $diastolicCount = $diastolicDays ? self::totalCount($diastolicDays) : 0;

        $bpService = resolve(BloodPressureDashboardService::class);
        $latestPair = $bpService->latestPair($user);

        return [
            'count' => min($systolicCount, $diastolicCount),
            'latestSystolic' => $latestPair['systolic'] ?? null,
            'latestDiastolic' => $latestPair['diastolic'] ?? null,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, uniqueMedications: array<int, string>}
     */
    private static function calculateMedicationStatsFromAggregates(Collection $aggregatesByType): array
    {
        $medDays = $aggregatesByType->get('medication');
        $medEventDays = $aggregatesByType->get('medicationDoseEvent');

        /** @var int $medCount */
        $medCount = $medDays ? $medDays->sum('value_count') : 0;
        /** @var int $medEventCount */
        $medEventCount = $medEventDays ? $medEventDays->sum('value_count') : 0;

        /** @var array<int, string> $uniqueMedications */
        $uniqueMedications = [];

        foreach ([$medDays, $medEventDays] as $days) {
            if ($days === null) {
                continue;
            }

            foreach ($days as $day) {
                if ($day->metadata !== null) {
                    $names = collect($day->metadata)->pluck('medication_name')->filter()->unique();
                    foreach ($names as $name) {
                        if (is_string($name) && ! in_array($name, $uniqueMedications, true) && count($uniqueMedications) < 2) {
                            $uniqueMedications[] = $name;
                        }
                    }
                }
            }
        }

        return [
            'count' => $medCount + $medEventCount,
            'uniqueMedications' => $uniqueMedications,
        ];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{count: int, latest: float|null}
     */
    private static function calculateA1cStatsFromAggregates(Collection $aggregatesByType): array
    {
        $a1cDays = $aggregatesByType->get('a1c');

        if ($a1cDays === null || $a1cDays->isEmpty()) {
            return ['count' => 0, 'latest' => null];
        }

        $latest = $a1cDays->sortByDesc('local_date')->first();

        return [
            'count' => self::totalCount($a1cDays),
            'latest' => $latest?->value_last,
        ];
    }

    /**
     * @return array{currentStreak: int, activeDays: int}
     */
    private static function calculateStreakFromAggregates(User $user): array
    {
        $uniqueDates = $user->healthDailyAggregates()
            ->select('local_date')
            ->groupBy('local_date')
            ->latest('local_date')
            ->limit(365)
            ->get();

        if ($uniqueDates->isEmpty()) {
            return ['currentStreak' => 0, 'activeDays' => 0];
        }

        $activeDays = $user->healthDailyAggregates()
            ->distinct('local_date')
            ->count('local_date');

        $dates = $uniqueDates->map(
            static fn (HealthDailyAggregate $agg): string => $agg->local_date?->toDateString() ?? ''
        )->filter();

        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        $streak = 0;
        $checkDate = today();

        if ($dates->doesntContain($today)) {
            if ($dates->doesntContain($yesterday)) {
                return ['currentStreak' => 0, 'activeDays' => $activeDays];
            }

            $checkDate = today()->subDay();
        }

        for ($i = 0; $i < 365; $i++) {
            $dateStr = $checkDate->toDateString();
            if ($dates->contains($dateStr)) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }

        return ['currentStreak' => $streak, 'activeDays' => $activeDays];
    }

    /**
     * @param  Collection<string, Collection<int, HealthDailyAggregate>>  $aggregatesByType
     * @return array{hasGlucose: bool, hasInsulin: bool, hasCarbs: bool, hasExercise: bool, hasMultipleFactors: bool}
     */
    private static function calculateDataTypesFromAggregates(Collection $aggregatesByType): array
    {
        $types = $aggregatesByType->keys();

        $hasGlucose = $types->contains('bloodGlucose');
        $hasInsulin = $types->contains('insulin');
        $hasCarbs = $types->contains('carbohydrates');
        $hasExercise = $types->contains('exerciseMinutes') || $types->contains('workouts');

        $factorCount = array_filter([$hasGlucose, $hasInsulin, $hasCarbs, $hasExercise]);

        return [
            'hasGlucose' => $hasGlucose,
            'hasInsulin' => $hasInsulin,
            'hasCarbs' => $hasCarbs,
            'hasExercise' => $hasExercise,
            'hasMultipleFactors' => count($factorCount) > 1,
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array<string, mixed>
     */
    private static function calculateSummary(Collection $samples, User $user): array
    {
        return [
            'glucoseStats' => self::calculateGlucoseStats($samples),
            'insulinStats' => self::calculateInsulinStats($samples),
            'carbStats' => self::calculateCarbStats($samples),
            'exerciseStats' => self::calculateExerciseStats($samples),
            'weightStats' => self::calculateWeightStats($samples),
            'bpStats' => self::calculateBloodPressureStats($samples),
            'medicationStats' => self::calculateMedicationStats($samples),
            'a1cStats' => self::calculateA1cStats($samples),
            'streakStats' => self::calculateStreak($user),
            'dataTypes' => self::calculateDataTypes($samples),
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, avg: float, min: float, max: float}
     */
    private static function calculateGlucoseStats(Collection $samples): array
    {
        $values = $samples
            ->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::BloodGlucose->value)
            ->pluck('value')
            ->filter()
            ->values();

        if ($values->isEmpty()) {
            return ['count' => 0, 'avg' => 0, 'min' => 0, 'max' => 0];
        }

        return [
            'count' => $values->count(),
            'avg' => round((float) ($values->avg()), 1),
            'min' => round((float) ($values->min()), 1), // @phpstan-ignore-line
            'max' => round((float) ($values->max()), 1), // @phpstan-ignore-line
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, total: float, bolusCount: int, basalCount: int}
     */
    private static function calculateInsulinStats(Collection $samples): array
    {
        $insulinSamples = $samples->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::Insulin->value);

        return [
            'count' => $insulinSamples->count(),
            'total' => round($insulinSamples->sum(fn (HealthSyncSample $s): float => $s->value), 1),
            'bolusCount' => $insulinSamples->filter(fn (HealthSyncSample $s): bool => ($s->metadata['insulin_type'] ?? null) === InsulinType::Bolus->value)->count(),
            'basalCount' => $insulinSamples->filter(fn (HealthSyncSample $s): bool => ($s->metadata['insulin_type'] ?? null) === InsulinType::Basal->value)->count(),
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, total: float, uniqueDays: int, avgPerDay: float}
     */
    private static function calculateCarbStats(Collection $samples): array
    {
        $carbSamples = $samples->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::Carbohydrates->value);
        $total = $carbSamples->sum(fn (HealthSyncSample $s): float => $s->value);
        $uniqueDays = $carbSamples->map(fn (HealthSyncSample $s): string => $s->measured_at->toDateString())->unique()->count();

        return [
            'count' => $carbSamples->count(),
            'total' => round($total, 1),
            'uniqueDays' => $uniqueDays,
            'avgPerDay' => $uniqueDays > 0 ? round($total / $uniqueDays) : 0,
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, totalMinutes: int, types: array<int, string>}
     */
    private static function calculateExerciseStats(Collection $samples): array
    {
        $exerciseSamples = $samples->filter(fn (HealthSyncSample $s): bool => in_array($s->type_identifier, [HealthSyncType::ExerciseMinutes->value, HealthSyncType::Workouts->value], true));

        /** @var array<int, string> $types */
        $types = $exerciseSamples
            ->map(fn (HealthSyncSample $s): string => is_string($s->metadata['exercise_type'] ?? null) ? $s->metadata['exercise_type'] : $s->type_identifier)
            ->unique()
            ->take(2)
            ->values()
            ->all();

        return [
            'count' => $exerciseSamples->count(),
            'totalMinutes' => (int) $exerciseSamples->sum(fn (HealthSyncSample $s): float => $s->value),
            'types' => $types,
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, latest: float|null, previous: float|null, trend: string|null, diff: float|null}
     */
    private static function calculateWeightStats(Collection $samples): array
    {
        $weightSamples = $samples
            ->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::Weight->value)
            ->sortByDesc('measured_at')
            ->values();

        $latest = $weightSamples->first()?->value;
        $previous = $weightSamples->skip(1)->first()?->value;
        $trend = null;
        $diff = null;

        if ($latest !== null && $previous !== null) {
            $trend = match (true) {
                $latest > $previous => 'up',
                $latest < $previous => 'down',
                default => 'stable',
            };

            $diff = round(abs($latest - $previous), 1);
        }

        return [
            'count' => $weightSamples->count(),
            'latest' => $latest,
            'previous' => $previous,
            'trend' => $trend,
            'diff' => $diff,
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, latestSystolic: int|null, latestDiastolic: int|null}
     */
    private static function calculateBloodPressureStats(Collection $samples): array
    {
        $systolicSamples = $samples
            ->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::BloodPressureSystolic->value)
            ->sortByDesc('measured_at');

        $diastolicSamples = $samples
            ->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::BloodPressureDiastolic->value)
            ->sortByDesc('measured_at');

        $latestSystolic = $systolicSamples->first();
        $latestDiastolic = $diastolicSamples->first();

        $pairCount = min($systolicSamples->count(), $diastolicSamples->count());

        return [
            'count' => $pairCount,
            'latestSystolic' => $latestSystolic !== null ? (int) $latestSystolic->value : null,
            'latestDiastolic' => $latestDiastolic !== null ? (int) $latestDiastolic->value : null,
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, uniqueMedications: array<int, string>}
     */
    private static function calculateMedicationStats(Collection $samples): array
    {
        $medSamples = $samples->filter(fn (HealthSyncSample $s): bool => in_array($s->type_identifier, [HealthSyncType::Medication->value, HealthSyncType::MedicationDoseEvent->value], true));

        /** @var array<int, string> $uniqueMedications */
        $uniqueMedications = $medSamples
            ->map(fn (HealthSyncSample $s): ?string => is_string($s->metadata['medication_name'] ?? null) ? $s->metadata['medication_name'] : null)
            ->filter()
            ->unique()
            ->take(2)
            ->values()
            ->all();

        return [
            'count' => $medSamples->count(),
            'uniqueMedications' => $uniqueMedications,
        ];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{count: int, latest: float|null}
     */
    private static function calculateA1cStats(Collection $samples): array
    {
        $a1cSamples = $samples
            ->filter(fn (HealthSyncSample $s): bool => $s->type_identifier === HealthSyncType::A1c->value)
            ->sortByDesc('measured_at')
            ->values();

        return [
            'count' => $a1cSamples->count(),
            'latest' => $a1cSamples->first()?->value,
        ];
    }

    /**
     * @return array{currentStreak: int, activeDays: int}
     */
    private static function calculateStreak(User $user): array
    {
        /** @var Collection<int, object{date: string}> $uniqueDates */
        $uniqueDates = $user->healthSyncSamples()
            ->whereIn('type_identifier', self::entryTypeIdentifiers())
            ->select(DB::raw('DATE(measured_at) as date'))
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(365)
            ->get();

        if ($uniqueDates->isEmpty()) {
            return ['currentStreak' => 0, 'activeDays' => 0];
        }

        $activeDays = $user->healthSyncSamples()
            ->whereIn('type_identifier', self::entryTypeIdentifiers())
            ->distinct(DB::raw('DATE(measured_at)'))
            ->count(DB::raw('DATE(measured_at)'));

        $dates = $uniqueDates->pluck('date');

        $today = today()->toDateString();
        $yesterday = today()->subDay()->toDateString();

        $streak = 0;
        $checkDate = today();

        if ($dates->doesntContain($today)) {
            if ($dates->doesntContain($yesterday)) {
                return ['currentStreak' => 0, 'activeDays' => $activeDays];
            }

            $checkDate = today()->subDay();
        }

        for ($i = 0; $i < 365; $i++) {
            $dateStr = $checkDate->toDateString();
            if ($dates->contains($dateStr)) {
                $streak++;
                $checkDate = $checkDate->subDay();
            } else {
                break;
            }
        }

        return ['currentStreak' => $streak, 'activeDays' => $activeDays];
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array{hasGlucose: bool, hasInsulin: bool, hasCarbs: bool, hasExercise: bool, hasMultipleFactors: bool}
     */
    private static function calculateDataTypes(Collection $samples): array
    {
        $types = $samples->pluck('type_identifier')->unique();

        $hasGlucose = $types->contains(HealthSyncType::BloodGlucose->value);
        $hasInsulin = $types->contains(HealthSyncType::Insulin->value);
        $hasCarbs = $types->contains(HealthSyncType::Carbohydrates->value);
        $hasExercise = $types->contains(HealthSyncType::ExerciseMinutes->value) || $types->contains(HealthSyncType::Workouts->value);

        $factorCount = array_filter([$hasGlucose, $hasInsulin, $hasCarbs, $hasExercise]);

        return [
            'hasGlucose' => $hasGlucose,
            'hasInsulin' => $hasInsulin,
            'hasCarbs' => $hasCarbs,
            'hasExercise' => $hasExercise,
            'hasMultipleFactors' => count($factorCount) > 1,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function entryTypeIdentifiers(): array
    {
        return HealthSyncType::entryTypeValues();
    }
}
