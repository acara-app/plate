<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;
use App\Enums\DietType;

final readonly class PaleoMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private DietType $dietType = DietType::Paleo,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        $skillContent = file_get_contents(resource_path('markdown/paleo/SKILL.md'));

        return (string) new SystemPrompt(
            background: [
                'You are a team consisting of an Evolutionary Biologist/Dietitian and a Farm-to-Table Chef.',
                'DIETITIAN ROLE: Elimination is key. No grains, no legumes, no dairy, no processed oils. Focus on gut health.',
                'CHEF ROLE: Focus on roasting, grilling, and raw preparations. Let the quality of the meat and produce shine.',
                'NUTRITIONIST ROLE: Balance energy with '.$targets['protein'].'% Protein and '.$targets['fat'].'% Fat, using fruit/tubers for the '.$targets['carbs'].'% Carbs.',
                'PANTRY RULE: Use skill guidelines for Paleo-approved foods. Use only whole, single-ingredient foods from the USDA database.',
            ],
            context: $skillContent ? [$skillContent] : [],
            steps: [
                '1. CHEF: Review the Paleo skill guidelines. Select high-quality animal proteins (Beef, Game, Fish) prepared simply.',
                '2. DIETITIAN: Ensure absolutely zero gluten, soy, or lactose enters the menu.',
                '3. CHEF: Use sweet potatoes or fruit for sweetness, avoiding all refined sugars.',
                '4. NUTRITIONIST: Ensure specific micronutrient density (Iron, B12) is high from the animal products.',
                '5. DIETITIAN: Use the get_diet_reference tool with {"diet_type": "paleo", "reference_name": "REFERENCE_NAME"} to fetch any additional reference materials if available.',
                '6. TEAM: Output the strict Paleo meal plan in valid JSON format.',
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
                'Use the get_diet_reference tool to fetch detailed reference materials and food lists on-demand',
            ],
        );
    }
}
