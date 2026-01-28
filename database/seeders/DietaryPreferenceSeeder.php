<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DietaryPreferenceType;
use App\Models\DietaryPreference;
use Illuminate\Database\Seeder;

final class DietaryPreferenceSeeder extends Seeder
{
    public function run(): void
    {
        $preferences = [
            // Common Allergies
            ['name' => 'Peanuts', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Severe allergic reaction to peanuts and peanut-containing products.'],
            ['name' => 'Tree Nuts', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to almonds, walnuts, cashews, pecans, and other tree nuts.'],
            ['name' => 'Dairy', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic reaction to milk proteins (casein and whey).'],
            ['name' => 'Eggs', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to egg proteins, particularly those in egg whites.'],
            ['name' => 'Soy', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic reaction to soy proteins found in soybeans and soy products.'],
            ['name' => 'Wheat', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to wheat proteins, distinct from celiac disease or gluten sensitivity.'],
            ['name' => 'Shellfish', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to crustaceans (shrimp, crab, lobster) and mollusks (clams, oysters).'],
            ['name' => 'Fish', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to finned fish like salmon, tuna, cod, and halibut.'],
            ['name' => 'Sesame', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to sesame seeds and sesame oil.'],
            ['name' => 'Mustard', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to mustard seeds and mustard-containing products.'],
            ['name' => 'Lupin', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to lupin beans and lupin flour, often found in gluten-free products.'],
            ['name' => 'Celery', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to celery and celeriac, including celery salt and celery seeds.'],
            ['name' => 'Corn', 'type' => DietaryPreferenceType::Allergy->value, 'description' => 'Allergic to corn and corn-derived products including corn syrup and cornstarch.'],

            // Food Intolerances
            ['name' => 'Lactose', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Difficulty digesting lactose, the sugar found in milk and dairy products.'],
            ['name' => 'Gluten', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Sensitivity to gluten proteins found in wheat, barley, and rye (non-celiac).'],
            ['name' => 'FODMAPs', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Sensitivity to fermentable carbohydrates that can cause digestive symptoms.'],
            ['name' => 'Histamine', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Difficulty breaking down histamine in aged, fermented, or cured foods.'],
            ['name' => 'Fructose', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Difficulty absorbing fructose, leading to digestive discomfort.'],
            ['name' => 'Caffeine', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Sensitivity to caffeine resulting in jitters, anxiety, or sleep disruption.'],
            ['name' => 'Sulfites', 'type' => DietaryPreferenceType::Intolerance->value, 'description' => 'Sensitivity to sulfite preservatives found in wine, dried fruits, and processed foods.'],

            // Common Food Dislikes
            ['name' => 'Mushrooms', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid mushrooms due to taste or texture.'],
            ['name' => 'Cilantro', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid cilantro, often described as tasting soapy.'],
            ['name' => 'Olives', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid olives and olive-based products.'],
            ['name' => 'Blue Cheese', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid blue cheese and other strong moldy cheeses.'],
            ['name' => 'Anchovies', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid anchovies due to their strong, salty flavor.'],
            ['name' => 'Liver', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid liver and organ meats.'],
            ['name' => 'Brussels Sprouts', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid Brussels sprouts due to taste or texture.'],
            ['name' => 'Raw Onions', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid raw onions while cooked may be acceptable.'],
            ['name' => 'Spicy Foods', 'type' => DietaryPreferenceType::Dislike->value, 'description' => 'Preference to avoid hot and spicy foods.'],

            // Religious/Cultural Restrictions
            ['name' => 'Halal', 'type' => DietaryPreferenceType::Restriction->value, 'description' => 'Food prepared according to Islamic dietary laws, excluding pork and alcohol.'],
            ['name' => 'Kosher', 'type' => DietaryPreferenceType::Restriction->value, 'description' => 'Food prepared according to Jewish dietary laws, including separation of meat and dairy.'],
        ];

        foreach ($preferences as $preference) {
            DietaryPreference::query()->updateOrCreate(
                ['name' => $preference['name']],
                $preference
            );
        }
    }
}
