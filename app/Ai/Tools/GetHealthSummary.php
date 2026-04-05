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
use stdClass;

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

        $summaries = $query->toBase()->get()->map(function (stdClass $row): array {
            $total = is_numeric($row->total) ? (float) $row->total : 0.0;
            $avg = is_numeric($row->avg) ? (float) $row->avg : 0.0;
            $min = is_numeric($row->min) ? (float) $row->min : 0.0;
            $max = is_numeric($row->max) ? (float) $row->max : 0.0;
            $count = is_numeric($row->count) ? (int) $row->count : 0;

            return [
                'date' => $row->date,
                'type' => $row->type_identifier,
                'unit' => $row->unit,
                'total' => round($total, 1),
                'avg' => round($avg, 1),
                'min' => round($min, 1),
                'max' => round($max, 1),
                'count' => $count,
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
}
