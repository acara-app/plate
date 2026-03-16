<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\HealthEntry;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class GetHealthEntries implements Tool
{
    public function name(): string
    {
        return 'get_health_entries';
    }

    public function description(): string
    {
        return "Retrieve the current user's logged health entries including food intake (carbs), glucose readings, weight, blood pressure, insulin, medications, and exercise. Use this when the user asks about their food log, health history, what they ate, or wants to compare their actual intake against a meal plan.";
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
                'entries' => null,
            ]);
        }

        /** @var string|null $date */
        $date = $request['date'] ?? null;
        $daysInput = $request['days'] ?? 1;
        $days = max(1, is_numeric($daysInput) ? (int) $daysInput : 1);
        /** @var string $type */
        $type = $request['type'] ?? 'all';

        $endDate = $date ? Date::parse($date)->endOfDay() : Date::now()->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

        $query = HealthEntry::query()
            ->where('user_id', $user->id)
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->latest('measured_at');

        $query = $this->applyTypeFilter($query, $type);

        $entries = $query->limit(100)->get();

        $formatted = $entries->map(function (HealthEntry $entry): array {
            $data = [
                'measured_at' => $entry->measured_at->toIso8601String(),
                'source' => $entry->source?->value,
            ];

            if ($entry->glucose_value !== null) {
                $data['glucose_value'] = $entry->glucose_value;
                $data['glucose_reading_type'] = $entry->glucose_reading_type?->value;
            }

            if ($entry->carbs_grams !== null) {
                $data['carbs_grams'] = $entry->carbs_grams;
                if ($entry->protein_grams !== null) {
                    $data['protein_grams'] = $entry->protein_grams;
                }

                if ($entry->fat_grams !== null) {
                    $data['fat_grams'] = $entry->fat_grams;
                }

                if ($entry->calories !== null) {
                    $data['calories'] = $entry->calories;
                }

                if ($entry->notes !== null) {
                    $data['food_name'] = $entry->notes;
                }
            }

            if ($entry->weight !== null) {
                $data['weight_kg'] = $entry->weight;
            }

            if ($entry->blood_pressure_systolic !== null) {
                $data['blood_pressure'] = $entry->blood_pressure_systolic.'/'.$entry->blood_pressure_diastolic;
            }

            if ($entry->insulin_units !== null) {
                $data['insulin_units'] = $entry->insulin_units;
                $data['insulin_type'] = $entry->insulin_type?->value;
            }

            if ($entry->medication_name !== null) {
                $data['medication'] = $entry->medication_name.' '.$entry->medication_dosage;
            }

            if ($entry->exercise_type !== null) {
                $data['exercise'] = $entry->exercise_type;
                $data['exercise_duration_minutes'] = $entry->exercise_duration_minutes;
            }

            if ($entry->a1c_value !== null) {
                $data['a1c_value'] = $entry->a1c_value;
            }

            if ($entry->notes !== null) {
                $data['notes'] = $entry->notes;
            }

            return $data;
        })->all();

        return (string) json_encode([
            'success' => true,
            'date_range' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'total_entries' => count($formatted),
            'entries' => array_values($formatted),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('The date to retrieve entries for in ISO format (e.g., "2026-02-27"). Defaults to today.'),
            'days' => $schema->integer()
                ->description('Number of days to look back from the specified date. Defaults to 1 (just that day).'),
            'type' => $schema->string()
                ->enum(['all', 'food', 'glucose', 'vitals', 'exercise', 'medication'])
                ->description('Filter entries by type. "all" returns everything, "food" returns carb entries, "glucose" returns glucose readings, "vitals" returns weight and blood pressure, "exercise" returns exercise logs, "medication" returns insulin and medication entries.'),
        ];
    }

    /**
     * @param  Builder<HealthEntry>  $query
     * @return Builder<HealthEntry>
     */
    private function applyTypeFilter(Builder $query, string $type): Builder
    {
        return match ($type) {
            'food' => $query->whereNotNull('carbs_grams'),
            'glucose' => $query->whereNotNull('glucose_value'),
            'vitals' => $query->whereNotNull('weight')
                ->orWhereNotNull('blood_pressure_systolic')
                ->orWhereNotNull('a1c_value'),
            'exercise' => $query->whereNotNull('exercise_type'),
            'medication' => $query->whereNotNull('insulin_units')
                ->orWhereNotNull('medication_name'),
            default => $query,
        };
    }
}
