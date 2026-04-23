<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\AggregateHealthDailySamplesAction;
use App\Actions\RecordHealthSampleAction;
use App\Ai\Attributes\AiToolSensitivity;
use App\Data\HealthLogData;
use App\Enums\DataSensitivity;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntrySource;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use App\Enums\WeightUnit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class LogHealthEntry implements Tool
{
    public function __construct(
        private AggregateHealthDailySamplesAction $aggregateHealthDailySamplesAction,
    ) {}

    public function name(): string
    {
        return 'log_health_entry';
    }

    public function description(): string
    {
        return 'Log a health entry for the current user. Use this when the user reports a health measurement like food intake, glucose reading, weight, blood pressure, insulin dose, medication, or exercise. Extract the relevant data from the user message and call this tool to save it.';
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode([
                'error' => 'User not authenticated',
            ]);
        }

        /** @var array<string, mixed> $requestData */
        $requestData = $request->toArray();
        $healthData = HealthLogData::fromParsedArray(array_merge(
            $requestData,
            ['is_health_data' => true],
        ));

        if ($this->requiresGlucoseUnitClarification($healthData)) {
            return (string) json_encode([
                'error' => 'I need the glucose unit to log this accurately.',
                'requires_clarification' => true,
                'field' => 'glucose_unit',
                'message' => 'Your glucose value looks like mmol/L. Please confirm whether it is mg/dL or mmol/L.',
                'allowed_values' => ['mg/dL', 'mmol/L'],
            ]);
        }

        try {
            $action = resolve(RecordHealthSampleAction::class);
            $sample = $action->handle($healthData, $user, HealthEntrySource::Chat);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return (string) json_encode([
                'error' => $invalidArgumentException->getMessage(),
                'hint' => 'Ask the user for specific values (e.g. glucose reading, weight, blood pressure) before logging.',
            ]);
        }

        $measuredAt = $healthData->measuredAt ?? CarbonImmutable::now('UTC');
        $utcDate = $measuredAt->setTimezone('UTC')->startOfDay();
        $this->aggregateHealthDailySamplesAction->handle($user, $utcDate);

        return (string) json_encode([
            'success' => true,
            'message' => $healthData->formatForDisplay(),
            'entry_id' => $sample->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'log_type' => $schema->string()->required()
                ->enum(HealthEntryType::class)
                ->description('Type of health entry to log.'),
            'glucose_value' => $schema->number()->required()->nullable()
                ->description('Glucose reading value in mg/dL. Convert mmol/L to mg/dL (x 18.018).'),
            'glucose_unit' => $schema->string()->required()->nullable()
                ->enum(GlucoseUnit::class)
                ->description('Unit for glucose_value. Required for mmol/L values to ensure accurate logging.'),
            'glucose_reading_type' => $schema->string()->required()->nullable()
                ->enum(GlucoseReadingType::class)
                ->description('When the glucose was measured.'),
            'carbs_grams' => $schema->number()->required()->nullable()
                ->description('Carbohydrate intake in grams (can be decimal like 12.5).'),
            'protein_grams' => $schema->number()->required()->nullable()
                ->description('Protein intake in grams (can be decimal like 12.5).'),
            'fat_grams' => $schema->number()->required()->nullable()
                ->description('Fat intake in grams (can be decimal like 12.5).'),
            'calories' => $schema->integer()->required()->nullable()
                ->description('Total calories.'),
            'notes' => $schema->string()->required()->nullable()
                ->description('Food name or additional notes.'),
            'insulin_units' => $schema->number()->required()->nullable()
                ->description('Insulin dose in units.'),
            'insulin_type' => $schema->string()->required()->nullable()
                ->enum(InsulinType::class)
                ->description('Type of insulin administered.'),
            'medication_name' => $schema->string()->required()->nullable()
                ->description('Name of the medication taken.'),
            'medication_dosage' => $schema->string()->required()->nullable()
                ->description('Dosage of the medication (e.g., "500mg").'),
            'weight' => $schema->number()->required()->nullable()
                ->description('Body weight in kg. Convert lbs to kg (/ 2.205).'),
            'weight_unit' => $schema->string()->required()->nullable()
                ->enum(WeightUnit::class)
                ->description('Unit for weight. Use "lb" for pounds or "kg" for kilograms.'),
            'bp_systolic' => $schema->integer()->required()->nullable()
                ->description('Systolic blood pressure reading.'),
            'bp_diastolic' => $schema->integer()->required()->nullable()
                ->description('Diastolic blood pressure reading.'),
            'exercise_type' => $schema->string()->required()->nullable()
                ->description('Type of exercise performed (e.g., "walking", "running").'),
            'exercise_duration_minutes' => $schema->integer()->required()->nullable()
                ->description('Duration of exercise in minutes.'),
            'measured_at' => $schema->string()->required()->nullable()
                ->description('When the measurement was taken in ISO 8601 format. Only set if the user specifies a time. Leave empty for current time.'),
        ];
    }

    private function requiresGlucoseUnitClarification(HealthLogData $healthData): bool
    {
        if ($healthData->logType !== HealthEntryType::Glucose) {
            return false;
        }

        if ($healthData->glucoseValue === null || $healthData->glucoseUnit instanceof GlucoseUnit) {
            return false;
        }

        return $healthData->glucoseValue > 0 && $healthData->glucoseValue < 20;
    }
}
