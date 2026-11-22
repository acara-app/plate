<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\RegisterDailyHealthDatasets;
use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use App\Enums\GlucoseUnit;
use App\Enums\HealthSyncType;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class GetHealthSummary implements Tool
{
    public function name(): string
    {
        return 'get_health_summary';
    }

    public function description(): string
    {
        return "Retrieve aggregated daily summaries of the user's health data. Returns totals, averages, min/max per day for any health metric — steps, heart rate, calories consumed, active energy, glucose readings, weight, and more. Use when the user asks about trends, daily totals, weekly averages, or comparisons over time.";
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
                'data' => null,
            ]);
        }

        /** @var string $type */
        $type = $request['type'] ?? 'all';
        $daysInput = $request['days'] ?? 7;
        $days = max(1, is_numeric($daysInput) ? (int) $daysInput : 7);
        /** @var string|null $date */
        $date = $request['date'] ?? null;

        $endDate = ($date ? Date::parse($date) : Date::now())->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

        $typeFilter = $this->resolveTypeFilter($user, $type);

        $query = $user->healthDailyAggregates()
            ->whereDate(HealthDailyAggregate::UTC_DAY_COLUMN, '>=', $startDate->toDateString())
            ->whereDate(HealthDailyAggregate::UTC_DAY_COLUMN, '<=', $endDate->toDateString())
            ->whereNotIn('type_identifier', HealthSyncType::userCharacteristicValues())
            ->latest(HealthDailyAggregate::UTC_DAY_COLUMN);

        if ($typeFilter !== null) {
            $query->whereIn('type_identifier', $typeFilter);
        }

        $aggregates = $query->get();
        $glucoseUnit = $user->glucoseUnit();

        resolve(RegisterDailyHealthDatasets::class)->handle($aggregates, $startDate, $endDate, $glucoseUnit);

        $summaries = $aggregates->map(function (HealthDailyAggregate $aggregate) use ($glucoseUnit): array {
            $inPersonUnit = $aggregate->type_identifier === 'bloodGlucose' && $aggregate->unit === GlucoseUnit::MgDl->value;
            $reading = fn (?float $value): float => $inPersonUnit ? $glucoseUnit->fromMgDl(round((float) $value, 1)) : round((float) $value, 1);
            $primaryValue = $aggregate->primaryValue();

            return [
                'date' => $aggregate->local_date?->toDateString() ?? $aggregate->date->toDateString(),
                'type' => $aggregate->type_identifier,
                'unit' => $inPersonUnit ? $glucoseUnit->value : $aggregate->unit,
                'total' => $reading($aggregate->value_sum),
                'avg' => $reading($aggregate->value_avg),
                'min' => $reading($aggregate->value_min),
                'max' => $reading($aggregate->value_max),
                'count' => (int) $aggregate->value_count,
                'aggregation_function' => $aggregate->aggregation_function,
                'primary_value' => $primaryValue === null ? null : $reading($primaryValue),
                'canonical_unit' => $aggregate->canonical_unit,
                'source_primary' => $aggregate->source_primary,
            ];
        })->values()->all();

        return (string) json_encode([
            'success' => true,
            'date_range' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'summaries' => $summaries,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required()->nullable()
                ->description('Filter by category (food, glucose, vitals, medication, exercise, heart_rate, steps, active_energy, distance, flights_climbed, stand_time) or raw type identifier (stepCount, heartRate, bloodGlucose, etc.). Defaults to "all".'),
            'days' => $schema->integer()->required()->nullable()
                ->description('Number of days to look back. Defaults to 7.'),
            'date' => $schema->string()->required()->nullable()
                ->description('The end date in ISO format (e.g., "2026-04-05"). Defaults to today.'),
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function resolveTypeFilter(User $user, string $type): ?array
    {
        if ($type === 'all') {
            return null;
        }

        $matched = $user->healthDailyAggregates()
            ->select('type_identifier')
            ->distinct()
            ->get()
            ->map(fn (HealthDailyAggregate $aggregate): string => $aggregate->type_identifier)
            ->filter(fn (string $typeIdentifier): bool => $typeIdentifier === $type || HealthSyncSample::categoryFor($typeIdentifier) === $type)
            ->values()
            ->all();

        return $matched !== [] ? $matched : [$type];
    }
}
