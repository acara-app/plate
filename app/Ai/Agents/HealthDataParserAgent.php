<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Contracts\ParsesHealthData;
use App\DataObjects\HealthLogData;
use App\DataObjects\HealthParserResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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

        $result = $this->extractResponseData($response);

        return $result->toHealthLogData();
    }

    /**
     * Extract response data from structured output or JSON string.
     */
    private function extractResponseData(mixed $response): HealthParserResult
    {
        if (is_array($response)) {
            return HealthParserResult::from($response);
        }

        if (is_object($response) && property_exists($response, 'structured') && is_array($response->structured)) {
            return HealthParserResult::from($response->structured);
        }

        if (is_string($response)) {
            $data = json_decode($response, true);

            if (is_array($data)) {
                return HealthParserResult::from($data);
            }
        }

        /** @var array<string, mixed>|null $encoded */
        $encoded = json_decode(json_encode($response), true);

        return HealthParserResult::from(is_array($encoded) ? $encoded : []);
    }
}
