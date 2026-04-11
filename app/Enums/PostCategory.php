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
        return match ($this) {
            self::ProductUpdates => 'Product Updates',
            self::NutritionTips => 'Nutrition Tips',
            self::Recipes => 'Recipes',
            self::Research => 'Research',
            self::Lifestyle => 'Lifestyle',
            self::DiabetesManagement => 'Diabetes Management',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::ProductUpdates => 'Product Updates & Announcements',
            self::NutritionTips => 'Nutrition Tips: Eat Smarter, Live Better',
            self::Recipes => 'Healthy Recipes: Balanced Meals & Snacks',
            self::Research => 'Health Research & Nutrition Science',
            self::Lifestyle => 'Lifestyle & Wellness',
            self::DiabetesManagement => 'Diabetes Management & Blood Sugar Control',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ProductUpdates => 'News about Acara Plate — new features, product updates, and our journey to make health accessible for everyone.',
            self::NutritionTips => 'Evidence-based nutrition advice — understanding macros, glycemic index, and making smarter food choices for your health goals.',
            self::Recipes => 'Delicious, health-conscious recipes designed to keep you nourished without sacrificing flavor.',
            self::Research => 'Summaries of the latest research in nutrition science, metabolic health, and wellness.',
            self::Lifestyle => 'Tips for exercising, sleeping better, and managing stress — the foundations of a healthy life.',
            self::DiabetesManagement => 'Practical guides for managing diabetes, monitoring blood sugar, and preventing glucose spikes.',
        };
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
