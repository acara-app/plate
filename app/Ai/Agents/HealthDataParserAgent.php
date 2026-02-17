<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Contracts\ParsesHealthData;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;
use BackedEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Date;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class HealthDataParserAgent implements Agent, HasStructuredOutput, ParsesHealthData
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INST'
You are a health data parser. Determine if the user is RECORDING health measurements (is_health_data: true) or ASKING A QUESTION (is_health_data: false).

INTENT DETECTION - Most important field:
- is_health_data: true → User is logging/recording a specific measurement with values
- is_health_data: false → User is asking a question, seeking advice, or general conversation

Examples of LOGGING (is_health_data: true):
- "My glucose is 140" → {is_health_data: true, log_type: "glucose", glucose_value: 140, glucose_reading_type: "random"}
- "My glucose is 7.8 mmol/L" → {is_health_data: true, log_type: "glucose", glucose_value: 140.54, glucose_unit: "mmol/L"}
- "Took 5 units of insulin" → {is_health_data: true, log_type: "insulin", insulin_units: 5, insulin_type: "bolus"}
- "Ate 45g carbs" → {is_health_data: true, log_type: "food", carbs_grams: 45}
- "Weigh 180 lbs" → {is_health_data: true, log_type: "vitals", weight: 81.65}
- "Weight 180 pounds" → {is_health_data: true, log_type: "vitals", weight: 81.65}
- "BP 120/80" → {is_health_data: true, log_type: "vitals", bp_systolic: 120, bp_diastolic: 80}
- "Blood pressure 120 over 80" → {is_health_data: true, log_type: "vitals", bp_systolic: 120, bp_diastolic: 80}
- "Walked 30 minutes" → {is_health_data: true, log_type: "exercise", exercise_type: "walking", exercise_duration_minutes: 30}
- "Took metformin 500mg" → {is_health_data: true, log_type: "meds", medication_name: "metformin", medication_dosage: "500mg"}
- "Fasting glucose 95" → {is_health_data: true, log_type: "glucose", glucose_value: 95, glucose_reading_type: "fasting"}
- "Glucose before meal 110" → {is_health_data: true, log_type: "glucose", glucose_value: 110, glucose_reading_type: "before-meal"}
- "Post-meal glucose 145" → {is_health_data: true, log_type: "glucose", glucose_value: 145, glucose_reading_type: "post-meal"}
- "mi glucosa es 140" (Spanish) → {is_health_data: true, log_type: "glucose", glucose_value: 140}

Examples of QUESTIONS (is_health_data: false):
- "What is a normal glucose level?" → {is_health_data: false, log_type: "glucose"}
- "Is 140 high for glucose?" → {is_health_data: false, log_type: "glucose"}
- "How many carbs should I eat?" → {is_health_data: false, log_type: "food"}
- "Can I eat pizza with diabetes?" → {is_health_data: false, log_type: "food"}

