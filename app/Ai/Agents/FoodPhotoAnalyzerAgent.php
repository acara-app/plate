<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\BaseAgent;
use App\Ai\SystemPrompt;
use App\DataObjects\FoodAnalysisData;
use App\Enums\ModelName;
use App\Utilities\JsonCleaner;
use Prism\Prism\ValueObjects\Media\Image;

final class FoodPhotoAnalyzerAgent extends BaseAgent
{
    public function modelName(): ModelName
    {
        return ModelName::GEMINI_3_FLASH;
    }

    public function systemPrompt(): string
    {
        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist and food recognition specialist.',
                'Your task is to analyze food photos and identify all food items with their nutritional information.',
                'You have extensive knowledge of food portions, calories, and macronutrients (protein, carbs, fat).',
                'You can accurately estimate portion sizes from visual inspection.',
            ],
            steps: [
                '1. Identify all distinct food items visible in the image',
                '2. Estimate the portion size for each item (e.g., "1 medium apple", "150g rice")',
                '3. Calculate calories and macros for each identified food item',
                '4. Sum up total calories and macros for the entire meal',
                '5. Provide a confidence score based on image clarity and food recognizability',
            ],
            output: [
                'Your response MUST be valid JSON and ONLY JSON',
                'Start your response with { and end with }',
                'Do NOT include markdown code blocks (no ```json)',
                'Do NOT include explanatory text before or after the JSON',
                'Return format: {"items": [{"name": "string", "calories": number, "protein": number, "carbs": number, "fat": number, "portion": "string"}], "total_calories": number, "total_protein": number, "total_carbs": number, "total_fat": number, "confidence": number}',
                'Each item should have name (food name), calories (kcal), protein (g), carbs (g), fat (g), portion (estimated size)',
                'confidence is a percentage (0-100) indicating how confident you are in the analysis',
                'All nutritional values should be rounded to 1 decimal place',
                'If no food is detected in the image, return empty items array with zeros for totals and confidence of 0',
            ],
        );
    }

    public function maxTokens(): int
    {
        return 2000;
    }

    /**
     * @return array<string, mixed>
     */
    public function clientOptions(): array
    {
        return [
            'timeout' => 90,
        ];
    }

    public function analyze(string $imageBase64, string $mimeType): FoodAnalysisData
    {
        $image = Image::fromBase64($imageBase64, $mimeType);

        $response = $this->text()
            ->withPrompt('Analyze this food photo and provide nutritional breakdown for all food items visible.', [$image])
            ->asText();

        $jsonText = $response->text;
        $cleanedJsonText = JsonCleaner::extractAndValidateJson($jsonText);

        /** @var array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}>, total_calories: float, total_protein: float, total_carbs: float, total_fat: float, confidence: int} $data */
        $data = json_decode($cleanedJsonText, true, 512, JSON_THROW_ON_ERROR);

        return FoodAnalysisData::from($data);
    }
}
