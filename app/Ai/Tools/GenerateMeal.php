<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\GetUserProfileContextAction;
use App\Ai\BaseAgent;
use App\Ai\MealPlanPromptBuilder;
use App\Models\User;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class GenerateMeal implements Tool
{
    public function __construct(
        private readonly MealPlanPromptBuilder $promptBuilder,
        private readonly GetUserProfileContextAction $profileContext,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Generate a personalized meal suggestion based on user preferences, dietary restrictions, and nutritional goals. Returns a complete meal with nutritional information.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return json_encode([
                'error' => 'User not authenticated',
                'meal' => null,
            ]);
        }

        $mealType = $request['meal_type'] ?? 'any';
        $cuisine = $request['cuisine'] ?? null;
        $maxCalories = $request['max_calories'] ?? null;
        $specificRequest = $request['specific_request'] ?? null;

        $profileContext = $this->profileContext->handle($user);

        $prompt = $this->buildMealPrompt(
            $user,
            $mealType,
            $cuisine,
            $maxCalories,
            $specificRequest,
            $profileContext['context']
        );

        try {
            $meal = $this->generateMeal($prompt);

            return json_encode([
                'success' => true,
                'meal' => $meal,
            ]);
        } catch (Exception $e) {
            return json_encode([
                'error' => 'Failed to generate meal: '.$e->getMessage(),
                'meal' => null,
            ]);
        }
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'meal_type' => $schema->string()
                ->enum(['breakfast', 'lunch', 'dinner', 'snack', 'any'])
                ->description('The type of meal to generate')
                ->required(),
            'cuisine' => $schema->string()
                ->description('Preferred cuisine style (e.g., Mediterranean, Asian, Mexican, Italian)'),
            'max_calories' => $schema->integer()
                ->description('Maximum calories for the meal'),
            'specific_request' => $schema->string()
                ->description('Any specific requirements or preferences (e.g., "high protein", "quick to prepare", "comfort food")'),
        ];
    }

    /**
     * Build the prompt for meal generation.
     */
    private function buildMealPrompt(
        User $user,
        string $mealType,
        ?string $cuisine,
        ?int $maxCalories,
        ?string $specificRequest,
        string $profileContext
    ): string {
        $parts = [
            'Generate a single meal suggestion for a user with the following profile:',
            '',
            $profileContext,
            '',
            'MEAL REQUIREMENTS:',
            "- Meal Type: {$mealType}",
        ];

        if ($cuisine !== null) {
            $parts[] = "- Cuisine Style: {$cuisine}";
        }

        if ($maxCalories !== null) {
            $parts[] = "- Maximum Calories: {$maxCalories}";
        }

        if ($specificRequest !== null) {
            $parts[] = "- Specific Request: {$specificRequest}";
        }

        $parts[] = '';
        $parts[] = 'Please provide the meal in the following JSON format:';
        $parts[] = json_encode([
            'name' => 'Meal name',
            'description' => 'Brief description of the meal',
            'meal_type' => $mealType,
            'cuisine' => 'Cuisine style',
            'calories' => 0,
            'protein_g' => 0,
            'carbs_g' => 0,
            'fat_g' => 0,
            'fiber_g' => 0,
            'ingredients' => ['ingredient 1', 'ingredient 2'],
            'instructions' => ['Step 1', 'Step 2'],
            'prep_time_minutes' => 0,
            'cook_time_minutes' => 0,
            'servings' => 1,
            'dietary_tags' => ['tag1', 'tag2'],
            'glycemic_index_estimate' => 'low|medium|high',
            'glucose_impact_notes' => 'Notes about glucose impact',
        ], JSON_PRETTY_PRINT);

        return implode("\n", $parts);
    }

    /**
     * Generate a meal using the AI.
     *
     * @return array<string, mixed>
     */
    private function generateMeal(string $prompt): array
    {
        $response = $this->getAIResponse($prompt);

        $jsonText = $this->extractJson($response);
        $data = json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * Get AI response for meal generation.
     */
    private function getAIResponse(string $prompt): string
    {
        $agent = new class extends BaseAgent
        {
            public function systemPrompt(): string
            {
                return 'You are a professional nutritionist and chef. Generate healthy, delicious meals that are appropriate for the user\'s dietary needs and health conditions. Always provide accurate nutritional estimates and consider glucose impact for users with diabetes or blood sugar concerns.';
            }
        };

        $response = $agent->text()
            ->withPrompt($prompt)
            ->asText();

        return $response->text;
    }

    /**
     * Extract JSON from AI response.
     */
    private function extractJson(string $response): string
    {
        $response = mb_trim($response);

        if (str_starts_with($response, '```json')) {
            $response = preg_replace('/^```json\s*/', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
        } elseif (str_starts_with($response, '```')) {
            $response = preg_replace('/^```\s*/', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
        }

        return mb_trim($response);
    }
}
