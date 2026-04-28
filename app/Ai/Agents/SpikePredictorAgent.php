<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\PredictsGlucoseSpikes;
use App\Data\SpikePredictionData;
use App\Enums\SpikeRiskLevel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[Provider('openai')]
#[MaxTokens(2000)]
#[Timeout(120)]
final class SpikePredictorAgent implements Agent, HasStructuredOutput, PredictsGlucoseSpikes
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
            'Return the structured response requested by the schema.',
            'risk_level must be exactly one of: "low", "medium", or "high".',
            'estimated_gl must be a whole number from 0 to 100.',
            'spike_reduction_percentage must be a whole number from 0 to 100.',
            'explanation should explain WHY; for comparisons, compare both foods.',
            'smart_fix should be a practical tip for single foods and recommend the winner for comparisons.',
            'For COMPARISONS: explanation should compare both foods GI/GL, smart_fix should clearly state which is better',
            'Keep responses concise but informative',
        ];

        if ($this->language !== null && $this->languageCode !== null) {
            $output[] = sprintf(
                'Write `explanation` and `smart_fix` in %s (language code: `%s`). Structured field names, the `risk_level` enum value, and numeric fields stay in English. Use natural, idiomatic terms in %s — do not transliterate from English.',
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

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'risk_level' => $schema->string()->enum(SpikeRiskLevel::class)->required(),
            'estimated_gl' => $schema->integer()->min(0)->max(100)->required(),
            'explanation' => $schema->string()->required(),
            'smart_fix' => $schema->string()->required(),
            'spike_reduction_percentage' => $schema->integer()->min(0)->max(100)->required(),
        ];
    }

    public function predict(string $food): SpikePredictionData
    {
        $prompt = sprintf('Analyze this food for glucose spike risk: "%s"', $food);

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        return SpikePredictionData::from([...$response->toArray(), 'food' => $food]);
    }
}
