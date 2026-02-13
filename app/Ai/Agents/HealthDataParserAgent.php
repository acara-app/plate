<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Contracts\ParsesHealthData;
use App\DataObjects\HealthLogData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
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
            'glucose_value' => $schema->number()->nullable(),
            'glucose_reading_type' => $schema->string()->nullable(),
            'glucose_unit' => $schema->string()->nullable(),
            'carbs_grams' => $schema->integer()->nullable(),
            'insulin_units' => $schema->number()->nullable(),
            'insulin_type' => $schema->string()->nullable(),
            'medication_name' => $schema->string()->nullable(),
            'medication_dosage' => $schema->string()->nullable(),
            'weight' => $schema->number()->nullable(),
            'bp_systolic' => $schema->integer()->nullable(),
            'bp_diastolic' => $schema->integer()->nullable(),
            'exercise_type' => $schema->string()->nullable(),
            'exercise_duration_minutes' => $schema->integer()->nullable(),
            'measured_at' => $schema->string()->nullable(),
        ];
    }

    public function parse(string $message): HealthLogData
    {
        $response = $this->prompt($message);
        $responseArray = json_decode(json_encode($response), true);

        if (! is_array($responseArray)) {
            $responseArray = [];
        }

        $isHealthData = $this->getBooleanValue($responseArray, 'is_health_data', false);
        $logType = $this->getStringValue($responseArray, 'log_type', 'glucose');
        $glucoseValue = $this->getFloatOrNull($responseArray, 'glucose_value');
        $glucoseReadingType = $this->getStringOrNull($responseArray, 'glucose_reading_type');
        $glucoseUnit = $this->getStringOrNull($responseArray, 'glucose_unit');
        $carbsGrams = $this->getIntOrNull($responseArray, 'carbs_grams');
        $insulinUnits = $this->getFloatOrNull($responseArray, 'insulin_units');
        $insulinType = $this->getStringOrNull($responseArray, 'insulin_type');
        $medicationName = $this->getStringOrNull($responseArray, 'medication_name');
        $medicationDosage = $this->getStringOrNull($responseArray, 'medication_dosage');
        $weight = $this->getFloatOrNull($responseArray, 'weight');
        $bpSystolic = $this->getIntOrNull($responseArray, 'bp_systolic');
        $bpDiastolic = $this->getIntOrNull($responseArray, 'bp_diastolic');
        $exerciseType = $this->getStringOrNull($responseArray, 'exercise_type');
        $exerciseDurationMinutes = $this->getIntOrNull($responseArray, 'exercise_duration_minutes');
        $measuredAtString = $this->getStringOrNull($responseArray, 'measured_at');

        $measuredAt = null;
        if ($measuredAtString !== null) {
            $measuredAt = Carbon::parse($measuredAtString);
        }

        return new HealthLogData(
            isHealthData: $isHealthData,
            logType: $logType,
            glucoseValue: $glucoseValue,
            glucoseReadingType: $glucoseReadingType,
            glucoseUnit: $glucoseUnit,
            carbsGrams: $carbsGrams,
            insulinUnits: $insulinUnits,
            insulinType: $insulinType,
            medicationName: $medicationName,
            medicationDosage: $medicationDosage,
            weight: $weight,
            bpSystolic: $bpSystolic,
            bpDiastolic: $bpDiastolic,
            exerciseType: $exerciseType,
            exerciseDurationMinutes: $exerciseDurationMinutes,
            measuredAt: $measuredAt,
        );
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function getBooleanValue(array $response, string $key, bool $default): bool
    {
        $value = $response[$key] ?? null;

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function getStringValue(array $response, string $key, string $default): string
    {
        $value = $response[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function getStringOrNull(array $response, string $key): ?string
    {
        $value = $response[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function getFloatOrNull(array $response, string $key): ?float
    {
        $value = $response[$key] ?? null;

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function getIntOrNull(array $response, string $key): ?int
    {
        $value = $response[$key] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}
