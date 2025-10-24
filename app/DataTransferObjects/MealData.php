<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\MealType;
use InvalidArgumentException;

final readonly class MealData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $dayNumber,
        public MealType $type,
        public string $name,
        public ?string $description,
        public ?string $preparationInstructions,
        public ?string $ingredients,
        public ?string $portionSize,
        public float $calories,
        public ?float $proteinGrams,
        public ?float $carbsGrams,
        public ?float $fatGrams,
        public ?int $preparationTimeMinutes,
        public int $sortOrder,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed>|null $metadata */
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        return new self(
            dayNumber: self::ensureInt($data['day_number']),
            type: MealType::from(self::ensureString($data['type'])),
            name: self::ensureString($data['name']),
            description: isset($data['description']) ? self::ensureString($data['description']) : null,
            preparationInstructions: isset($data['preparation_instructions']) ? self::ensureString($data['preparation_instructions']) : null,
            ingredients: isset($data['ingredients']) ? self::ensureString($data['ingredients']) : null,
            portionSize: isset($data['portion_size']) ? self::ensureString($data['portion_size']) : null,
            calories: self::ensureFloat($data['calories']),
            proteinGrams: isset($data['protein_grams']) ? self::ensureFloat($data['protein_grams']) : null,
            carbsGrams: isset($data['carbs_grams']) ? self::ensureFloat($data['carbs_grams']) : null,
            fatGrams: isset($data['fat_grams']) ? self::ensureFloat($data['fat_grams']) : null,
            preparationTimeMinutes: isset($data['preparation_time_minutes']) ? self::ensureInt($data['preparation_time_minutes']) : null,
            sortOrder: self::ensureInt($data['sort_order']),
            metadata: $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'day_number' => $this->dayNumber,
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'preparation_instructions' => $this->preparationInstructions,
            'ingredients' => $this->ingredients,
            'portion_size' => $this->portionSize,
            'calories' => $this->calories,
            'protein_grams' => $this->proteinGrams,
            'carbs_grams' => $this->carbsGrams,
            'fat_grams' => $this->fatGrams,
            'preparation_time_minutes' => $this->preparationTimeMinutes,
            'sort_order' => $this->sortOrder,
            'metadata' => $this->metadata,
        ];
    }

    private static function ensureInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Value must be convertible to int');
    }

    private static function ensureFloat(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException('Value must be convertible to float');
    }

    private static function ensureString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        throw new InvalidArgumentException('Value must be convertible to string');
    }
}
