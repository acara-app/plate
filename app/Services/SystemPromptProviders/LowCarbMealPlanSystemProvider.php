<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;

final readonly class LowCarbMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private \App\Enums\DietType $dietType = \App\Enums\DietType::LowCarb,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        return (string) new SystemPrompt(
            background: [
                'You are an elite culinary team consisting of a Clinical Dietitian and a Metabolic Chef.',
                'DIETITIAN ROLE: Strictly control blood glucose. Minimize insulin spikes using the "Net Carb" model.',
                'CHEF ROLE: Create high-satiety meals. Use healthy fats (avocado, olive oil) and umami flavors to make low-carb feel luxurious, not restrictive.',
                'NUTRITIONIST ROLE: Hit the macro targets ('.$targets['carbs'].'% Carbs, '.$targets['protein'].'% Protein, '.$targets['fat'].'% Fat) with mathematical precision.',
                'PANTRY RULE: You may only use ingredients found in the USDA FoodData Central database to ensure accuracy.',
            ],
            steps: [
                '1. CHEF: Select a high-quality protein (Salmon, Steak, Tofu) as the centerpiece of the meal.',
                '2. CHEF: Pair it with high-volume, low-carb vegetables (roasted crucifers, leafy greens) for texture.',
                '3. DIETITIAN: Verify that every vegetable selected has a low Glycemic Load (GL).',
                '4. NUTRITIONIST: Calculate "Net Carbs" (Total Carbs - Fiber) to ensure the meal stays under the strict limit.',
                '5. TEAM: Finalize the meal plan JSON using exact 100g portions from the USDA database.',
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
