<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Data\ExtractedIngredientData;
use App\Data\GroceryItemData;
use App\Data\GroceryListData;
use App\Data\IngredientData;
use App\Models\MealPlan;
use App\Utilities\LanguageUtil;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Spatie\LaravelData\DataCollection;

#[Provider('gemini')]
#[MaxTokens(67000)]
#[Timeout(120)]
final class GroceryListGeneratorAgent implements Agent, HasStructuredOutput
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
                'Return the grocery list using the provided structured format.',
                'The "days" array must contain the day numbers (1-based) where the ingredient is used',
                'Valid categories are: Produce, Dairy, Meat & Seafood, Pantry, Frozen, Bakery, Beverages, Condiments & Sauces, Herbs & Spices, Other',
                'The "category" VALUE must be one of the English category names listed above — do not translate it',
                'The "name" and "quantity" VALUES must follow the language directive provided in the user prompt',
                'Structured field names ("name", "quantity", "category", "days") always stay in English',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $itemSchema = new ObjectType([
            'name' => $schema->string()->required(),
            'quantity' => $schema->string()->required(),
            'category' => $schema->string()->enum([
                'Produce',
                'Dairy',
                'Meat & Seafood',
                'Pantry',
                'Frozen',
                'Bakery',
                'Beverages',
                'Condiments & Sauces',
                'Herbs & Spices',
                'Other',
            ])->required(),
            'days' => (new ArrayType)->items($schema->integer())->required(),
        ])->withoutAdditionalProperties();

        return [
            'items' => (new ArrayType)->items($itemSchema)->required(),
        ];
    }

    public function generate(MealPlan $mealPlan): GroceryListData
    {
        $ingredients = $this->extractIngredients($mealPlan);

        if ($ingredients === []) {
            return new GroceryListData(
                items: new DataCollection(GroceryItemData::class, []),
            );
        }

        $prompt = $this->buildPrompt($ingredients, $mealPlan);
        $data = $this->generateStructuredGroceryList($prompt);

        /** @var array{items: array<int, array{name: string, quantity: string, category: string, days: array<int>}>} $data */
        return GroceryListData::from($data);
    }

    /**
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

        $mealPlan->loadMissing('user');
        ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($mealPlan->user->locale);

        return <<<PROMPT
            Please consolidate the following ingredients from a {$mealPlan->duration_days}-day meal plan into an organized grocery list.

            LANGUAGE:
            Write each item's "name" and "quantity" in {$language} (language code: `{$languageCode}`).
            The "category" value MUST stay in English and match one of the canonical categories (Produce, Dairy, Meat & Seafood, Pantry, Frozen, Bakery, Beverages, Condiments & Sauces, Herbs & Spices, Other).
            Structured field names ("name", "quantity", "category", "days") always stay in English.
            Do NOT mix languages within an item's name or quantity.

            INGREDIENTS FROM MEAL PLAN:
            {$ingredientList}

            INSTRUCTIONS:
            1. Combine similar ingredients (e.g., if "eggs" appears multiple times, sum the quantities)
            2. Normalize ingredient names (e.g., "boneless skinless chicken breast" and "chicken breast" should be combined)
            3. Keep quantities practical (round to reasonable amounts)
            4. Categorize each item appropriately (English category name)
            5. Track which days each ingredient is used in the "days" array (use the Day numbers from above)

            Return an "items" array containing consolidated grocery items.
            Each item must include a "days" array with the day numbers where it is used.
            PROMPT;
    }

    /**
     * @return array{items: array<int, array{name: string, quantity: string, category: string, days: array<int>}>}
     */
    private function generateStructuredGroceryList(string $prompt): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt($prompt);

        /** @var array{items: array<int, array{name: string, quantity: string, category: string, days: array<int>}>} */
        return $response->toArray();
    }
}
