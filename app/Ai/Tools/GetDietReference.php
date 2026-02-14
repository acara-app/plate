<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Enums\DietType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

/**
 * Tool for fetching diet-specific reference materials on-demand.
 * Allows AI to load detailed nutrient data, food lists, and other
 * reference files when needed for meal planning.
 */
final readonly class GetDietReference implements Tool
{
    public function description(): string
    {
        return 'Fetch diet-specific reference materials (nutrient score cards, food lists, detailed guidelines) on-demand. Use this when you need specific reference data for meal planning that is not in your immediate context.';
    }

    public function handle(Request $request): string
    {
        /** @var string $dietTypeValue */
        $dietTypeValue = $request['diet_type'] ?? '';
        /** @var string $referenceName */
        $referenceName = $request['reference_name'] ?? '';

        $dietType = DietType::tryFrom($dietTypeValue);
        if ($dietType === null) {
            return (string) json_encode([
                'success' => false,
                'error' => "Invalid diet type '{$dietTypeValue}'. Valid options: ".implode(', ', array_map(fn (DietType $t) => $t->value, DietType::cases())),
            ]);
        }

        // Validate reference name (prevent directory traversal)
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '', $referenceName);
        if ($sanitizedName !== $referenceName || ($sanitizedName === '' || $sanitizedName === '0')) {
            return (string) json_encode([
                'success' => false,
                'error' => 'Invalid reference name. Use only alphanumeric characters, hyphens, and underscores.',
            ]);
        }

        $filePath = resource_path("markdown/{$dietType->value}/references/{$sanitizedName}.md");

        if (! File::exists($filePath)) {
            return (string) json_encode([
                'success' => false,
                'error' => "Reference '{$sanitizedName}' not found for diet '{$dietType->value}'. Available references may vary by diet type.",
            ]);
        }

        $content = File::get($filePath);

        return (string) json_encode([
            'success' => true,
            'diet_type' => $dietType->value,
            'reference_name' => $sanitizedName,
            'content' => $content,
        ]);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'diet_type' => $schema->string()
                ->description('The diet type (e.g., mediterranean, keto, dash, low_carb, vegetarian, vegan, paleo, balanced)')
                ->required(),
            'reference_name' => $schema->string()
                ->description('Name of the reference file without extension (e.g., med-diet-nutrient-score-card, keto-food-list)')
                ->required(),
        ];
    }
}
