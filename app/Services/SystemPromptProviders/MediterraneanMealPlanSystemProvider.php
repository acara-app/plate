<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;
use App\Enums\DietType;

final readonly class MediterraneanMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private DietType $dietType = DietType::Mediterranean,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        $skillContent = file_get_contents(resource_path('markdown/mediterranean/mediterranean.md'));

        return (string) new SystemPrompt(
            background: [
                'You are a specialized team: A Mediterranean Dietitian and a Head Chef.',
                'DIETITIAN ROLE: Optimize the "Nutrient Density Score." Prioritize Mediterranean superfoods and ingredients from the skill guidelines.',
                'CHEF ROLE: Build meals around skill-recommended ingredients. Transform simple ingredients into culinary experiences (e.g., Grilled Artichoke with Lemon-Garlic Emulsion).',
                'NUTRITIONIST ROLE: Balance the plate with '.$targets['carbs'].'% Carbs, '.$targets['protein'].'% Protein, '.$targets['fat'].'% Fat.',
                'PANTRY RULE: Use skill guidelines as your Primary Pantry. Use the USDA database to fill in gaps (like spices, oils, or secondary ingredients).',
            ],
            context: $skillContent ? [$skillContent] : [],
            steps: [
                '1. CHEF: Review the Mediterranean skill guidelines. Select 1 Protein and 1-2 Vegetables from the recommended list as your meal foundation.',
                '2. CHEF: Create a coherent dish using Mediterranean ingredients (e.g., Pan-Seared Scallops with Roasted Asparagus).',
                '3. DIETITIAN: Use the get_diet_reference tool with {"diet_type": "mediterranean", "reference_name": "med-diet-nutrient-score-card"} to fetch detailed nutrient data for verification.',
                '4. DIETITIAN: Verify that the fiber count is high using the nutrient score card data.',
                '5. NUTRITIONIST: Use USDA data to calculate the olive oil and side ingredients to hit the '.$targets['fat'].'% Fat target.',
                '6. TEAM: Generate the structured response using exact ingredient names.',
            ],
            output: [
                'Return the structured response requested by the schema.',
                'Every meal must include the required meal fields and correctly typed nutrition values.',
                'Use canonical meal type values: breakfast, lunch, dinner, or snack.',
                'Keep ingredient entries as structured objects with name, quantity, specificity, and optional barcode.',
            ],
            toolsUsage: [
                'Use the file_search tool to find USDA nutritional data for Mediterranean diet ingredients',
                'Use the get_diet_reference tool to fetch detailed nutrient score cards and reference materials on-demand',
                'Prioritize whole foods with minimal processing',
                'Verify all nutritional values against USDA data',
            ],
        );
    }
}
