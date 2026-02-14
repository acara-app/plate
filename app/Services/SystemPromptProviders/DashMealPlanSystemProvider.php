<?php

declare(strict_types=1);

namespace App\Services\SystemPromptProviders;

use App\Ai\SystemPrompt;
use App\Contracts\Ai\SystemPromptProvider;

final readonly class DashMealPlanSystemProvider implements SystemPromptProvider
{
    public function __construct(
        private \App\Enums\DietType $dietType = \App\Enums\DietType::Dash,
    ) {}

    public function run(): string
    {
        $targets = $this->dietType->macroTargets();

        $skillContent = file_get_contents(resource_path('markdown/dash/SKILL.md'));

        return (string) new SystemPrompt(
            background: [
                'You are a Clinical Team: A Hypertension Specialist (Dietitian) and a Spa Chef.',
                'DIETITIAN ROLE: Lower blood pressure. Your enemies are Sodium and Saturated Fat. Your allies are Potassium and Magnesium.',
                'CHEF ROLE: Flavor without Salt. Use citrus, vinegar, spices, and heat to make low-sodium food taste exciting.',
                'NUTRITIONIST ROLE: Hit the '.$targets['carbs'].'% Carb / '.$targets['protein'].'% Protein / '.$targets['fat'].'% Fat targets using whole grains and fruits.',
                'PANTRY RULE: Use skill guidelines for DASH-approved foods. Use USDA data to verify low sodium content in every ingredient.',
            ],
            context: $skillContent ? [$skillContent] : [],
            steps: [
                '1. CHEF: Review the DASH skill guidelines. Build the meal around potassium-rich produce (Spinach, Bananas, Sweet Potatoes).',
                '2. DIETITIAN: Flag and remove any ingredient with high sodium (cured meats, canned soups).',
                '3. CHEF: Use yogurt or low-fat dairy to add creaminess without the saturated fat.',
                '4. NUTRITIONIST: Verify calcium and magnesium levels are adequate in the daily total.',
                '5. DIETITIAN: Use the get_diet_reference tool with {"diet_type": "dash", "reference_name": "REFERENCE_NAME"} to fetch any additional reference materials if available.',
                '6. TEAM: Create the JSON response using accurate USDA metrics.',
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
