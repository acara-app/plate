<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Content>
 */
final class ContentFactory extends Factory
{
    protected $model = Content::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var string $foodName */
        $foodName = fake()->randomElement([
            'Banana',
            'Apple',
            'Brown Rice',
            'Chicken Breast',
            'Oatmeal',
            'Salmon',
            'Avocado',
            'Quinoa',
            'Broccoli',
            'Sweet Potato',
        ]);

        return [
            'type' => ContentType::Food,
            'slug' => Str::slug($foodName),
            'title' => "Is {$foodName} Good for Diabetics?",
            'meta_title' => "{$foodName} Glycemic Index & Diabetes Safety | Acara Plate",
            'meta_description' => "Learn about {$foodName}'s glycemic index, nutritional value, and whether it's safe for diabetics. Get personalized glucose spike predictions.",
            'body' => [
                'display_name' => $foodName,
                'diabetic_insight' => "Based on USDA nutritional data, {$foodName} contains moderate carbohydrates. For diabetics, portion control is recommended.",
                'glycemic_assessment' => fake()->randomElement(['low', 'medium', 'high']),
                'nutrition' => [
                    'calories' => fake()->numberBetween(50, 300),
                    'protein' => fake()->randomFloat(1, 0, 30),
                    'carbs' => fake()->randomFloat(1, 0, 50),
                    'fat' => fake()->randomFloat(1, 0, 20),
                    'fiber' => fake()->randomFloat(1, 0, 10),
                    'sugar' => fake()->randomFloat(1, 0, 25),
                ],
            ],
            'image_path' => null,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_published' => false,
        ]);
    }

    public function withImage(): static
    {
        return $this->state(function (array $attributes): array {
            $body = is_array($attributes['body']) ? $attributes['body'] : [];
            $displayName = is_string($body['display_name'] ?? null) ? $body['display_name'] : 'food';

            return ['image_path' => 'food-images/'.Str::slug($displayName).'.png'];
        });
    }
}
