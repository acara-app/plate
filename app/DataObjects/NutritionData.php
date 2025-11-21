<?php

declare(strict_types=1);

namespace App\DataObjects;

final readonly class NutritionData
{
    public function __construct(
        public ?float $calories,
        public ?float $protein,
        public ?float $carbs,
        public ?float $fat,
        public ?float $fiber,
        public ?float $sugar,
        public ?float $sodium,
    ) {}

    /**
     * @param  array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            calories: $data['calories'],
            protein: $data['protein'],
            carbs: $data['carbs'],
            fat: $data['fat'],
            fiber: $data['fiber'],
            sugar: $data['sugar'],
            sodium: $data['sodium'],
        );
    }

    /**
     * @return array{calories: float|null, protein: float|null, carbs: float|null, fat: float|null, fiber: float|null, sugar: float|null, sodium: float|null}
     */
    public function toArray(): array
    {
        return [
            'calories' => $this->calories,
            'protein' => $this->protein,
            'carbs' => $this->carbs,
            'fat' => $this->fat,
            'fiber' => $this->fiber,
            'sugar' => $this->sugar,
            'sodium' => $this->sodium,
        ];
    }
}
