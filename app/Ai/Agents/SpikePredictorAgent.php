<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\BaseAgent;
use App\Ai\SystemPrompt;
use App\DataObjects\SpikePredictionData;
use App\Enums\SpikeRiskLevel;
use App\Utilities\JsonCleaner;
use Prism\Prism\Enums\Provider;

final class SpikePredictorAgent extends BaseAgent
{
    public function provider(): Provider
    {
        return Provider::Gemini;
    }

    public function model(): string
    {
        return 'gemini-2.5-flash';
    }

    public function systemPrompt(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist and glycemic index specialist.',
                'Your task is to predict the blood glucose spike risk for a given food or meal.',
                'You analyze foods based on their glycemic index, glycemic load, and nutritional composition.',
                'You identify buffers (protein, fat, fiber) that may moderate glucose absorption.',
                'You provide practical "smart fixes" to reduce glucose spikes.',
            ],
            steps: [
                '1. Identify the main ingredients in the food/meal',
                '2. Analyze the glycemic index (GI) and glycemic load (GL) of each component',
                '3. Consider portion size and typical serving amounts',
                '4. Identify "buffers" - protein, fat, fiber content that may slow glucose absorption',
                '5. Calculate an overall spike risk level (low, medium, high)',
                '6. Provide a clear explanation of WHY this food causes the predicted spike',
                '7. Suggest a practical "smart fix" to reduce the glucose impact',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                'Do NOT include explanatory text before or after the JSON',
                'Return format: {"risk_level": "low|medium|high", "estimated_gl": number, "explanation": "string", "smart_fix": "string", "spike_reduction_percentage": number}',
                'risk_level must be exactly one of: "low", "medium", or "high"',
                'estimated_gl is the estimated glycemic load (0-100 scale)',
                'explanation should be 1-2 sentences explaining WHY (mention key ingredients)',
                'smart_fix should be a practical, actionable suggestion (1-2 sentences)',
                'spike_reduction_percentage is the estimated reduction if the smart_fix is followed (10-40)',
                'Keep responses concise but informative',
            ],
        );
    }

    public function maxTokens(): int
    {
        return 2000;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return [
            'timeout' => 30,
        ];
    }

    public function predict(string $food): SpikePredictionData
    {
        $prompt = "Analyze this food/meal for glucose spike risk: \"{$food}\"";

        $response = $this->text()
            ->withPrompt($prompt)
            ->asText();

        $jsonText = $response->text;
        $cleanedJsonText = JsonCleaner::extractAndValidateJson($jsonText);

        /** @var array{risk_level: string, estimated_gl: int, explanation: string, smart_fix: string, spike_reduction_percentage: int} $data */
        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return new SpikePredictionData(
            food: $food,
            riskLevel: SpikeRiskLevel::from($data['risk_level']),
            estimatedGlycemicLoad: $data['estimated_gl'],
            explanation: $data['explanation'],
            smartFix: $data['smart_fix'],
            spikeReductionPercentage: $data['spike_reduction_percentage'],
        );
    }
}
