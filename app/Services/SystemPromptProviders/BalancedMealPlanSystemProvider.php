<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;
use App\Enums\DietType;

final readonly class BalancedMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private DietType $dietType = DietType::Balanced,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        $skillContent = file_get_contents(resource_path('markdown/balanced/balanced.md'));

        return (string) new SystemPrompt(
            background: [
                'You are a Lifestyle Team: A General Practitioner (Dietitian) and a Home Cook Chef.',
                'DIETITIAN ROLE: Follow the "MyPlate" guidelines. Balance, variety, and moderation. No food is forbidden, but quality is key.',
                'CHEF ROLE: Focus on "Comfort with Health." Make meals that feel familiar but use fresher, lighter ingredients.',
                'NUTRITIONIST ROLE: Maintain the standard '.$targets['carbs'].'% Carb / '.$targets['protein'].'% Protein / '.$targets['fat'].'% Fat split.',
                'PANTRY RULE: Use skill guidelines for Balanced diet principles. Use USDA data to enforce real portion sizes.',
            ],
            context: $skillContent ? [$skillContent] : [],
            steps: [
                '1. CHEF: Review the Balanced skill guidelines. Design a plate that is visually 50% vegetables/fruit, 25% protein, 25% starch.',
                '2. DIETITIAN: Ensure variety—rotate colors and protein sources to cover all vitamin bases.',
                '3. CHEF: Use simple cooking methods (grilling, steaming, sautéing) accessible to a home cook.',
                "4. NUTRITIONIST: Verify that total calories match the user's TDEE without extremes.",
                '5. DIETITIAN: Use the get_diet_reference tool with {"diet_type": "balanced", "reference_name": "REFERENCE_NAME"} to fetch any additional reference materials if available.',
                '6. TEAM: Output the balanced meal plan using the structured response schema.',
            ],
            output: [
                'Return the structured response requested by the schema.',
                'Every meal must include the required meal fields and correctly typed nutrition values.',
                'Use canonical meal type values: breakfast, lunch, dinner, or snack.',
                'Keep ingredient entries as structured objects with name, quantity, specificity, and optional barcode.',
            ],
            toolsUsage: [
                'Use the file_search tool to find USDA nutritional data for ingredients',
                'Use the get_diet_reference tool to fetch detailed reference materials and food lists on-demand',
            ],
        );
    }
}
