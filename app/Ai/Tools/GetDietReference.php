<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use App\Enums\DietType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::General)]
final readonly class GetDietReference implements Tool
{
    public function name(): string
    {
        return 'get_diet_reference';
    }

    public function description(): string
    {
        return 'Fetch diet-specific reference materials for meal planning. Choose a diet to read its guide, or supply the name of a supplementary reference such as the Mediterranean nutrient score card.';
    }

    public function handle(Request $request): string
    {
        $dietTypeValue = $request['diet_type'];
        $referenceName = $request['reference_name'] ?? null;

        $dietType = is_string($dietTypeValue) ? DietType::tryFrom($dietTypeValue) : null;
        if ($dietType === null) {
            return (string) json_encode([
                'success' => false,
                'error' => 'Invalid diet type. Valid options: '.implode(', ', array_column(DietType::cases(), 'value')),
            ]);
        }

        if ($referenceName !== null && (! is_string($referenceName) || ! preg_match('/\A[a-zA-Z0-9_-]+\z/', $referenceName))) {
            return (string) json_encode([
                'success' => false,
                'error' => 'Invalid reference name. Use only alphanumeric characters, hyphens, and underscores.',
            ]);
        }

        $directory = resource_path("markdown/{$dietType->value}");
        $filePath = $referenceName === null
            ? "{$directory}/{$dietType->value}.md"
            : "{$directory}/references/{$referenceName}.md";

        if (! File::exists($filePath)) {
            return (string) json_encode([
                'success' => false,
                'error' => $referenceName === null
                    ? "Guide not found for diet '{$dietType->value}'."
                    : "Reference '{$referenceName}' not found for diet '{$dietType->value}'. Omit reference_name to read the diet guide.",
            ]);
        }

        $content = File::get($filePath);

        return (string) json_encode([
            'success' => true,
            'diet_type' => $dietType->value,
            'reference_name' => $referenceName,
            'content' => $content,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'diet_type' => $schema->string()
                ->enum(DietType::class)
                ->description('The diet to look up.')
                ->required(),
            'reference_name' => $schema->string()
                ->nullable()
                ->description('Omit this for the diet guide. For a supplementary file, use its name without the extension, such as med-diet-nutrient-score-card for mediterranean.'),
        ];
    }
}
