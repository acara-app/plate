<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Data\CaffeineGuidanceData;
use App\Data\CaffeineLimitData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(2500)]
#[Timeout(60)]
final class CaffeineGuidanceAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a concise caffeine safety explainer for a public health calculator.',
                'The application provides deterministic caffeine limits based on EFSA guidelines (3 mg/kg body weight).',
                'You personalize the wording only. Never change, recalculate, or contradict the provided limit_mg, status, or reasons.',
                'Never give medical diagnoses. Always recommend consulting a clinician for condition-related limits.',
            ],
            steps: [
                '1. Read the deterministic assessment JSON including weight, age, sex, and detected conditions.',
                '2. Write a direct answer to "How much is too much?" for the user.',
                '3. Keep copy short, specific, and practical.',
                '4. If conditions are detected (pregnancy, heart condition, anxiety, etc.), address them condition-specifically.',
                '5. If the context names drinks, use them as examples without estimating their caffeine unless the user supplied exact amounts.',
                '6. Mention medical/medication caution only in the safety note, not as a long disclaimer.',
            ],
            output: [
                'Return only the structured response requested by the schema.',
                'Component copy must fit compact UI cards.',
                'Use the exact provided limit_mg value when present.',
                'Do not recommend drink schedules, cups, coffee brands, or sleep cutoff times.',
                'For condition_sections, only include conditions that are actually detected in the assessment.',
                'Tone mapping for condition_sections: pregnancy/breastfeeding/trying_to_conceive use "green"; heart_condition/medication use "amber"; anxiety/insomnia/gerd use "blue"; otherwise "slate".',
                'For pregnancy/breastfeeding/trying_to_conceive sections, set link_url to "https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy" and link_label to "ACOG guidance".',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $tone = ['green', 'amber', 'red', 'blue', 'slate'];
        $stringList = new ArrayType()->items($schema->string())->min(2)->max(4);

        return [
            'summary' => $schema->string()->required()->description('One concise sentence summarizing the caffeine limit.'),
            'verdict_card' => $schema->object(fn (JsonSchema $s): array => [
                'title' => $s->string()->required(),
                'body' => $s->string()->required(),
                'badge' => $s->string()->required(),
                'tone' => $s->string()->enum($tone)->required(),
                'limit_mg' => $s->integer()->required()->nullable(),
            ])->withoutAdditionalProperties()->required(),
            'limit_gauge' => $schema->object(fn (JsonSchema $s): array => [
                'label' => $s->string()->required(),
                'value_label' => $s->string()->required(),
                'limit_mg' => $s->integer()->required()->nullable(),
                'max_mg' => $s->integer()->required(),
                'tone' => $s->string()->enum($tone)->required(),
                'caption' => $s->string()->required(),
            ])->withoutAdditionalProperties()->required(),
            'guidance_list' => $schema->object(fn (JsonSchema $s): array => [
                'title' => $s->string()->required(),
                'items' => $stringList->required(),
            ])->withoutAdditionalProperties()->required(),
            'safety_note' => $schema->object(fn (JsonSchema $s): array => [
                'title' => $s->string()->required(),
                'body' => $s->string()->required(),
                'items' => (new ArrayType)->items($s->string())->min(2)->max(3)->required(),
            ])->withoutAdditionalProperties()->required(),
            'condition_sections' => (new ArrayType)->items(
                $schema->object(fn (JsonSchema $s): array => [
                    'condition' => $s->string()->required(),
                    'title' => $s->string()->required(),
                    'body' => $s->string()->required(),
                    'tone' => $s->string()->enum($tone)->required(),
                    'link_url' => $s->string()->nullable(),
                    'link_label' => $s->string()->nullable(),
                ])->withoutAdditionalProperties()
            )->nullable(),
        ];
    }

    public function assess(CaffeineLimitData $limit, ?string $context): CaffeineGuidanceData
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($this->buildPrompt($limit, $context));

        return CaffeineGuidanceData::from($response->toArray());
    }

    private function buildPrompt(CaffeineLimitData $limit, ?string $context): string
    {
        $payload = [
            'assessment' => $limit->toArray(),
            'optional_context' => filled($context) ? $context : null,
            'copy_rules' => [
                'answer_first' => true,
                'max_verdict_body_words' => 32,
                'max_guidance_items' => 4,
                'limit_mg_is_authoritative' => true,
                'weight_kg_is_primary_factor' => true,
                'use_drink_context_without_inventing_milligrams' => true,
                'address_detected_conditions_personally' => true,
                'never_recommend_a_specific_brand_or_product' => true,
            ],
        ];

        return "Create the caffeine guidance UI copy from this deterministic assessment JSON:\n"
            .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
