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
     * SEO-friendly title for the food category.
     * Focus: Glycemic Index, Diabetes Safety, Blood Sugar Impact.
     */
    public function title(): string
    {
        return match ($this) {
            self::Fruits => 'Diabetic Friendly Fruits: Glycemic Index & Sugar Safety Chart',
            self::Vegetables => 'Low Carb Vegetables: Non-Starchy List for Blood Sugar Control',
            self::GrainsStarches => 'Grains & Starches: Glycemic Index & Carb Counting Guide',
            self::DairyAlternatives => 'Dairy & Alternatives: Glucose Impact & Lactose Guide',
            self::ProteinsLegumes => 'Proteins & Legumes: Blood Sugar Stabilizers & Fiber List',
            self::NutsSeeds => 'Best Nuts & Seeds for Diabetics: Zero Spike Snacking',
            self::Beverages => 'Diabetic Safe Drinks: No-Spike Juices & Hydration List',
            self::CondimentsSauces => 'Condiments & Sauces: Hidden Sugars & Carb Count Detector',
            self::SnacksSweets => 'Low GI Sweets: Diabetes Friendly Desserts & Treat Guide',
            self::Other => 'Specialty Foods Database: Glycemic Index & Nutrition Facts',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Fruits => 'Fruits contain natural fructose. We analyze the essential FDA foundational list to show you exactly which common fruits—from berries to bananas—have a safe glycemic impact.',
            self::Vegetables => 'From leafy greens to starchy roots, we grade the glycemic impact of everyday vegetables. Use our data to distinguish between "free foods" and those that require portion control.',
            self::GrainsStarches => 'Not all carbs are equal. We display the glycemic index for pantry staples like whole quinoa and white rice, helping you spot the difference between slow-digesting grains and refined starches.',
            self::DairyAlternatives => 'Dairy contains lactose (milk sugar). We reveal the insulin impact of common milks, yogurts, and cheeses so you can see which options fit your glucose management goals.',
            self::ProteinsLegumes => 'Proteins are usually safe, but preparation matters. We break down the data for core proteins and legumes to help you identify healthy options with hidden carb loads.',
            self::NutsSeeds => 'Healthy fats usually stabilize blood sugar, but carb counts vary. Check the glycemic score of verified nuts and seeds to find zero-spike snacking options.',
            self::Beverages => 'Liquids hit the bloodstream fast. We rate standard beverages—from pure juices to coffee—so you can instantly spot the "sugar bombs" that bypass digestion.',
            self::CondimentsSauces => 'Sauces are often where sugar hides. We expose the true carb counts in key condiments like ketchup and dressings, ensuring you don\'t ruin a healthy meal with the wrong topping.',
            self::SnacksSweets => 'Craving something sweet? We analyze the glycemic load of classic treats to help you find the ones that satisfy a craving without sending your numbers off the charts.',
            self::Other => 'Unsure about a specific ingredient? Search our verified database to uncover the nutritional facts and glycemic safety of miscellaneous common foods.',
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
