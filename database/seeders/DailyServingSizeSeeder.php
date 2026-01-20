<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the database with USDA Daily Serving Size guidelines from the
 * Dietary Guidelines for Americans, 2025-2030.
 *
 * Data source: realfood.gov - Daily Serving Sizes by Calorie Level
 */
final class DailyServingSizeSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDailyServingSizes();
        $this->seedSugarLimits();
    }

    private function seedDailyServingSizes(): void
    {
        $foodGroups = [
            [
                'food_group' => 'Protein Foods',
                'serving_size_unit' => '3 oz cooked meat, poultry, or seafood; 1 egg; ½ cup beans, peas, or lentils; 1 oz nuts or seeds; 2 tbsp nut or seed butter; 3 oz soy',
                'serving_size_examples' => [
                    '3 oz cooked meat',
                    '3 oz cooked poultry',
                    '3 oz cooked seafood',
                    '1 egg',
                    '½ cup beans, peas, or lentils',
                    '1 oz nuts or seeds',
                    '2 tbsp nut or seed butter',
                    '3 oz soy',
                ],
                'servings_by_calorie_level' => [
                    '1000' => ['min' => 1, 'max' => 1.5],
                    '1200' => ['min' => 1.5, 'max' => 2],
                    '1400' => ['min' => 2, 'max' => 2.5],
                    '1600' => ['min' => 2.5, 'max' => 3.5],
                    '1800' => ['min' => 2.5, 'max' => 3.5],
                    '2000' => ['min' => 3, 'max' => 4],
                    '2200' => ['min' => 3.5, 'max' => 4.5],
                    '2400' => ['min' => 3.5, 'max' => 4.5],
                    '2600' => ['min' => 3.5, 'max' => 4.5],
                    '2800' => ['min' => 4, 'max' => 5],
                    '3000' => ['min' => 4, 'max' => 5],
                    '3200' => ['min' => 4, 'max' => 5],
                ],
                'food_group_details' => 'Animal- and plant-based protein foods, including meat, poultry, eggs, seafood, beans, peas, lentils, legumes, nuts, seeds, and soy.',
            ],
            [
                'food_group' => 'Dairy',
                'serving_size_unit' => '1 cup milk; ¾ cup yogurt; 1 oz cheese',
                'serving_size_examples' => [
                    '1 cup milk',
                    '¾ cup yogurt',
                    '1 oz cheese',
                ],
                'servings_by_calorie_level' => [
                    '1000' => ['min' => 2, 'max' => 2],
                    '1200' => ['min' => 2.5, 'max' => 2.5],
                    '1400' => ['min' => 2.5, 'max' => 2.5],
                    '1600' => ['min' => 3, 'max' => 3],
                    '1800' => ['min' => 3, 'max' => 3],
                    '2000' => ['min' => 3, 'max' => 3],
                    '2200' => ['min' => 3, 'max' => 3],
                    '2400' => ['min' => 3, 'max' => 3],
                    '2600' => ['min' => 3, 'max' => 3],
                    '2800' => ['min' => 3, 'max' => 3],
                    '3000' => ['min' => 3, 'max' => 3],
                    '3200' => ['min' => 3, 'max' => 3],
                ],
                'food_group_details' => 'Whole, reduced-fat, low-fat, or nonfat dairy products, including fluid, dry, or evaporated milk; yogurt; and cheeses. Lactose-free and lactose-reduced products, as well as fortified dairy alternatives, are also options.',
            ],
            [
                'food_group' => 'Vegetables',
                'serving_size_unit' => '1 cup raw or cooked; 2 cups leafy greens',
                'serving_size_examples' => [
                    '1 cup raw vegetables',
                    '1 cup cooked vegetables',
                    '2 cups leafy greens',
                ],
                'servings_by_calorie_level' => [
                    '1000' => ['min' => 1.25, 'max' => 1.25],
                    '1200' => ['min' => 1.75, 'max' => 1.75],
                    '1400' => ['min' => 1.75, 'max' => 1.75],
                    '1600' => ['min' => 2.5, 'max' => 2.5],
                    '1800' => ['min' => 3, 'max' => 3],
                    '2000' => ['min' => 3, 'max' => 3],
                    '2200' => ['min' => 3.5, 'max' => 3.5],
                    '2400' => ['min' => 3.5, 'max' => 3.5],
                    '2600' => ['min' => 4.25, 'max' => 4.25],
                    '2800' => ['min' => 4.25, 'max' => 4.25],
                    '3000' => ['min' => 4.75, 'max' => 4.75],
                    '3200' => ['min' => 4.75, 'max' => 4.75],
                ],
                'food_group_details' => 'Vegetables of all types—dark green; red and orange; beans, peas, lentils, and legumes; starchy; and other vegetables, including fresh, frozen, and canned, cooked, or raw vegetables.',
            ],
            [
                'food_group' => 'Fruits',
                'serving_size_unit' => '1 cup raw; ½ cup dried',
                'serving_size_examples' => [
                    '1 cup raw fruit',
                    '½ cup dried fruit',
                ],
                'servings_by_calorie_level' => [
                    '1000' => ['min' => 1, 'max' => 1],
                    '1200' => ['min' => 1, 'max' => 1],
                    '1400' => ['min' => 1.5, 'max' => 1.5],
                    '1600' => ['min' => 1.5, 'max' => 1.5],
                    '1800' => ['min' => 1.5, 'max' => 1.5],
                    '2000' => ['min' => 2, 'max' => 2],
                    '2200' => ['min' => 2, 'max' => 2],
                    '2400' => ['min' => 2, 'max' => 2],
                    '2600' => ['min' => 2, 'max' => 2],
                    '2800' => ['min' => 2.5, 'max' => 2.5],
                    '3000' => ['min' => 2.5, 'max' => 2.5],
                    '3200' => ['min' => 2.5, 'max' => 2.5],
                ],
                'food_group_details' => 'Fruits of all types, including fresh, frozen, canned, juiced, and dried fruits.',
            ],
            [
                'food_group' => 'Whole Grains',
                'serving_size_unit' => '½ cup cooked oats, brown rice, barley, quinoa, or buckwheat; 1 slice bread; 1 tortilla',
                'serving_size_examples' => [
                    '½ cup cooked oats',
                    '½ cup cooked brown rice',
                    '½ cup cooked barley',
                    '½ cup cooked quinoa',
                    '½ cup cooked buckwheat',
                    '1 slice bread',
                    '1 tortilla',
                ],
                'servings_by_calorie_level' => [
                    '1000' => ['min' => 1, 'max' => 2],
                    '1200' => ['min' => 1.5, 'max' => 2.75],
                    '1400' => ['min' => 1.75, 'max' => 3.25],
                    '1600' => ['min' => 1.75, 'max' => 3.25],
                    '1800' => ['min' => 2, 'max' => 4],
                    '2000' => ['min' => 2, 'max' => 4],
                    '2200' => ['min' => 2.25, 'max' => 4.5],
                    '2400' => ['min' => 2.75, 'max' => 5.25],
                    '2600' => ['min' => 3, 'max' => 6],
                    '2800' => ['min' => 3.25, 'max' => 6.5],
                    '3000' => ['min' => 3.25, 'max' => 6.5],
                    '3200' => ['min' => 3.25, 'max' => 6.5],
                ],
                'food_group_details' => 'All whole-grain foods and products made with whole grains as ingredients.',
            ],
            [
                'food_group' => 'Healthy Fats',
                'serving_size_unit' => '1 tsp olive oil or butter',
                'serving_size_examples' => [
                    '1 tsp olive oil',
                    '1 tsp butter',
                ],
                'servings_by_calorie_level' => [
                    '1000' => ['min' => 2.5, 'max' => 2.5],
                    '1200' => ['min' => 2.5, 'max' => 2.5],
                    '1400' => ['min' => 2.5, 'max' => 2.5],
                    '1600' => ['min' => 3.5, 'max' => 3.5],
                    '1800' => ['min' => 4, 'max' => 4],
                    '2000' => ['min' => 4.5, 'max' => 4.5],
                    '2200' => ['min' => 4.5, 'max' => 4.5],
                    '2400' => ['min' => 5, 'max' => 5],
                    '2600' => ['min' => 5.5, 'max' => 5.5],
                    '2800' => ['min' => 6, 'max' => 6],
                    '3000' => ['min' => 7, 'max' => 7],
                    '3200' => ['min' => 8, 'max' => 8],
                ],
                'food_group_details' => 'Healthy fats are naturally present in many whole foods, and small amounts may also be used when cooking with or adding fats to meals.',
            ],
        ];

        foreach ($foodGroups as $foodGroup) {
            $slug = Str::slug('usda-daily-serving-'.$foodGroup['food_group']);

            Content::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => ContentType::UsdaDailyServingSize->value,
                    'slug' => $slug,
                    'title' => $foodGroup['food_group'].' - USDA Daily Serving Size',
                    'meta_title' => 'USDA Daily Serving Size: '.$foodGroup['food_group'],
                    'meta_description' => 'Recommended daily servings of '.$foodGroup['food_group'].' based on the Dietary Guidelines for Americans, 2025-2030.',
                    'body' => [
                        'food_group' => $foodGroup['food_group'],
                        'serving_size_unit' => $foodGroup['serving_size_unit'],
                        'serving_size_examples' => $foodGroup['serving_size_examples'],
                        'servings_by_calorie_level' => $foodGroup['servings_by_calorie_level'],
                        'food_group_details' => $foodGroup['food_group_details'],
                        'source' => 'Dietary Guidelines for Americans, 2025-2030',
                        'source_url' => 'https://realfood.gov',
                    ],
                    'is_published' => true,
                ]
            );
        }
    }

    private function seedSugarLimits(): void
    {
        $sugarLimits = [
            [
                'food_group' => 'Grain product',
                'minimum_equivalent' => '¾ oz whole-grain equivalent',
                'added_sugar_limit_grams' => 5,
            ],
            [
                'food_group' => 'Dairy product',
                'minimum_equivalent' => '⅔ cup equivalent',
                'added_sugar_limit_grams' => 2.5,
            ],
            [
                'food_group' => 'Vegetable product',
                'minimum_equivalent' => '½ cup equivalent',
                'added_sugar_limit_grams' => 1,
            ],
            [
                'food_group' => 'Fruit product',
                'minimum_equivalent' => '½ cup equivalent',
                'added_sugar_limit_grams' => 1,
            ],
            [
                'food_group' => 'Game meat',
                'minimum_equivalent' => '1½ oz equivalent',
                'added_sugar_limit_grams' => 1,
            ],
            [
                'food_group' => 'Seafood',
                'minimum_equivalent' => '1 oz equivalent',
                'added_sugar_limit_grams' => 1,
            ],
            [
                'food_group' => 'Eggs',
                'minimum_equivalent' => '1 egg',
                'added_sugar_limit_grams' => 1,
            ],
            [
                'food_group' => 'Beans, peas, and lentils',
                'minimum_equivalent' => '1 oz equivalent',
                'added_sugar_limit_grams' => 1,
            ],
            [
                'food_group' => 'Nuts, seeds, and soy products',
                'minimum_equivalent' => '1 oz equivalent',
                'added_sugar_limit_grams' => 1,
            ],
        ];

        foreach ($sugarLimits as $limit) {
            $slug = Str::slug('usda-sugar-limit-'.$limit['food_group']);

            Content::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => ContentType::UsdaSugarLimit->value,
                    'slug' => $slug,
                    'title' => $limit['food_group'].' - FDA Healthy Claim Sugar Limit',
                    'meta_title' => 'FDA Healthy Claim: '.$limit['food_group'].' Sugar Limit',
                    'meta_description' => 'FDA "Healthy" claim added sugar limit for '.$limit['food_group'].': '.$limit['added_sugar_limit_grams'].' grams.',
                    'body' => [
                        'food_group' => $limit['food_group'],
                        'minimum_equivalent' => $limit['minimum_equivalent'],
                        'added_sugar_limit_grams' => $limit['added_sugar_limit_grams'],
                        'source' => 'FDA "Healthy" Claim Guidelines',
                    ],
                    'is_published' => true,
                ]
            );
        }
    }
}
