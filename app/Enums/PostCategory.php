<?php

declare(strict_types=1);

namespace App\Enums;

enum PostCategory: string
{
    case ProductUpdates = 'product_updates';
    case NutritionTips = 'nutrition_tips';
    case Recipes = 'recipes';
    case Research = 'research';
    case Lifestyle = 'lifestyle';
    case DiabetesManagement = 'diabetes_management';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function label(): string
    {
        return __('post.category_'.$this->value);
    }

    public function title(): string
    {
        return __('post.category_title_'.$this->value);
    }

    public function description(): string
    {
        return __('post.category_desc_'.$this->value);
    }

    public function order(): int
    {
        return match ($this) {
            self::ProductUpdates => 1,
            self::NutritionTips => 2,
            self::Recipes => 3,
            self::Research => 4,
            self::Lifestyle => 5,
            self::DiabetesManagement => 6,
        };
    }
}
