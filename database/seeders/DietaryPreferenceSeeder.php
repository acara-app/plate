<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DietaryPreference;
use Illuminate\Database\Seeder;

final class DietaryPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $preferences = [
            // Dietary Patterns
            ['name' => 'Vegan', 'type' => 'pattern', 'description' => 'Plant-based diet excluding all animal products including meat, dairy, eggs, and honey.'],
            ['name' => 'Vegetarian', 'type' => 'pattern', 'description' => 'Diet excluding meat and fish but may include dairy and eggs.'],
            ['name' => 'Pescatarian', 'type' => 'pattern', 'description' => 'Vegetarian diet that includes fish and seafood.'],
            ['name' => 'Keto', 'type' => 'pattern', 'description' => 'Very low-carb, high-fat diet aimed at achieving ketosis for fat burning.'],
            ['name' => 'Paleo', 'type' => 'pattern', 'description' => 'Diet based on foods presumed to have been eaten during the Paleolithic era.'],
            ['name' => 'Mediterranean', 'type' => 'pattern', 'description' => 'Diet rich in fruits, vegetables, whole grains, legumes, and healthy fats like olive oil.'],
            ['name' => 'Low-Carb', 'type' => 'pattern', 'description' => 'Diet that restricts carbohydrate consumption to promote weight loss and blood sugar control.'],
            ['name' => 'High-Protein', 'type' => 'pattern', 'description' => 'Diet emphasizing protein-rich foods for muscle building and satiety.'],
            ['name' => 'Intermittent Fasting', 'type' => 'pattern', 'description' => 'Eating pattern that cycles between periods of fasting and eating.'],
            ['name' => 'DASH', 'type' => 'pattern', 'description' => 'Dietary Approaches to Stop Hypertension - diet rich in fruits, vegetables, and low-fat dairy.'],
            ['name' => 'Whole30', 'type' => 'pattern', 'description' => '30-day elimination diet removing sugar, alcohol, grains, legumes, soy, and dairy.'],

            // Common Allergies
            ['name' => 'Peanuts', 'type' => 'allergy', 'description' => 'Severe allergic reaction to peanuts and peanut-containing products.'],
            ['name' => 'Tree Nuts', 'type' => 'allergy', 'description' => 'Allergic to almonds, walnuts, cashews, pecans, and other tree nuts.'],
            ['name' => 'Dairy', 'type' => 'allergy', 'description' => 'Allergic reaction to milk proteins (casein and whey).'],
            ['name' => 'Eggs', 'type' => 'allergy', 'description' => 'Allergic to egg proteins, particularly those in egg whites.'],
            ['name' => 'Soy', 'type' => 'allergy', 'description' => 'Allergic reaction to soy proteins found in soybeans and soy products.'],
            ['name' => 'Wheat', 'type' => 'allergy', 'description' => 'Allergic to wheat proteins, distinct from celiac disease or gluten sensitivity.'],
            ['name' => 'Shellfish', 'type' => 'allergy', 'description' => 'Allergic to crustaceans (shrimp, crab, lobster) and mollusks (clams, oysters).'],
            ['name' => 'Fish', 'type' => 'allergy', 'description' => 'Allergic to finned fish like salmon, tuna, cod, and halibut.'],
            ['name' => 'Sesame', 'type' => 'allergy', 'description' => 'Allergic to sesame seeds and sesame oil.'],

            // Food Intolerances
            ['name' => 'Lactose', 'type' => 'intolerance', 'description' => 'Difficulty digesting lactose, the sugar found in milk and dairy products.'],
            ['name' => 'Gluten', 'type' => 'intolerance', 'description' => 'Sensitivity to gluten proteins found in wheat, barley, and rye (non-celiac).'],
            ['name' => 'FODMAPs', 'type' => 'intolerance', 'description' => 'Sensitivity to fermentable carbohydrates that can cause digestive symptoms.'],
            ['name' => 'Histamine', 'type' => 'intolerance', 'description' => 'Difficulty breaking down histamine in aged, fermented, or cured foods.'],
            ['name' => 'Fructose', 'type' => 'intolerance', 'description' => 'Difficulty absorbing fructose, leading to digestive discomfort.'],
            ['name' => 'Caffeine', 'type' => 'intolerance', 'description' => 'Sensitivity to caffeine resulting in jitters, anxiety, or sleep disruption.'],
            ['name' => 'Sulfites', 'type' => 'intolerance', 'description' => 'Sensitivity to sulfite preservatives found in wine, dried fruits, and processed foods.'],

            // Common Food Dislikes
            ['name' => 'Mushrooms', 'type' => 'dislike', 'description' => 'Preference to avoid mushrooms due to taste or texture.'],
            ['name' => 'Cilantro', 'type' => 'dislike', 'description' => 'Preference to avoid cilantro, often described as tasting soapy.'],
            ['name' => 'Olives', 'type' => 'dislike', 'description' => 'Preference to avoid olives and olive-based products.'],
            ['name' => 'Blue Cheese', 'type' => 'dislike', 'description' => 'Preference to avoid blue cheese and other strong moldy cheeses.'],
            ['name' => 'Anchovies', 'type' => 'dislike', 'description' => 'Preference to avoid anchovies due to their strong, salty flavor.'],
            ['name' => 'Liver', 'type' => 'dislike', 'description' => 'Preference to avoid liver and organ meats.'],
            ['name' => 'Brussels Sprouts', 'type' => 'dislike', 'description' => 'Preference to avoid Brussels sprouts due to taste or texture.'],
            ['name' => 'Raw Onions', 'type' => 'dislike', 'description' => 'Preference to avoid raw onions while cooked may be acceptable.'],
            ['name' => 'Spicy Foods', 'type' => 'dislike', 'description' => 'Preference to avoid hot and spicy foods.'],
        ];

        foreach ($preferences as $preference) {
            DietaryPreference::create($preference);
        }
    }
}
