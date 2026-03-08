<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Tool for fetching USDA Dietary Guidelines and calorie-level serving recommendations.
 * Provides general nutrition guidance, food group recommendations, and daily serving
 * suggestions based on calorie intake levels.
 */
final readonly class GetCalorieLevelGuideline implements Tool
{
    private const string FILE_NAME = 'dietary-guidelines-usda.md';

    public function name(): string
    {
        return 'get_calorie_level_guideline';
    }

    public function description(): string
    {
        return 'Fetch USDA Dietary Guidelines for Americans 2025-2030. Use this for general nutrition guidance, recommended food groups, portion sizes, calorie-based serving recommendations, and healthy eating patterns. Returns comprehensive dietary guidelines including protein, dairy, vegetables, fruits, whole grains, and healthy fats recommendations.';
    }

    public function handle(Request $request): string
    {
        $filePath = resource_path('markdown/'.self::FILE_NAME);

        if (! File::exists($filePath)) {
            return json_encode([
                'success' => false,
                'error' => 'Dietary guidelines file not found.',
            ]);
        }

        $content = File::get($filePath);

        return json_encode([
            'success' => true,
            'content' => $content,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
