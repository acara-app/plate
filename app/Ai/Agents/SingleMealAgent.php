<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SingleMealPromptBuilder;
use App\DataObjects\GeneratedMealData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class SingleMealAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private SingleMealPromptBuilder $promptBuilder,
    ) {}

    public function instructions(): string
    {
        return 'You are a professional nutritionist and chef. Generate healthy, delicious meals that are appropriate for the user\'s dietary needs and health conditions. Always provide accurate nutritional estimates and consider glucose impact for users with diabetes or blood sugar concerns.';
    }

    public function maxTokens(): int
    {
        return 8000;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return [
            'timeout' => 60,
        ];
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'description' => $schema->string(),
            'meal_type' => $schema->string()->required(),
            'cuisine' => $schema->string(),
            'calories' => $schema->number()->required(),
            'protein_grams' => $schema->number()->required(),
            'carbs_grams' => $schema->number()->required(),
            'fat_grams' => $schema->number()->required(),
            'fiber_grams' => $schema->number(),
            'ingredients' => $schema->array(),
            'instructions' => $schema->array(),
            'prep_time_minutes' => $schema->integer(),
            'cook_time_minutes' => $schema->integer(),
            'servings' => $schema->integer(),
            'dietary_tags' => $schema->array(),
            'glycemic_index_estimate' => $schema->string(),
            'glucose_impact_notes' => $schema->string(),
        ];
    }

    public function generate(
        \App\Models\User $user,
        string $mealType,
        ?string $cuisine = null,
        ?int $maxCalories = null,
        ?string $specificRequest = null,
    ): GeneratedMealData {
        $prompt = $this->promptBuilder->handle(
            $user,
            $mealType,
            $cuisine,
            $maxCalories,
            $specificRequest,
        );

        $response = $this->prompt($prompt);

        return GeneratedMealData::from(collect($response)->toArray());
    }
}
