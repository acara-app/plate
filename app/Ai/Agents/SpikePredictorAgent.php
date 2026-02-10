<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\BaseAgent;
use App\Ai\SystemPrompt;
use App\DataObjects\SpikePredictionData;
use App\Enums\ModelName;
use App\Enums\SpikeRiskLevel;
use App\Utilities\JsonCleaner;

final class SpikePredictorAgent extends BaseAgent
{
    public function modelName(): ModelName
    {
        return ModelName::GPT_5_MINI;
    }

    public function systemPrompt(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist and glycemic index specialist.',
                'Your task is to predict the blood glucose spike risk for foods.',
                'You analyze foods based on their glycemic index, glycemic load, and nutritional composition.',
                'You identify buffers (protein, fat, fiber) that may moderate glucose absorption.',
                'You provide practical advice to help people make better food choices.',
            ],
            steps: [
                '1. If the query contains "vs", "versus", or "compared to", treat it as a COMPARISON and analyze BOTH foods',
                '2. For comparisons: Determine which food is BETTER for blood sugar and explain why',
                '3. Analyze the glycemic index (GI) and glycemic load (GL) of each food',
                '4. Consider portion size and typical serving amounts',
                '5. Identify "buffers" - protein, fat, fiber that slow glucose absorption',
                '6. Calculate an overall spike risk level (low, medium, high)',
                '7. For comparisons: smart_fix should recommend the WINNER and why',
                '8. For single foods: smart_fix should be a practical tip to reduce spike',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                '',
                'Return format:',
                '{',
                '  "risk_level": "low|medium|high",',
                '  "estimated_gl": number (0-100),',
                '  "explanation": "string explaining WHY (for comparisons: compare both foods)",',
                '  "smart_fix": "string (for comparisons: recommend the winner; for single: practical tip)",',
                '  "spike_reduction_percentage": number (10-60)',
                '}',
                '',
                'For COMPARISONS: explanation should compare both foods GI/GL, smart_fix should clearly state which is better',
                'risk_level must be exactly one of: "low", "medium", or "high"',
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
            'timeout' => 120,
        ];
    }

    public function predict(string $food): SpikePredictionData
    {
        $prompt = "Analyze this food for glucose spike risk: \"{$food}\"";

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
