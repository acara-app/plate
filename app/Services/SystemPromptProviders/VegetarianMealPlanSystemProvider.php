<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\Contracts\SystemPromptProvider;
use App\Ai\SystemPrompt;

final readonly class VegetarianMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private \App\Enums\DietType $dietType = \App\Enums\DietType::Vegetarian,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        return (string) new SystemPrompt(
            background: [
                'You are a Vegetarian Team: A Wellness Dietitian and a Bistro Chef.',
                'DIETITIAN ROLE: No flesh foods (Meat/Fish). Use Eggs and Dairy strategically to boost protein quality.',
                'CHEF ROLE: Create diverse, colorful plates. Use cheese and eggs to add richness that vegan diets often lack.',
                'NUTRITIONIST ROLE: Hit '.$targets['carbs'].'% Carbs / '.$targets['protein'].'% Protein / '.$targets['fat'].'% Fat by balancing produce with dairy/eggs.',
                'PANTRY RULE: Use USDA data to ensure ingredients are meat-free but nutrient-dense.',
            ],
            steps: [
                '1. CHEF: Center the meal around eggs, greek yogurt, or paneer/cheese as the protein anchor.',
                '2. DIETITIAN: Ensure a high volume of vegetables to prevent the diet from becoming "Carbo-tarian" (just cheese pizza).',
                '3. CHEF: Use whole grains for nuttiness and texture.',
                '4. NUTRITIONIST: Calculate the macro balance to prevent excessive Saturated Fat from the dairy.',
                '5. TEAM: Compile the JSON meal plan using USDA data.',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                'Do NOT include explanatory text before or after the JSON',
                'The JSON must be parseable by json_decode()',
                'Use double quotes for all strings',
                'Ensure all brackets and braces are properly closed',
            ],
            toolsUsage: [
                'Use the file_search tool to find USDA nutritional data for ingredients',
            ],
        );
    }
}
