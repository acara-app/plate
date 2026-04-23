<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\GetMostRecentHealthSyncSamplesByTypeAction;
use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class GetHealthData implements Tool
{
    public function name(): string
    {
        return 'get_health_data';
    }

    public function description(): string
    {
        return "Retrieve the user's individual health records. Returns specific entries like food intake, glucose readings, weight, blood pressure, step counts, heart rate samples, and any other logged or synced health data. Use when the user asks about specific entries, what they ate, their readings, or recent health events.";
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
        $daysInput = $request['days'] ?? 1;
        $days = max(1, is_numeric($daysInput) ? (int) $daysInput : 1);
        /** @var string|null $date */
        $date = $request['date'] ?? null;

        $endDate = $date ? Date::parse($date)->endOfDay() : Date::now()->endOfDay();
        $startDate = $endDate->copy()->subDays($days - 1)->startOfDay();

        $typeFilter = HealthSyncSample::resolveTypeFilter($type, $user->id);
        $limit = 200;

        $query = $user->healthSyncSamples()
            ->whereBetween('measured_at', [$startDate, $endDate])
            ->whereNotIn('type_identifier', HealthSyncType::userCharacteristicValues())
            ->latest('measured_at');

        if ($typeFilter !== null) {
            $query->whereIn('type_identifier', $typeFilter);
        }

        $totalMatching = (clone $query)->count();
        $samples = $query->limit($limit)->get();

        $records = $samples->map(fn (HealthSyncSample $sample): array => [
            'type' => $sample->type_identifier,
            'value' => $sample->value,
            'unit' => $sample->unit,
            'measured_at' => $sample->measured_at->toIso8601String(),
            'source' => $sample->source,
            'metadata' => $sample->metadata,
        ])->all();

        $returnedCount = count($records);
        $response = [
            'success' => true,
            'date_range' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'total' => $returnedCount,
            'limit' => $limit,
            'returned_count' => $returnedCount,
            'truncated' => $totalMatching > $limit,
            'records' => array_values($records),
        ];

        if ($samples->isEmpty()) {
            $recentSamples = resolve(GetMostRecentHealthSyncSamplesByTypeAction::class)
                ->handle($user, $typeFilter);

            $response['data_availability'] = [
                'message' => 'No entries found in the requested date range. Here is the most recent data available.',
                'most_recent_by_type' => $recentSamples->map(fn (HealthSyncSample $sample): array => [
                    'type' => $sample->type_identifier,
                    'latest_measured_at' => $sample->measured_at->toIso8601String(),
                    'days_ago' => (int) $sample->measured_at->diffInDays(now()),
                ])->values()->all(),
            ];
        }

        return (string) json_encode($response);
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
                ->description('Number of days to look back. Defaults to 1.'),
            'date' => $schema->string()->required()->nullable()
                ->description('The end date in ISO format (e.g., "2026-04-05"). Defaults to today.'),
        ];
    }
}
