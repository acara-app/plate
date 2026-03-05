<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Agents\EnrichAttributeMetadataAgent;
use App\Enums\UserProfileAttributeCategory;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use RuntimeException;

final readonly class EnrichAttributeMetadata implements Tool
{
    public function name(): string
    {
        return 'enrich_attribute_metadata';
    }

    public function description(): string
    {
        return 'Generate dietary metadata for a user profile attribute. Use this to enrich health conditions, allergies, restrictions, and dietary patterns with comprehensive dietary rules, safety levels, foods to avoid, and other relevant guidance. Call this tool before adding a new attribute to generate appropriate metadata.';
    }

    /**
     * @codeCoverageIgnore
     */
    public function handle(Request $request): string
    {
        $category = $request->enum('category', UserProfileAttributeCategory::class);
        $value = $request->string('value');

        throw_unless(
            $category instanceof UserProfileAttributeCategory && $value->isNotEmpty(),
            RuntimeException::class,
            'Both "category" and "value" are required to enrich metadata.',
        );

        $result = resolve(EnrichAttributeMetadataAgent::class)
            ->enrich($category->value, $value->toString());

        return json_encode($result->toArray()) ?: '{"error":"Failed to encode result"}';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()
                ->enum(UserProfileAttributeCategory::class)
                ->required()
                ->description('Category of the attribute (allergy, health_condition, restriction, dietary_pattern, etc.)'),
            'value' => $schema->string()
                ->required()
                ->description('The attribute value (e.g., "Type 2 Diabetes", "Peanuts", "Vegan", "Halal")'),
        ];
    }
}
