<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::General)]
final readonly class GetDailyServingsByCalorie implements Tool
{
    private const string FILE_NAME = 'daily-servings-by-calorie-level-usda.md';

    public function name(): string
    {
        return 'get_daily_servings_by_calorie';
    }

    public function description(): string
    {
        return "Fetch USDA daily serving recommendations by calorie level (1000-3200). Returns the complete table with serving sizes for protein, dairy, vegetables, fruits, whole grains, and healthy fats. Use this to determine appropriate serving sizes based on a user's caloric needs.";
    }

    public function handle(Request $request): string
    {
        $filePath = resource_path('markdown/'.self::FILE_NAME);

        // @codeCoverageIgnoreStart
        if (! File::exists($filePath)) {
            return (string) json_encode([
                'success' => false,
                'error' => 'Daily servings file not found.',
            ]);
        }

        // @codeCoverageIgnoreEnd

        $content = File::get($filePath);

        return (string) json_encode([
            'success' => true,
            'content' => $content,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'context' => $schema->string()->required()->nullable()
                ->description('Optional context for why the servings data is needed (e.g., "building meal plan", "checking portions").'),
        ];
    }
}
