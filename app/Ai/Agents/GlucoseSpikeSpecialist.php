<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Data\SpikePredictionData;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

#[Provider('openai')]
#[MaxTokens(2000)]
#[Timeout(120)]
final class GlucoseSpikeSpecialist implements Agent, CanActAsTool, HasStructuredOutput
{
    use Promptable;

    public function name(): string
    {
        return 'glucose_spike_specialist';
    }

    public function description(): string
    {
        return 'Delegate blood sugar spike questions and worries about foods, meals, restaurant orders, and food comparisons to a structured specialist. Pass a complete, self-contained task; the specialist cannot see the chat history. It returns a structured glucose spike risk result.';
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
            'For COMPARISONS: explanation should compare both foods GI/GL, smart_fix should clearly state which is better.',
            'If the task expresses worry or concern, keep the answer calm, practical, and specific without overstating certainty.',
            'Keep responses concise but informative.',
        ];

        $user = Auth::user();

        if ($user instanceof User) {
            ['label' => $label, 'code' => $code] = LanguageUtil::resolve($user->locale);

            $output[] = sprintf(
                'Write `explanation` and `smart_fix` in %s (language code: `%s`). Structured field names, the `risk_level` enum value, and numeric fields stay in English. Use natural, idiomatic terms in %s; do not transliterate from English.',
                $label,
                $code,
                $label,
            );
        }

        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist and glycemic index specialist.',
                'You help people who are asking about or worried about blood sugar spikes from foods and meals.',
                'Your task is to predict the blood glucose spike risk for foods and meals.',
                'You analyze foods based on their glycemic index, glycemic load, portion size, and nutritional composition.',
                'You identify buffers (protein, fat, fiber) that may moderate glucose absorption.',
                'You provide practical advice to help people make better food choices.',
            ],
            steps: [
                '1. If the task contains "vs", "versus", or "compared to", treat it as a COMPARISON and analyze BOTH foods.',
                '2. For comparisons: determine which food is BETTER for blood sugar and explain why.',
                '3. Analyze the glycemic index (GI) and glycemic load (GL) of each food or meal.',
                '4. Consider portion size and typical serving amounts.',
                '5. Identify "buffers" - protein, fat, fiber that slow glucose absorption.',
                '6. Calculate an overall spike risk level (low, medium, high).',
                '7. For comparisons: smart_fix should recommend the WINNER and why.',
                '8. For single foods or meals: smart_fix should be a practical tip to reduce the spike.',
            ],
            output: $output,
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return SpikePredictionData::jsonSchema($schema);
    }
}