Rules:
- ALWAYS set is_health_data correctly - this is the most important field
- Works in ANY language - understand context regardless of language
- Weight in lbs/pounds → convert to kg (÷ 2.205)
- Glucose in mmol/L → convert to mg/dL (× 18.018)
- If no unit specified for glucose, assume "mg/dL"
- If no unit specified for weight, assume "kg"
- glucose_reading_type must be one of: "fasting", "before-meal", "post-meal", "random"
- insulin_type must be one of: "basal", "bolus", "mixed"
- If time mentioned (e.g., "this morning", "at 8am"), include in measured_at as ISO format
- If no time mentioned, set measured_at to null (will be set to current time)
- If is_health_data is false, still provide a best-guess log_type based on the topic
INST;
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\BooleanType|\Illuminate\JsonSchema\Types\IntegerType|\Illuminate\JsonSchema\Types\NumberType|\Illuminate\JsonSchema\Types\StringType>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'is_health_data' => $schema->boolean()->required(),
            'log_type' => $schema->string()->required(),
            'glucose_value' => $schema->number(),
            'glucose_reading_type' => $schema->string(),
            'glucose_unit' => $schema->string(),
            'carbs_grams' => $schema->integer(),
            'insulin_units' => $schema->number(),
            'insulin_type' => $schema->string(),
            'medication_name' => $schema->string(),
            'medication_dosage' => $schema->string(),
            'weight' => $schema->number(),
            'bp_systolic' => $schema->integer(),
            'bp_diastolic' => $schema->integer(),
            'exercise_type' => $schema->string(),
            'exercise_duration_minutes' => $schema->integer(),
            'measured_at' => $schema->string(),
        ];
    }

    public function parse(string $message): HealthLogData
    {
        $response = $this->prompt($message);

        // Extract response data from structured output or JSON string
        $data = $this->extractResponseData($response);

        return new HealthLogData(
            isHealthData: $this->toBoolean($data['is_health_data'] ?? null, false),
            logType: $this->toLogType($data['log_type'] ?? null),
            glucoseValue: $this->toFloat($data['glucose_value'] ?? null),
            glucoseReadingType: $this->toEnum($data['glucose_reading_type'] ?? null, GlucoseReadingType::class),
            glucoseUnit: $this->toEnum($data['glucose_unit'] ?? null, GlucoseUnit::class),
            carbsGrams: $this->toInt($data['carbs_grams'] ?? null),
            insulinUnits: $this->toFloat($data['insulin_units'] ?? null),
            insulinType: $this->toEnum($data['insulin_type'] ?? null, InsulinType::class),
            medicationName: $this->toString($data['medication_name'] ?? null),
            medicationDosage: $this->toString($data['medication_dosage'] ?? null),
            weight: $this->toFloat($data['weight'] ?? null),
            bpSystolic: $this->toInt($data['bp_systolic'] ?? null),
            bpDiastolic: $this->toInt($data['bp_diastolic'] ?? null),
            exerciseType: $this->toString($data['exercise_type'] ?? null),
            exerciseDurationMinutes: $this->toInt($data['exercise_duration_minutes'] ?? null),
            measuredAt: $this->toDateTime($data['measured_at'] ?? null),
        );
    }

    /**
     * Extract response data from structured output or JSON string.
     *
     * @return array<string, mixed>
     *
     * @codeCoverageIgnore
     *
     * @phpstan-return array<string, mixed>
     */
    private function extractResponseData(mixed $response): array
    {
        if (is_object($response) && property_exists($response, 'structured') && is_array($response->structured)) {
            // @phpstan-ignore return.type
            return $response->structured;
        }

        // @phpstan-ignore cast.string
        $json = (string) $response;
        // @phpstan-ignore argument.type
        $data = json_decode($json, true);

        if (is_array($data)) {
            // @phpstan-ignore return.type
            return $data;
        }

        /** @var array<string, mixed>|null $encoded */
        // @phpstan-ignore argument.type
        $encoded = json_decode(json_encode($response), true);

        // @phpstan-ignore argument.type
        return is_array($encoded) ? $encoded : [];
    }

    private function toBoolean(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }

    private function toLogType(mixed $value): HealthEntryType
    {
        $string = $this->toString($value);

        return HealthEntryType::tryFrom($string ?? '') ?? HealthEntryType::Glucose;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enumClass
     * @return T|null
     */
    private function toEnum(mixed $value, string $enumClass): ?BackedEnum
    {
        $string = $this->toString($value);

        if ($string === null) {
            return null;
        }

        return $enumClass::tryFrom($string);
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @codeCoverageIgnore
     */
    private function toInt(mixed $value): ?int
    {
        if ($value === null || $value === 'null') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toString(mixed $value): ?string
    {
        if ($value === null || $value === 'null' || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (is_scalar($value) ? (string) $value : null);
    }

    private function toDateTime(mixed $value): ?\Carbon\CarbonInterface
    {
        $string = $this->toString($value);

        return $string !== null ? Date::parse($string) : null;
    }
}
