<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContentType;
use App\Enums\PostCategory;
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
            'title' => sprintf('Is %s Good for Diabetics?', $foodName),
            'meta_data' => [
                'seo_title' => $foodName.' Glycemic Index & Diabetes Safety | Acara Plate',
                'seo_description' => sprintf("Learn about %s's glycemic index, nutritional value, and whether it's safe for diabetics. Get personalized glucose spike predictions.", $foodName),
                'manual_links' => [],
            ],
            'body' => [
                'display_name' => $foodName,
                'diabetic_insight' => sprintf('Based on USDA nutritional data, %s contains moderate carbohydrates. For diabetics, portion control is recommended.', $foodName),
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
            'locale' => 'en',
            'translation_group' => null,
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

    public function post(): static
    {
        return $this->state(function (array $attributes): array {
            /** @var string $postTitle */
            $postTitle = fake()->randomElement([
                'How to Manage Blood Sugar Spikes After Meals',
                '10 Low Glycemic Snacks That Actually Taste Good',
                'Understanding the Glycemic Index: A Complete Guide',
                "Meal Planning for Type 2 Diabetes: A Beginner's Guide",
                'The Truth About Carbs and Diabetes',
            ]);

            /** @var PostCategory $postCategory */
            $postCategory = fake()->randomElement(PostCategory::cases());

            return [
                'type' => ContentType::Post,
                'slug' => Str::slug($postTitle),
                'title' => $postTitle,
                'category' => $postCategory,
                'meta_data' => [
                    'seo_title' => $postTitle.' | Acara Plate',
                    'seo_description' => sprintf('Learn about %s and how it relates to diabetes management and nutrition.', $postTitle),
                    'manual_links' => [],
                ],
                'body' => [
                    'display_name' => $postTitle,
                    'excerpt' => fake()->sentence(12),
                    'content' => fake()->paragraphs(3, asText: true),
                    'reading_time' => fake()->numberBetween(3, 12),
                ],
                'locale' => 'en',
                'translation_group' => null,
            ];
        });
    }

    public function localized(string $locale, ?string $translationGroup = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'locale' => $locale,
            'translation_group' => $translationGroup ?? fake()->uuid(),
        ]);
    }
}
