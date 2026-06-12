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
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

#[Provider('gemini')]
#[MaxTokens(35000)]
#[Timeout(120)]
final class FoodPhotoAnalyzerAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const string PROMPT_VERSION = '3';

    private ?string $language = null;

    private ?string $languageCode = null;

    public static function pinnedModel(): string
    {
        $model = config('plate.food_photo_analyzer.model');

        throw_if(! is_string($model) || $model === '', RuntimeException::class, 'No model is pinned for the food photo analyzer. Set plate.food_photo_analyzer.model.');

        return $model;
    }

    public static function version(): string
    {
        return sprintf('%s/p%s', self::pinnedModel(), self::PROMPT_VERSION);
    }

    public function withLanguage(string $language, string $languageCode): self
    {
        $this->language = $language;
        $this->languageCode = $languageCode;

        return $this;
    }

    public function instructions(): string
    {
        $output = [
            'Return the analysis using the provided structured format.',
            'Each item MUST have accurate per-item values: name (food name), calories (kcal), protein (g), carbs (g), fat (g), portion (estimated size), grams (numeric total weight), match_name (canonical English food name)',
            'Do NOT put all macros in the totals only — each food item must carry its own calorie and macro breakdown',
            'grams is the item\'s estimated total weight as a number, e.g. 150 for 150g of rice — provide it for every item',
            'match_name is the food\'s canonical English name suitable for matching a nutrition reference database, e.g. "rice, white, cooked" or "chicken breast, grilled" — always English, even when name is in another language',
            'confidence is a percentage (0-100) indicating how confident you are in the analysis',
            'All nutritional values should be rounded to 1 decimal place',
            'If no food is detected in the image, return empty items array with zeros for totals and confidence of 0',
        ];

        if ($this->language !== null && $this->languageCode !== null) {
            $output[] = sprintf(
                'Return all `name` and `portion` values in %s (language code: `%s`). Numeric fields and structured field names stay as-is. Use natural, idiomatic terms in %s — do not transliterate from English.',
                $this->language,
                $this->languageCode,
                $this->language,
            );
        }

        return (string) new SystemPrompt(
            background: [
                'You are an expert nutritionist and food recognition specialist.',
                'Your task is to analyze food photos and identify ALL distinct food items with accurate per-item nutritional data.',
                'You have extensive knowledge of food portions, calories, and macronutrients (protein, carbs, fat).',
                'You can accurately estimate portion sizes from visual inspection.',
                'Accuracy per ingredient matters more than speed — users track per-item carbohydrate values closely and review every estimate before saving.',
            ],
            steps: [
                '1. Carefully identify ALL distinct food items visible in the image — do not merge separate ingredients into one entry',
                '2. Estimate each item\'s portion as readable text (e.g., "1 medium apple", "150g rice", "50g feta cheese") AND as a numeric weight in the grams field',
                '3. Give each item a canonical English match_name for nutrition-database lookup, even when name is localized to another language',
                '4. Calculate calories and macros (protein, carbs, fat) for EACH identified food item individually — these per-item values are critical',
                '5. Sum up total calories and macros for the entire meal (must equal the sum of individual items)',
                '6. Provide a confidence score based on image clarity and food recognizability',
            ],
            output: $output,
        );
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $foodItemSchema = new ObjectType([
            'name' => $schema->string()->required(),
            'calories' => $schema->number()->required(),
            'protein' => $schema->number()->required(),
            'carbs' => $schema->number()->required(),
            'fat' => $schema->number()->required(),
            'portion' => $schema->string()->required(),
            'grams' => $schema->number()->required(),
            'match_name' => $schema->string()->required(),
        ])->withoutAdditionalProperties();

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
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt(
            'Analyze this food photo and provide nutritional breakdown for all food items visible.',
            attachments: [
                new Base64Image($imageBase64, $mimeType),
            ],
            model: self::pinnedModel(),
        );

        /** @var array<string, mixed> $data */
        $data = $response->toArray();
        $data['analyzer_version'] = self::version();

        return FoodAnalysisData::from($data);
    }
}
