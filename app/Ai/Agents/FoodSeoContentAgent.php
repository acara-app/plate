<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\BaseAgent;
use App\Ai\SystemPrompt;
use App\Enums\FoodCategory;
use App\Enums\ModelName;
use App\Enums\SettingKey;
use App\Models\Setting;
use App\Utilities\JsonCleaner;
use Prism\Prism\ValueObjects\ProviderTool;

final class FoodSeoContentAgent extends BaseAgent
{
    public function systemPrompt(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are a Medical Nutrition Therapist with dual training in medicine and clinical nutrition.',
                'You specialize in diabetes management and understanding how foods affect blood glucose levels.',
                'Your recommendations are always backed by peer-reviewed research and USDA nutritional data.',
                'You understand glycemic index (GI), glycemic load (GL), and carbohydrate-insulin interactions.',
                'You have access to the USDA FoodData Central database for accurate nutritional information.',
                'Glycemic Load (GL) = (GI × carbs per serving) / 100. Low GL: 0-10, Medium GL: 11-19, High GL: 20+.',
            ],
            steps: [
                '1. Use file_search to retrieve exact USDA nutritional data for the food (calories, protein, carbs, fat, fiber, sugar per 100g)',
                '2. Analyze the carbohydrate profile and estimate glycemic impact based on sugar-to-fiber ratio and total carbs',
                '3. Extract a clean, user-friendly display name from the USDA description (e.g., "Banana, raw" → "Banana")',
                '4. Generate an engaging, SEO-optimized H1 title using question format for diabetes-related search queries',
                '5. Write a concise diabetic safety insight explaining blood sugar impact with specific nutritional numbers',
                '6. Create meta title and description optimized for click-through rate and search visibility',
                '7. Assess overall glycemic impact as "low", "medium", or "high" based on nutritional analysis',
                '8. Estimate Glycemic Load (GL) as "low" (0-10), "medium" (11-19), or "high" (20+) based on carb content and estimated GI',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                'Do NOT include explanatory text before or after the JSON',
                'The JSON must be parseable by json_decode()',
                'Use double quotes for all strings',
                'Return JSON with these exact keys:',
                '  - display_name: Clean food name for display (e.g., "Banana" not "Banana, raw")',
                '  - h1_title: SEO H1 using question format like "Is Banana Good for Diabetics?" or "Can Diabetics Eat Banana?"',
                '  - meta_title: Format as "{Food} Glycemic Index & Diabetes Safety | Acara Plate"',
                '  - meta_description: 150-160 character description with keywords and call-to-action',
                '  - diabetic_insight: 2-3 sentences explaining glucose impact with actual carb/sugar/fiber values from USDA',
                '  - glycemic_assessment: One of "low", "medium", or "high"',
                '  - glycemic_load: One of "low", "medium", or "high" (estimated GL category for typical serving)',
                '  - category: One of "'.implode('", "', array_map(fn (FoodCategory $c) => $c->value, FoodCategory::cases())).'"',
                '  - nutrition: Object with calories, protein, carbs, fat, fiber, sugar (per 100g from USDA)',
            ],
            toolsUsage: [
                'Use file_search tool to find accurate USDA nutritional data - do NOT estimate or guess values',
                'If exact food is not found, search for the closest match in the database',
                'Always base nutritional values on USDA data retrieved via file_search',
            ],
        );
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
            'timeout' => 120,
        ];
    }

    /**
     * @return array<int, ProviderTool>
     */
    public function providerTools(): array
    {
        $storeNames = $this->getFileSearchStoreNames();

        if ($storeNames === []) {
            return [];
        }

        return [
            new ProviderTool(
                type: 'file_search',
                name: 'file_search',
                options: [
                    'file_search_store_names' => $storeNames,
                ]
            ),
        ];
    }

    /**
     * Generate SEO content for a food item.
     *
     * @return array{
     *     display_name: string,
     *     h1_title: string,
     *     meta_title: string,
     *     meta_description: string,
     *     diabetic_insight: string,
     *     glycemic_assessment: string,
     *     glycemic_load: string,
     *     category: string,
     *     nutrition: array{calories: float, protein: float, carbs: float, fat: float, fiber: float, sugar: float}
     * }
     */
    public function generate(string $foodName): array
    {
        $prompt = $this->buildPrompt($foodName);

        $response = $this->text()
            ->withPrompt($prompt)
            ->withProviderTools($this->providerTools())
            ->asText();

        \Illuminate\Support\Facades\Log::debug('FoodSeoContentAgent raw response', [
            'food_name' => $foodName,
            'response_length' => strlen($response->text),
            'response_preview' => substr($response->text, 0, 500),
        ]);

        $cleanedJson = JsonCleaner::extractAndValidateJson($response->text);

        \Illuminate\Support\Facades\Log::debug('FoodSeoContentAgent cleaned JSON', [
            'cleaned_json_preview' => substr($cleanedJson, 0, 500),
        ]);

        /** @var array{display_name: string, h1_title: string, meta_title: string, meta_description: string, diabetic_insight: string, glycemic_assessment: string, glycemic_load: string, category: string, nutrition: array{calories: int|float, protein: int|float, carbs: int|float, fat: int|float, fiber: int|float, sugar: int|float}} $decoded */
        $decoded = json_decode($cleanedJson, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    private function buildPrompt(string $foodName): string
    {
        $categoryOptions = implode(', ', array_map(fn (FoodCategory $c): string => $c->value.' ('.$c->label().')', FoodCategory::cases()));

        return <<<PROMPT
Generate SEO-optimized content for a diabetes-focused food information page about: "{$foodName}"

Use the file_search tool to find accurate USDA nutritional data for this food.

Requirements:
1. Search for "{$foodName}" in the USDA database
2. Extract nutrition values per 100g (calories, protein, carbs, fat, fiber, sugar)
3. Generate an engaging H1 title using question format (e.g., "Is {$foodName} Good for Diabetics?")
4. Write a diabetic insight that includes actual numbers from USDA data
5. Assess glycemic impact based on ESTIMATED Glycemic Index (GI):
   - "low" = GI < 55 (most vegetables, legumes, whole grains like brown rice, nuts)
   - "medium" = GI 56-69 (some fruits, whole wheat products)
   - "high" = GI 70+ (white bread, white rice, sugary foods, potatoes)
   IMPORTANT: Brown rice is LOW-MEDIUM (~50-55 GI), NOT high. White rice is HIGH (~72 GI).
6. Estimate glycemic load (GL) for a typical serving: "low" (GL 0-10), "medium" (GL 11-19), or "high" (GL 20+)
7. Categorize the food into one of these categories: {$categoryOptions}
   - fruits: fresh fruits, dried fruits
   - vegetables: fresh vegetables, leafy greens
   - grains_starches: rice, bread, pasta, cereals, potatoes
   - dairy_alternatives: milk, cheese, yogurt, plant-based alternatives
   - proteins_legumes: meat, fish, eggs, beans, lentils, tofu
   - nuts_seeds: nuts, seeds, nut butters
   - beverages: juices, smoothies, drinks
   - condiments_sauces: sauces, dressings, spreads
   - snacks_sweets: processed snacks, sweets, desserts
   - other: anything that doesn't fit above

Return ONLY valid JSON with the required fields.
PROMPT;
    }

    /**
     * @return array<int, string>
     */
    private function getFileSearchStoreNames(): array
    {
        $storeName = Setting::get(SettingKey::GeminiFileSearchStoreName);

        if (! $storeName || ! is_string($storeName)) {
            return [];
        }

        return [$storeName];
    }
}
