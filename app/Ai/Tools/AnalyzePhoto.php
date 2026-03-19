<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;

final readonly class AnalyzePhoto implements Tool
{
    /**
     * @param  array<int, Base64Image>  $images
     */
    public function __construct(private array $images) {}

    public function name(): string
    {
        return 'analyze_photo';
    }

    public function description(): string
    {
        return 'Analyze a food photo shared by the user. Performs detailed nutritional analysis including calorie count, macronutrients (protein, carbs, fat), and portion size estimation for each food item. Use this tool when the user shares a photo of food or a meal. After receiving the results, present BOTH the per-ingredient breakdown (showing calories and carbs for each item) AND the meal totals. This per-item detail helps users understand where their carbs and calories come from. Present for confirmation before logging with log_health_entry.';
    }

    public function handle(Request $request): string
    {
        if ($this->images === []) {
            return (string) json_encode([
                'error' => 'No image was provided. Please share a photo first.',
            ]);
        }

        $image = $this->images[0];

        $analysis = resolve(FoodPhotoAnalyzerAgent::class)
            ->analyze($image->base64, $image->mime ?? 'image/jpeg');

        return (string) json_encode($analysis->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional context about what the user wants to know about the photo (e.g., "How many calories?", "Is this healthy?"). Defaults to general food analysis.')
                ->required(),
        ];
    }
}
