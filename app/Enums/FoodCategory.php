<?php

declare(strict_types=1);

namespace App\Enums;

enum FoodCategory: string
{
    case Fruits = 'fruits';
    case Vegetables = 'vegetables';
    case GrainsStarches = 'grains_starches';
    case DairyAlternatives = 'dairy_alternatives';
    case ProteinsLegumes = 'proteins_legumes';
    case NutsSeeds = 'nuts_seeds';
    case Beverages = 'beverages';
    case CondimentsSauces = 'condiments_sauces';
    case SnacksSweets = 'snacks_sweets';
    case Other = 'other';

    /**
     * Get all categories as options for forms/filters.
     *
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
            self::Fruits => 'Fruits',
            self::Vegetables => 'Vegetables',
            self::GrainsStarches => 'Grains & Starches',
            self::DairyAlternatives => 'Dairy & Alternatives',
            self::ProteinsLegumes => 'Proteins & Legumes',
            self::NutsSeeds => 'Nuts & Seeds',
            self::Beverages => 'Beverages',
            self::CondimentsSauces => 'Condiments & Sauces',
            self::SnacksSweets => 'Snacks & Sweets',
            self::Other => 'Other',
        };
    }

    /**
     * Get the display order for sorting categories.
     */
    public function order(): int
    {
        return match ($this) {
            self::Fruits => 1,
            self::Vegetables => 2,
            self::GrainsStarches => 3,
            self::DairyAlternatives => 4,
            self::ProteinsLegumes => 5,
            self::NutsSeeds => 6,
            self::Beverages => 7,
            self::CondimentsSauces => 8,
            self::SnacksSweets => 9,
            self::Other => 99,
        };
    }

    /**
     * Get average glycemic index for the food category.
     *
     * These are approximate category averages based on published GI data.
     * Used to calculate glycemic load when exact GI is not available.
     */
    public function averageGlycemicIndex(): int
    {
        return match ($this) {
            self::Fruits => 40,             // Most fruits are low-medium GI
            self::Vegetables => 15,         // Non-starchy vegetables are very low
            self::GrainsStarches => 65,     // Grains/starches tend to be medium-high
            self::DairyAlternatives => 35,  // Dairy products are generally low GI
            self::ProteinsLegumes => 30,    // Legumes are low GI, proteins minimal
            self::NutsSeeds => 15,          // Nuts and seeds are very low GI
            self::Beverages => 50,          // Varies widely, use moderate default
            self::CondimentsSauces => 30,   // Typically low due to small portions
            self::SnacksSweets => 70,       // Snacks/sweets tend to be high GI
            self::Other => 50,              // Default moderate value
        };
    }
}
