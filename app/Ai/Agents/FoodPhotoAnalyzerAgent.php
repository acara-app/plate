<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\BaseAgent;
use App\Ai\SystemPrompt;
use App\DataObjects\FoodAnalysisData;
use App\DataObjects\FoodItemData;
use App\Enums\ModelName;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Prism\Prism\Structured\PendingRequest;
use Prism\Prism\ValueObjects\Media\Image;
use Spatie\LaravelData\DataCollection;

/**
 * @phpstan-type FoodItem array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}
 * @phpstan-type FoodAnalysisResponse array{items: array<int, FoodItem>, total_calories: float, total_protein: float, total_carbs: float, total_fat: float, confidence: float}
 */
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
                'Return the analysis using the provided structured format.',
                'Each item should have name (food name), calories (kcal), protein (g), carbs (g), fat (g), portion (estimated size)',
                'confidence is a percentage (0-100) indicating how confident you are in the analysis',
                'All nutritional values should be rounded to 1 decimal place',
                'If no food is detected in the image, return empty items array with zeros for totals and confidence of 0',
            ],
        );
    }

    public function maxTokens(): int
    {
        return 8000;
    }

    /**
     * @return array<string, int>
     */
    public function clientOptions(): array
    {
        return [
            'timeout' => 60,
        ];
    }

    public function structured(): PendingRequest
    {
        return Prism::structured()
            ->using($this->provider(), $this->modelName())
            ->withSystemPrompt($this->systemPrompt())
            ->withSchema($this->getOutputSchema())
            ->withMaxTokens($this->maxTokens());
    }

    public function analyze(string $imageBase64, string $mimeType): FoodAnalysisData
    {
        $image = Image::fromBase64($imageBase64, $mimeType);

        $response = $this->structured()
            ->withPrompt('Analyze this food photo and provide nutritional breakdown for all food items visible.', [$image])
            ->withClientOptions($this->clientOptions())
            ->asStructured();

        $data = $response->structured;

        // Validate response data is present and has required keys
        $requiredKeys = ['items', 'total_calories', 'total_protein', 'total_carbs', 'total_fat', 'confidence'];
        if ($data === null || array_diff($requiredKeys, array_keys($data)) !== []) {
            Log::error('Food analysis returned invalid structured data', [
                'original_response' => $response->text ?? 'No text response',
                'structured_data' => $data,
            ]);
            throw new InvalidArgumentException('AI returned invalid analysis structure');
        }

        /** @var FoodAnalysisResponse $data */
        return $this->mapToFoodAnalysisData($data);
    }

    private function getOutputSchema(): ObjectSchema
    {
        $foodItemSchema = new ObjectSchema(
            name: 'food_item',
            description: 'A single food item with nutritional information',
            properties: [
                new StringSchema('name', 'Name of the food item (e.g., "Grilled Chicken Breast")'),
                new NumberSchema('calories', 'Calories in kcal'),
                new NumberSchema('protein', 'Protein content in grams'),
                new NumberSchema('carbs', 'Carbohydrates content in grams'),
                new NumberSchema('fat', 'Fat content in grams'),
                new StringSchema('portion', 'Estimated portion size (e.g., "100g", "1 medium apple")'),
            ],
            requiredFields: ['name', 'calories', 'protein', 'carbs', 'fat', 'portion'],
        );

        return new ObjectSchema(
            name: 'food_analysis',
            description: 'Nutritional analysis of a food photo containing all identified food items and totals',
            properties: [
                new ArraySchema('items', 'List of all food items identified in the image', $foodItemSchema),
                new NumberSchema('total_calories', 'Total calories for the entire meal'),
                new NumberSchema('total_protein', 'Total protein in grams'),
                new NumberSchema('total_carbs', 'Total carbohydrates in grams'),
                new NumberSchema('total_fat', 'Total fat in grams'),
                new NumberSchema('confidence', 'Confidence score (0-100) indicating how confident the analysis is'),
            ],
            requiredFields: ['items', 'total_calories', 'total_protein', 'total_carbs', 'total_fat', 'confidence'],
        );
    }

    /**
     * Map the structured response to FoodAnalysisData DTO.
     *
     * @param  FoodAnalysisResponse  $data
     */
    private function mapToFoodAnalysisData(array $data): FoodAnalysisData
    {
        $items = $data['items'];

        $foodItems = collect($items)->map(
            fn (array $item): FoodItemData => new FoodItemData(
                name: $item['name'],
                calories: $item['calories'],
                protein: $item['protein'],
                carbs: $item['carbs'],
                fat: $item['fat'],
                portion: $item['portion'],
            )
        );

        return new FoodAnalysisData(
            items: new DataCollection(FoodItemData::class, $foodItems->toArray()),
            totalCalories: $data['total_calories'],
            totalProtein: $data['total_protein'],
            totalCarbs: $data['total_carbs'],
            totalFat: $data['total_fat'],
            confidence: (int) $data['confidence'],
        );
    }
}
