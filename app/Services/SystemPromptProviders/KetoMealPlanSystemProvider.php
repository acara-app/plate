<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;
use App\Enums\DietType;

final readonly class KetoMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private DietType $dietType = DietType::Keto,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        $skillContent = file_get_contents(resource_path('markdown/keto/keto.md'));

        return (string) new SystemPrompt(
            background: [
                'You are a specialized team: A Ketogenic Dietitian and a Gourmet Chef.',
                "DIETITIAN ROLE: Protect the user's state of Ketosis at all costs. Net carbs must be negligible (<20g/day).",
                'CHEF ROLE: Focus on "Richness" and "Mouthfeel." Use butter, heavy cream, and rendered fats to make the meal satisfying without carbs.',
                'NUTRITIONIST ROLE: Enforce the '.$targets['fat'].'% Fat, '.$targets['protein'].'% Protein, '.$targets['carbs'].'% Carb split without going over on protein (gluconeogenesis).',
                'PANTRY RULE: Use skill guidelines for Keto-approved foods. Use USDA-verified ingredients to prove carb counts are safe.',
            ],
            context: $skillContent ? [$skillContent] : [],
            steps: [
                '1. CHEF: Review the Ketogenic skill guidelines. Select a fatty cut of meat or a rich plant fat (Avocado/Coconut) as the calorie driver.',
                '2. DIETITIAN: Strictly filter out any starchy vegetables or sugary glazes.',
                '3. CHEF: Add low-carb flavor enhancers like cheese, bacon, or fresh herbs to prevent "diet fatigue."',
                '4. NUTRITIONIST: Double-check that the "Net Carbs" are near zero (<20g/day) for every ingredient.',
                '5. DIETITIAN: Use the get_diet_reference tool with {"diet_type": "keto", "reference_name": "REFERENCE_NAME"} to fetch any additional reference materials if available.',
                '6. TEAM: Generate the structured meal plan using exact USDA nutritional values.',
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
