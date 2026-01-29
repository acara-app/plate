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
            self::Fruits => 'Fruits contain natural fructose, but their fiber content often buffers the insulin response. We focus on low-glycemic berries and stone fruits that satisfy sweet cravings without causing rapid glucose spikes.',
            self::Vegetables => 'The cornerstone of blood sugar management. These non-starchy powerhouses are high in volume, rich in micronutrients, and have a negligible impact on blood glucose, making them "free foods" for most diabetics.',
            self::GrainsStarches => 'The trickiest category for glucose control. We distinguish between complex, slow-digesting grains (like quinoa/barley) and refined starches that act like sugar. Portion control here is critical for preventing post-meal spikes.',
            self::DairyAlternatives => 'A source of protein and calcium, but watch out for lactose (milk sugar). We prioritize full-fat or fermented options like Greek yogurt which have a lower insulin index compared to skim milk.',
            self::ProteinsLegumes => 'Your best defense against spikes. Protein slows down the absorption of carbohydrates when eaten together. Legumes offer a double benefit: high protein plus "resistant starch" that improves insulin sensitivity.',
            self::NutsSeeds => 'The ultimate blood sugar stabilizers. Packed with healthy fats and fiber, adding a handful of nuts to a carb-heavy meal can significantly lower the overall glycemic load of that meal.',
            self::Beverages => 'Hydration without the sugar crash. We analyze everything from coffee and tea to fruit juices, helping you identify "liquid sugar" bombs that bypass digestion and spike glucose instantly.',
            self::CondimentsSauces => 'Hidden sugars often lurk here. From ketchup to BBQ sauce, we expose the secret carb counts in your favorite toppings so you can add flavor without ruining your daily numbers.',
            self::SnacksSweets => 'Treats, optimized. You don\'t have to live without dessert, but you do need strategy. We focus on high-fat, low-carb treats that satisfy cravings while keeping you in a safe glucose range.',
            self::Other => 'Miscellaneous items and ingredients. Always verify the nutritional label, as these specialized products can vary wildly in their glycemic impact.',
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
