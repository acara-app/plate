<?php

declare(strict_types=1);

namespace App\Http\Layouts;

use Inertia\DeferProp;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthSyncType;
use App\Enums\InsulinType;
use App\Models\HealthSyncSample;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Inertia;

/** @codeCoverageIgnore */
final readonly class DiabetesLayout
{
    /**
     * @return array{glucoseReadingTypes: Collection<int, array{value: string, label: string}>, insulinTypes: Collection<int, array{value: string, label: string}>, glucoseUnit: string, recentMedications: DeferProp, recentInsulins: DeferProp, todaysMeals: DeferProp}
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
            'recentMedications' => Inertia::defer(fn (): array => self::getRecentMedications($user)),
            'recentInsulins' => Inertia::defer(fn (): array => self::getRecentInsulins($user)),
            'todaysMeals' => Inertia::defer(fn (): array => self::getTodaysMeals($user)),
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
            ->filter(fn (HealthSyncSample $s): bool => $s->medicationName() !== null)
            ->unique(fn (HealthSyncSample $s): string => sprintf('%s|%s', $s->medicationName() ?? '', $s->medicationDosage() ?? ''))
            ->take(5)
            ->map(function (HealthSyncSample $s): array {
                $name = $s->medicationName() ?? '';
                $dosage = $s->medicationDosage() ?? '';

                return [
                    'name' => $name,
                    'dosage' => $dosage,
                    'label' => mb_trim(sprintf('%s %s', $name, $dosage)),
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
}
