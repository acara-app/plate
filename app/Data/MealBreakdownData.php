<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class MealBreakdownData extends Data
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public array $items,
        public ?int $confidence,
        public ?string $source,
    ) {}

    /** @param array<array-key, mixed> $metadata */
    public static function fromMetadata(array $metadata): self
    {
        return new self(
            items: array_map(
                fn (array $item): array => [
                    ...$item,
                    'grams' => is_numeric($item['grams'] ?? null)
                        ? (float) $item['grams']
                        : ReviewedMealData::gramsFromPortion(is_string($item['portion'] ?? null) ? $item['portion'] : null),
                ],
                ReviewedMealData::normalizeItems($metadata['items'] ?? null),
            ),
            confidence: is_int($metadata['confidence'] ?? null) ? $metadata['confidence'] : null,
            source: is_string($metadata['source'] ?? null) ? $metadata['source'] : null,
        );
    }
}
