<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\SystemPrompt;
use App\Data\FoodAnalysisData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Promptable;

#[Provider('gemini')]
#[MaxTokens(35000)]
#[Timeout(120)]
final class FoodPhotoAnalyzerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist and food recognition specialist.',
                'Your task is to analyze food photos and identify ALL distinct food items with accurate per-item nutritional data.',
                'You have extensive knowledge of food portions, calories, and macronutrients (protein, carbs, fat).',
                'You can accurately estimate portion sizes from visual inspection.',
                'Accuracy per ingredient matters more than speed — users depend on per-item carb counts for diabetes management and carb counting.',
            ],
            steps: [
                '1. Carefully identify ALL distinct food items visible in the image — do not merge separate ingredients into one entry',
                '2. Estimate the portion size for each item (e.g., "1 medium apple", "150g rice", "50g feta cheese")',
                '3. Calculate calories and macros (protein, carbs, fat) for EACH identified food item individually — these per-item values are critical',
                '4. Sum up total calories and macros for the entire meal (must equal the sum of individual items)',
                '5. Provide a confidence score based on image clarity and food recognizability',
            ],
            output: [
                'Return the analysis using the provided structured format.',
                'Each item MUST have accurate per-item values: name (food name), calories (kcal), protein (g), carbs (g), fat (g), portion (estimated size)',
                'Do NOT put all macros in the totals only — each food item must carry its own calorie and macro breakdown',
                'confidence is a percentage (0-100) indicating how confident you are in the analysis',
                'All nutritional values should be rounded to 1 decimal place',
                'If no food is detected in the image, return empty items array with zeros for totals and confidence of 0',
            ],
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $foodItemSchema = new ObjectType([
            'name' => $schema->string(),
            'calories' => $schema->number(),
            'protein' => $schema->number(),
            'carbs' => $schema->number(),
            'fat' => $schema->number(),
            'portion' => $schema->string(),
        ]);

        return [
            'items' => $schema->array()->items($foodItemSchema)->required(),
            'total_calories' => $schema->number()->required(),
            'total_protein' => $schema->number()->required(),
            'total_carbs' => $schema->number()->required(),
            'total_fat' => $schema->number()->required(),
            'confidence' => $schema->number()->required(),
        ];
    }

    public function analyze(string $imageBase64, string $mimeType): FoodAnalysisData
    {
        $response = $this->prompt(
            'Analyze this food photo and provide nutritional breakdown for all food items visible.',
            attachments: [
                new Base64Image($imageBase64, $mimeType),
            ]
        );

        /** @var array<string, mixed> $data */
        $data = json_decode((string) json_encode($response), true);

        return FoodAnalysisData::from($data);
    }
}
