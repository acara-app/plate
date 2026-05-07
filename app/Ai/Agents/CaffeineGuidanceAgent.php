<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Data\CaffeineGuidanceData;
use App\Data\CaffeineLimitData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
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

    /**
     * @var array<int, string>
     */
    private const array TONES = ['green', 'amber', 'red', 'blue', 'slate'];

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
                '1. Read the deterministic assessment JSON including weight, sex, and detected conditions.',
                '2. Write a direct answer to "How much is too much?" for the user.',
                '3. Keep copy short, specific, and practical.',
                '4. If conditions are detected (pregnancy, heart condition, anxiety, etc.), address them condition-specifically.',
                '5. If the context names drinks, use them as examples without estimating their caffeine unless the user supplied exact amounts.',
                '6. Mention medical/medication caution only in the safety note, not as a long disclaimer.',
            ],
            output: [
                'Return only the structured response requested by the schema.',
                'Always return every required top-level field: summary, verdict_card, limit_gauge, timing_card, guidance_list, safety_note. condition_sections is the only optional field and may be an empty array or null when no conditions are detected.',
                'Component copy must fit compact UI cards.',
                'Use the exact provided limit_mg value when present.',
                'For condition_sections, only include conditions that are actually detected in the assessment.',
                'Tone mapping for condition_sections: pregnancy/breastfeeding/trying_to_conceive use "green"; heart_condition/medication use "amber"; anxiety/insomnia/gerd use "blue"; otherwise "slate".',
                'For pregnancy/breastfeeding/trying_to_conceive sections, set link_url to "https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy" and link_label to "ACOG guidance".',
                'For timing_card: extract bedtime_24h (0-23 integer) from optional_context if a bedtime is mentioned (e.g., "I sleep at 11pm" -> 23, "bedtime is 10:30" -> 22); otherwise default bedtime_24h to 23. Compute cutoff_24h as (bedtime_24h - 6 + 24) % 24. The title must include the cutoff time in a natural sentence; the body should briefly explain the ~5h half-life and recommend stopping ~6h before bed. cutoff_label and bedtime_label are short locale-formatted time strings (English: "5:00 pm", "11:00 pm"; Mongolian: "17:00", "23:00"). For insomnia in conditions, the body may suggest stopping earlier than 6h.',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $this->summarySchema($schema),
            'verdict_card' => $this->verdictCardSchema($schema),
            'limit_gauge' => $this->limitGaugeSchema($schema),
            'timing_card' => $this->timingCardSchema($schema),
            'guidance_list' => $this->guidanceListSchema($schema),
            'safety_note' => $this->safetyNoteSchema($schema),
            'condition_sections' => $this->conditionSectionsSchema($schema),
        ];
    }

    public function assess(CaffeineLimitData $limit, ?string $context, string $locale = 'en'): CaffeineGuidanceData
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($this->buildPrompt($limit, $context, $locale));

        return CaffeineGuidanceData::from($response->toArray());
    }

    private function summarySchema(JsonSchema $schema): Type
    {
        return $schema->string()
            ->required()
            ->description('One concise sentence summarizing the caffeine limit.');
    }

    private function verdictCardSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object(fn (JsonSchema $s): array => [
            'title' => $s->string()->required(),
            'body' => $s->string()->required(),
            'badge' => $s->string()->required(),
            'tone' => $s->string()->enum(self::TONES)->required(),
            'limit_mg' => $s->integer()->required()->nullable(),
        ])->withoutAdditionalProperties()->required();
    }

    private function limitGaugeSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object(fn (JsonSchema $s): array => [
            'label' => $s->string()->required(),
            'value_label' => $s->string()->required(),
            'limit_mg' => $s->integer()->required()->nullable(),
            'max_mg' => $s->integer()->required(),
            'tone' => $s->string()->enum(self::TONES)->required(),
            'caption' => $s->string()->required(),
        ])->withoutAdditionalProperties()->required();
    }

    private function timingCardSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object(fn (JsonSchema $s): array => [
            'title' => $s->string()->required(),
            'body' => $s->string()->required(),
            'cutoff_label' => $s->string()->required(),
            'bedtime_label' => $s->string()->required(),
            'cutoff_24h' => $s->integer()->min(0)->max(23)->required(),
            'bedtime_24h' => $s->integer()->min(0)->max(23)->required(),
        ])->withoutAdditionalProperties()->required();
    }

    private function guidanceListSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object(fn (JsonSchema $s): array => [
            'title' => $s->string()->required(),
            'items' => (new ArrayType)
                ->items($s->string())
                ->min(2)
                ->max(4)
                ->required(),
        ])->withoutAdditionalProperties()->required();
    }

    private function safetyNoteSchema(JsonSchema $schema): ObjectType
    {
        return $schema->object(fn (JsonSchema $s): array => [
            'title' => $s->string()->required(),
            'body' => $s->string()->required(),
            'items' => (new ArrayType)
                ->items($s->string())
                ->min(2)
                ->max(3)
                ->required(),
        ])->withoutAdditionalProperties()->required();
    }

    private function conditionSectionsSchema(JsonSchema $schema): ArrayType
    {
        return (new ArrayType)->items(
            $schema->object(fn (JsonSchema $s): array => [
                'condition' => $s->string()->required(),
                'title' => $s->string()->required(),
                'body' => $s->string()->required(),
                'tone' => $s->string()->enum(self::TONES)->required(),
                'link_url' => $s->string()->nullable(),
                'link_label' => $s->string()->nullable(),
            ])->withoutAdditionalProperties()
        )->nullable();
    }

    private function buildPrompt(CaffeineLimitData $limit, ?string $context, string $locale): string
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
            'locale' => $locale,
            'language_instruction' => $locale === 'mn'
                ? 'Respond entirely in Mongolian using clear, professional, and friendly medical tone. Use Mongolian medical terminology where appropriate.'
                : 'Respond in English.',
        ];

        return "Create the caffeine guidance UI copy from this deterministic assessment JSON:\n"
            .json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
