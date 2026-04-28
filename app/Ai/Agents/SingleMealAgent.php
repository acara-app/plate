<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SingleMealPromptBuilder;
use App\Contracts\Ai\GeneratesSingleMeals;
use App\Data\GeneratedMealData;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[MaxTokens(8000)]
#[Timeout(60)]
final class SingleMealAgent implements Agent, GeneratesSingleMeals, HasStructuredOutput
{
    use Promptable;

    public function __construct(
        private SingleMealPromptBuilder $promptBuilder,
    ) {}

    public function instructions(): string
    {
        return "You are a professional nutritionist and chef. Generate healthy, delicious meals that are appropriate for the user's dietary needs and health conditions. Always provide accurate nutritional estimates and consider glucose impact for users with diabetes or blood sugar concerns.";
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'description' => $schema->string()->nullable()->required(),
            'meal_type' => $schema->string()->required(),
            'cuisine' => $schema->string()->nullable()->required(),
            'calories' => $schema->number()->required(),
            'protein_grams' => $schema->number()->required(),
            'carbs_grams' => $schema->number()->required(),
            'fat_grams' => $schema->number()->required(),
            'fiber_grams' => $schema->number()->nullable(),
            'ingredients' => (new ArrayType)->items($schema->string())->nullable(),
            'instructions' => (new ArrayType)->items($schema->string())->nullable(),
            'prep_time_minutes' => $schema->integer()->nullable(),
            'cook_time_minutes' => $schema->integer()->nullable(),
            'servings' => $schema->integer(),
            'dietary_tags' => (new ArrayType)->items($schema->string())->nullable(),
            'glycemic_index_estimate' => $schema->string()->nullable(),
            'glucose_impact_notes' => $schema->string()->nullable(),
        ];
    }

    public function generate(
        User $user,
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

        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        /** @var array<string, mixed> $responseArray */
        $responseArray = $response->toArray();

        return GeneratedMealData::from($responseArray);
    }
}
