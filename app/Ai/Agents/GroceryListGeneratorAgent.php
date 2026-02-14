<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\DataObjects\ExtractedIngredientData;
use App\DataObjects\GroceryItemData;
use App\DataObjects\GroceryListData;
use App\DataObjects\IngredientData;
use App\Models\MealPlan;
use App\Utilities\JsonCleaner;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Spatie\LaravelData\DataCollection;

#[Provider('gemini')]
#[MaxTokens(67000)]
#[Timeout(120)]
final class GroceryListGeneratorAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert grocery list optimizer and food categorizer.',
                'Your task is to consolidate ingredients from a meal plan into an organized grocery list.',
                'You intelligently combine similar ingredients and sum their quantities.',
                'You categorize items into logical grocery store sections.',
            ],
            steps: [
                '1. Analyze all ingredients provided from the meal plan',
                '2. Identify similar ingredients that should be combined (e.g., "chicken breast" and "boneless chicken" are the same)',
                '3. Sum quantities where possible, keeping units consistent',
                '4. Categorize each item into the appropriate grocery category',
                '5. Return a clean, consolidated grocery list',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                'Do NOT include explanatory text before or after the JSON',
                'The JSON must be parseable by json_decode()',
                'Use double quotes for all strings',
                'Ensure all brackets and braces are properly closed',
                'Return format: {"items": [{"name": "Item Name", "quantity": "Combined Quantity", "category": "Category Name", "days": [1, 2, 3]}]}',
                'The "days" array must contain the day numbers (1-based) where the ingredient is used',
                'Valid categories are: Produce, Dairy, Meat & Seafood, Pantry, Frozen, Bakery, Beverages, Condiments & Sauces, Herbs & Spices, Other',
            ],
        );
    }

    public function maxTokens(): int
    {
        return 67000;
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

    /**
     * Generate a consolidated grocery list from a meal plan.
     */
    public function generate(MealPlan $mealPlan): GroceryListData
    {
        $ingredients = $this->extractIngredients($mealPlan);

        if ($ingredients === []) {
            return new GroceryListData(
                items: new DataCollection(GroceryItemData::class, []),
            );
        }

        $prompt = $this->buildPrompt($ingredients, $mealPlan);
        $jsonText = $this->generateGroceryListJson($prompt);
        $cleanedJsonText = JsonCleaner::extractAndValidateJson($jsonText);

        /** @var array{items: array<int, array{name: string, quantity: string, category: string}>} $data */
        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return GroceryListData::from($data);
    }

    /**
     * Extract all ingredients from the meal plan's meals.
     *
     * @return list<ExtractedIngredientData>
     */
    private function extractIngredients(MealPlan $mealPlan): array
    {
        $ingredients = [];

        foreach ($mealPlan->meals as $meal) {
            if ($meal->ingredients === null) {
                continue;
            }
            if (count($meal->ingredients) === 0) {
                continue;
            }
            foreach ($meal->ingredients as $ingredient) {
                $ingredientData = IngredientData::from($ingredient);
                $ingredients[] = new ExtractedIngredientData(
                    name: $ingredientData->name,
                    quantity: $ingredientData->quantity,
                    day: $meal->day_number,
                    meal: $meal->name,
                );
            }
        }

        return $ingredients;
    }

    /**
     * Build the prompt for the AI agent.
     *
     * @param  list<ExtractedIngredientData>  $ingredients
     */
    private function buildPrompt(array $ingredients, MealPlan $mealPlan): string
    {
        $ingredientList = '';
        foreach ($ingredients as $ingredient) {
            $ingredientList .= sprintf(
                "- %s: %s (Day %d, %s)\n",
                $ingredient->name,
                $ingredient->quantity,
                $ingredient->day,
                $ingredient->meal,
            );
        }

        return <<<PROMPT
            Please consolidate the following ingredients from a {$mealPlan->duration_days}-day meal plan into an organized grocery list.

            INGREDIENTS FROM MEAL PLAN:
            {$ingredientList}

            INSTRUCTIONS:
            1. Combine similar ingredients (e.g., if "eggs" appears multiple times, sum the quantities)
            2. Normalize ingredient names (e.g., "boneless skinless chicken breast" and "chicken breast" should be combined)
            3. Keep quantities practical (round to reasonable amounts)
            4. Categorize each item appropriately
            5. Track which days each ingredient is used in the "days" array (use the Day numbers from above)

            Return a JSON object with an "items" array containing consolidated grocery items.
            Each item must include a "days" array with the day numbers where it is used.
            PROMPT;
    }

    private function generateGroceryListJson(string $prompt): string
    {
        $response = $this->prompt($prompt);

        return (string) $response;
    }
}
