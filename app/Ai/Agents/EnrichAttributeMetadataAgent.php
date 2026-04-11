<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Data\AttributeMetadataData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[Provider('gemini')]
#[MaxTokens(8192)]
#[Timeout(60)]
final class EnrichAttributeMetadataAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist specializing in dietary planning for health conditions, allergies, and dietary restrictions.',
                'Your task is to generate comprehensive dietary metadata for user profile attributes.',
                'You have extensive knowledge of medical nutrition therapy, food science, and allergen management.',
            ],
            steps: [
                '1. Analyze the category (health_condition, allergy, restriction, etc.)',
                '2. Understand the specific value (e.g., "Type 2 Diabetes", "Peanuts", "Vegan")',
                '3. Generate appropriate dietary rules, safety levels, and food guidance',
                '4. Only include fields relevant to the category (e.g., no "carb_limit" for allergies)',
            ],
            output: [
                'Return the metadata using the provided structured format.',
                'Include only fields that make sense for the category:',
                '- Health conditions: safety_level, dietary_rules, foods_to_avoid, foods_to_prioritize, carb_limit_per_meal_g, min_fibre_per_meal_g',
                '- Allergies: safety_level, hidden_sources, dietary_rules',
                '- Restrictions (religious/cultural): requirements',
                '- Dietary patterns: general_advice',
                'safety_level should be "critical" for life-threatening conditions (severe allergies, celiac), "warning" for manageable conditions, "info" for preferences',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'safety_level' => $schema->string()->enum(['critical', 'warning', 'info'])->required(),
            'dietary_rules' => new ArrayType()->items($schema->string())->description('List of dietary rules or guidelines to follow'),
            'foods_to_avoid' => new ArrayType()->items($schema->string())->description('List of foods that should be avoided'),
            'foods_to_prioritize' => new ArrayType()->items($schema->string())->description('List of foods that should be prioritized'),
            'carb_limit_per_meal_g' => $schema->integer()->description('Maximum carbohydrates per meal in grams (for diabetes-related conditions)'),
            'min_fibre_per_meal_g' => $schema->integer()->description('Minimum fiber per meal in grams'),
            'hidden_sources' => new ArrayType()->items($schema->string())->description('Common hidden sources of allergens in processed foods'),
            'requirements' => new ArrayType()->items($schema->string())->description('Requirements for religious/cultural dietary restrictions'),
            'general_advice' => $schema->string()->description('General dietary advice or notes'),
        ];
    }

    /**
     * @codeCoverageIgnore
     */
    public function enrich(string $category, string $value): AttributeMetadataData
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt(
            sprintf('Generate dietary metadata for category: %s, value: %s. ', $category, $value).
                'Provide comprehensive dietary rules and guidance appropriate for this attribute.',
        );

        return AttributeMetadataData::from($response->toArray());
    }
}
