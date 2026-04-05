<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

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

        $endDate = $date ? Date::parse($date)->endOfDay() : Date::now()->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

        $typeFilter = HealthSyncSample::resolveTypeFilter($type, $user->id);

        $query = $user->healthSyncSamples()
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->whereNotIn('type_identifier', HealthSyncType::userCharacteristicValues())
            ->select([
                DB::raw('DATE(measured_at) as date'),
                'type_identifier',
                'unit',
                DB::raw('SUM(value) as total'),
                DB::raw('AVG(value) as avg'),
                DB::raw('MIN(value) as min'),
                DB::raw('MAX(value) as max'),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('date', 'type_identifier', 'unit')
            ->orderByDesc('date');

        if ($typeFilter !== null) {
            $query->whereIn('type_identifier', $typeFilter);
        }

        $summaries = $query->get()->map(fn (object $row): array => [
            'date' => $row->date,
            'type' => $row->type_identifier,
            'unit' => $row->unit,
            'total' => round((float) $row->total, 1),
            'avg' => round((float) $row->avg, 1),
            'min' => round((float) $row->min, 1),
            'max' => round((float) $row->max, 1),
            'count' => (int) $row->count,
        ])->values()->all();

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
}
