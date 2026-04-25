<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\PredictsGlucoseSpikes;
use App\Data\SpikePredictionData;
use App\Utilities\JsonCleaner;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

#[Provider('openai')]
#[MaxTokens(2000)]
#[Timeout(120)]
final class SpikePredictorAgent implements Agent, PredictsGlucoseSpikes
{
    use Promptable;

    private ?string $language = null;

    private ?string $languageCode = null;

    public function withLanguage(string $language, string $languageCode): self
    {
        $this->language = $language;
        $this->languageCode = $languageCode;

        return $this;
    }

    public function instructions(): string
    {
        $output = [
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
        ];

        if ($this->language !== null && $this->languageCode !== null) {
            $output[] = sprintf(
                'Write `explanation` and `smart_fix` in %s (language code: `%s`). JSON keys, the `risk_level` enum value, and numeric fields stay in English. Use natural, idiomatic terms in %s — do not transliterate from English.',
                $this->language,
                $this->languageCode,
                $this->language,
            );
        }

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
            output: $output,
        );
    }

    public function predict(string $food): SpikePredictionData
    {
        $prompt = sprintf('Analyze this food for glucose spike risk: "%s"', $food);

        $response = $this->prompt($prompt);

        $cleanedJsonText = JsonCleaner::extractAndValidateJson((string) $response);

        /** @var array<string, mixed> $data */
        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return SpikePredictionData::from([...$data, 'food' => $food]);
    }
}
