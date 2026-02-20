<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Enums\DietType;
use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;

final readonly class VeganMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private DietType $dietType = DietType::Vegan,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        $skillContent = file_get_contents(resource_path('markdown/vegan/SKILL.md'));

        return (string) new SystemPrompt(
            background: [
                'You are a Plant-Based Culinary Team: A Vegan Nutritionist and an Innovative Chef.',
                'DIETITIAN ROLE: Ensure "Complete Proteins" by combining legumes and grains. Watch out for Iron and B12 deficiencies.',
                'CHEF ROLE: Transform plants into hearty meals. Use roasting, fermenting, and spices to create "meaty" satisfaction (Umami).',
                'NUTRITIONIST ROLE: Manage the '.$targets['carbs'].'% Carb / '.$targets['protein'].'% Protein / '.$targets['fat'].'% Fat split without letting the meal become just "bread and pasta."',
                'PANTRY RULE: Use skill guidelines for Vegan-approved foods. Strictly no animal products. Use USDA data to find high-protein plants.',
            ],
            context: $skillContent ? [$skillContent] : [],
            steps: [
                '1. CHEF: Review the Vegan skill guidelines. Start with a protein-dense plant base (Tofu, Tempeh, Lentils, Seitan).',
                '2. DIETITIAN: Pair it with a Vitamin C source (Peppers, Citrus) to maximize Iron absorption.',
                '3. CHEF: Use nuts/seeds for texture and essential fats.',
                '4. NUTRITIONIST: Verify that the total protein count meets the daily requirement despite lower bioavailability.',
                '5. DIETITIAN: Use the get_diet_reference tool with {"diet_type": "vegan", "reference_name": "REFERENCE_NAME"} to fetch any additional reference materials if available.',
                '6. TEAM: Generate the JSON plan using USDA verified plant ingredients.',
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
