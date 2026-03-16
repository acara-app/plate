<?php

declare(strict_types=1);

namespace App\Enums;

enum DietType: string
{
    case Mediterranean = 'mediterranean';
    case LowCarb = 'low_carb';
    case Keto = 'keto';
    case Dash = 'dash';
    case Vegetarian = 'vegetarian';
    case Vegan = 'vegan';
    case Paleo = 'paleo';
    case Balanced = 'balanced';

    /**
     * @return array<string, string>
     */
    public static function toArray(): array
    {
        return [
            self::Mediterranean->value => self::Mediterranean->label(),
            self::LowCarb->value => self::LowCarb->label(),
            self::Keto->value => self::Keto->label(),
            self::Dash->value => self::Dash->label(),
            self::Vegetarian->value => self::Vegetarian->label(),
            self::Vegan->value => self::Vegan->label(),
            self::Paleo->value => self::Paleo->label(),
            self::Balanced->value => self::Balanced->label(),
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Mediterranean => 'Mediterranean (Gold Standard)',
            self::LowCarb => 'Low Carb (Diabetic Friendly)',
            self::Keto => 'Ketogenic (Strict)',
            self::Dash => 'DASH Diet',
            self::Vegetarian => 'Vegetarian',
            self::Vegan => 'Vegan',
            self::Paleo => 'Paleo',
            self::Balanced => 'Standard Balanced (USDA)',
        };
    }

    public function shortName(): string
    {
        return match ($this) {
            self::Mediterranean => 'Mediterranean',
            self::LowCarb => 'Low Carb',
            self::Keto => 'Keto',
            self::Dash => 'DASH',
            self::Vegetarian => 'Vegetarian',
            self::Vegan => 'Vegan',
            self::Paleo => 'Paleo',
            self::Balanced => 'Balanced',
        };
    }

    public function focus(): string
    {
        return match ($this) {
            self::Mediterranean => 'High healthy fats (olive oil), fiber, lean proteins. Low red meat.',
            self::LowCarb => 'Reduced carbohydrates (<130g), increased protein and fats.',
            self::Keto => 'Extremely low carbohydrate (<20g), very high fat.',
            self::Dash => 'Low sodium, high potassium, rich in fruits and vegetables.',
            self::Vegetarian => 'No meat/poultry/fish. Eggs and dairy allowed.',
            self::Vegan => 'Strictly plant-based. No animal products.',
            self::Paleo => 'Ancestral eating. No grains, legumes, or dairy.',
            self::Balanced => 'Moderate mix of all macronutrients per dietary guidelines.',
        };
    }

    /**
     * @return array{carbs: int, protein: int, fat: int}
     */
    public function macroTargets(): array
    {
        return match ($this) {
            self::Mediterranean => ['carbs' => 45, 'protein' => 18, 'fat' => 37],

            self::LowCarb => ['carbs' => 20, 'protein' => 35, 'fat' => 45],

            self::Keto => ['carbs' => 5, 'protein' => 20, 'fat' => 75],

            self::Dash => ['carbs' => 52, 'protein' => 18, 'fat' => 30],

            self::Paleo => ['carbs' => 30, 'protein' => 35, 'fat' => 35],

            self::Vegetarian => ['carbs' => 55, 'protein' => 15, 'fat' => 30],
            self::Vegan => ['carbs' => 60, 'protein' => 14, 'fat' => 26],

            self::Balanced => ['carbs' => 50, 'protein' => 20, 'fat' => 30],
        };
    }

    public function isDiabeticFriendly(): bool
    {
        return match ($this) {
            self::Mediterranean,
            self::LowCarb,
            self::Keto,
            self::Dash,
            self::Balanced,
            self::Vegetarian => true,

            default => false,
        };
    }
}
