<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GroceryList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroceryItem>
 */
final class GroceryItemFactory extends Factory
{
    private const array CATEGORIES = [
        'Produce',
        'Dairy',
        'Meat & Seafood',
        'Pantry',
        'Frozen',
        'Bakery',
        'Beverages',
        'Other',
    ];

    private const array SAMPLE_ITEMS = [
        'Produce' => ['Apples', 'Bananas', 'Spinach', 'Carrots', 'Onions', 'Garlic', 'Tomatoes', 'Bell Peppers'],
        'Dairy' => ['Milk', 'Greek Yogurt', 'Cheese', 'Eggs', 'Butter', 'Cream Cheese'],
        'Meat & Seafood' => ['Chicken Breast', 'Ground Beef', 'Salmon', 'Shrimp', 'Turkey'],
        'Pantry' => ['Rice', 'Pasta', 'Olive Oil', 'Canned Beans', 'Flour', 'Sugar', 'Oats'],
        'Frozen' => ['Frozen Vegetables', 'Ice Cream', 'Frozen Berries'],
        'Bakery' => ['Bread', 'Tortillas', 'Bagels'],
        'Beverages' => ['Orange Juice', 'Coffee', 'Tea'],
        'Other' => ['Honey', 'Maple Syrup', 'Soy Sauce'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(self::CATEGORIES);
        $items = self::SAMPLE_ITEMS[$category] ?? ['Item'];

        return [
            'grocery_list_id' => GroceryList::factory(),
            'name' => fake()->randomElement($items),
            'quantity' => fake()->randomElement(['1', '2', '3', '500g', '1 lb', '1 cup', '2 cups', '1 dozen']),
            'category' => $category,
            'is_checked' => false,
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function checked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_checked' => true,
        ]);
    }

    public function inCategory(string $category): static
    {
        $items = self::SAMPLE_ITEMS[$category] ?? ['Item'];

        return $this->state(fn (array $attributes): array => [
            'category' => $category,
            'name' => fake()->randomElement($items),
        ]);
    }
}
