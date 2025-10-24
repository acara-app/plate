<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\MealPlanType;
use InvalidArgumentException;

final readonly class MealPlanData
{
    /**
     * @param  array<int, MealData>  $meals
     * @param  array{protein: int, carbs: int, fat: int}|null  $macronutrientRatios
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public MealPlanType $type,
        public ?string $name,
        public ?string $description,
        public int $durationDays,
        public ?float $targetDailyCalories,
        public ?array $macronutrientRatios,
        public array $meals,
        public ?array $metadata = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<int, array<string, mixed>> $mealsData */
        $mealsData = $data['meals'] ?? [];

        $meals = array_map(
            fn (array $meal): MealData => MealData::fromArray($meal),
            $mealsData
        );

        /** @var array{protein: int, carbs: int, fat: int}|null $macronutrientRatios */
        $macronutrientRatios = isset($data['macronutrient_ratios']) && is_array($data['macronutrient_ratios'])
            ? $data['macronutrient_ratios']
            : null;

        /** @var array<string, mixed>|null $metadata */
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        return new self(
            type: MealPlanType::from(self::ensureString($data['type'])),
            name: isset($data['name']) ? self::ensureString($data['name']) : null,
            description: isset($data['description']) ? self::ensureString($data['description']) : null,
            durationDays: self::ensureInt($data['duration_days']),
            targetDailyCalories: isset($data['target_daily_calories']) ? self::ensureFloat($data['target_daily_calories']) : null,
            macronutrientRatios: $macronutrientRatios,
            meals: $meals,
            metadata: $metadata,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'name' => $this->name,
            'description' => $this->description,
            'duration_days' => $this->durationDays,
            'target_daily_calories' => $this->targetDailyCalories,
            'macronutrient_ratios' => $this->macronutrientRatios,
            'meals' => array_map(fn (MealData $meal): array => $meal->toArray(), $this->meals),
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
