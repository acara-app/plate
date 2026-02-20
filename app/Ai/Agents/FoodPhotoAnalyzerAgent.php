<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\JsonSchema\Types\Type;
use App\Ai\SystemPrompt;
use App\DataObjects\FoodAnalysisData;
use App\DataObjects\FoodItemData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Promptable;
use Spatie\LaravelData\DataCollection;

#[Provider('gemini')]
#[MaxTokens(8000)]
#[Timeout(60)]
final class FoodPhotoAnalyzerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
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

        $json = json_encode($response);
        // @phpstan-ignore argument.type
        $data = json_decode($json, true);

        // Validate response data is present and has required keys
        $requiredKeys = ['items', 'total_calories', 'total_protein', 'total_carbs', 'total_fat', 'confidence'];
        if (! is_array($data) || array_diff($requiredKeys, array_keys($data)) !== []) {
            Log::error('Food analysis returned invalid structured data', [
                'response' => $response,
                'data' => $data,
            ]);
            throw new InvalidArgumentException('AI returned invalid analysis structure');
        }

        /** @var array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}>, total_calories: float, total_protein: float, total_carbs: float, total_fat: float, confidence: float} $data */
        return $this->mapToFoodAnalysisData($data);
    }

    /**
     * Map the structured response to FoodAnalysisData DTO.
     *
     * @param  array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}>, total_calories: float, total_protein: float, total_carbs: float, total_fat: float, confidence: float}  $data
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
