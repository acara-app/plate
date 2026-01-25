<?php

declare(strict_types=1);

namespace App\Services\FoodDataProviders;

use App\DataObjects\MediterraneanDietFoodData;

final class MediterraneanDietFoods
{
    /**
     * @return array<int, MediterraneanDietFoodData>
     */
    public static function all(): array
    {
        return [
            new MediterraneanDietFoodData(name: 'Artichoke, boiled, 1 medium', calories: 150, protein: 10.0, fat: 5.0, saturatedFat: 0.0, fiber: 16.0),
            new MediterraneanDietFoodData(name: 'Asparagus, boiled, 6 spears', calories: 22, protein: 2.3, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Broccoli, boiled, 1/2 cup', calories: 22, protein: 2.5, fat: 0.0, saturatedFat: 0.0, fiber: 2.5),
            new MediterraneanDietFoodData(name: 'Carrots, boiled, 1/2 cup slices', calories: 35, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 2.5),
            new MediterraneanDietFoodData(name: 'Cauliflower, boiled, 1/2 cup pieces', calories: 14, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Eggplant, boiled, 1/2 cup', calories: 13, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 1.0),
            new MediterraneanDietFoodData(name: 'Endive, raw, 1/2 cup chopped', calories: 4, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 1.0),
            new MediterraneanDietFoodData(name: 'Green beans, boiled, 1/2 cup', calories: 22, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 2.0),
            new MediterraneanDietFoodData(name: 'Romaine lettuce, raw, 1/2 cup shredded', calories: 4, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 0.5),
            new MediterraneanDietFoodData(name: 'Mushrooms, boiled, 1/2 cup pieces', calories: 21, protein: 2.0, fat: 0.0, saturatedFat: 0.0, fiber: 2.0),
            new MediterraneanDietFoodData(name: 'Onions, raw, 1/2 cup', calories: 16, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Peppers, sweet, raw, 1/2 cup chopped', calories: 14, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 1.0),
            new MediterraneanDietFoodData(name: 'Radicchio, raw, 1/2 cup, shredded', calories: 5, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Spinach, raw, 1/2 cup, chopped', calories: 6, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.0),
            new MediterraneanDietFoodData(name: 'Zucchini, boiled, 1/2 cup slices', calories: 14, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Squash, summer, crookneck, boiled, 1/2 cup', calories: 18, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Tomato, red, raw, 1 medium', calories: 26, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Tomato, red, sun-dried, 1/2 cup', calories: 70, protein: 3.5, fat: 0.0, saturatedFat: 0.0, fiber: 3.5),
            new MediterraneanDietFoodData(name: 'Broad beans, 1 cup, boiled', calories: 187, protein: 13.0, fat: 0.0, saturatedFat: 0.0, fiber: 9.0),
            new MediterraneanDietFoodData(name: 'Chickpeas (garbanzo beans), boiled, 1 cup', calories: 270, protein: 14.5, fat: 0.0, saturatedFat: 0.0, fiber: 12.5),
            new MediterraneanDietFoodData(name: 'Hummus, 1/2 cup', calories: 210, protein: 6.0, fat: 10.0, saturatedFat: 1.6, fiber: 6.0),
            new MediterraneanDietFoodData(name: 'Northern beans, boiled, 1/2 cup', calories: 105, protein: 7.0, fat: 0.0, saturatedFat: 0.0, fiber: 6.0),
            new MediterraneanDietFoodData(name: 'Kidney beans, red, boiled, 1/2 cup', calories: 110, protein: 7.5, fat: 0.0, saturatedFat: 0.0, fiber: 7.5),
            new MediterraneanDietFoodData(name: 'Lentils, boiled, 1/2 cup', calories: 115, protein: 9.0, fat: 0.0, saturatedFat: 0.0, fiber: 7.5),
            new MediterraneanDietFoodData(name: 'Lima beans, boiled, 1/2 cup', calories: 108, protein: 7.0, fat: 0.0, saturatedFat: 0.0, fiber: 6.5),
            new MediterraneanDietFoodData(name: 'Navy beans, boiled, 1/2 cup', calories: 228, protein: 8.0, fat: 0.0, saturatedFat: 0.0, fiber: 5.5),
            new MediterraneanDietFoodData(name: 'Peas, green, boiled, frozen, 1/2 cup', calories: 60, protein: 5.0, fat: 0.0, saturatedFat: 0.0, fiber: 2.0),
            new MediterraneanDietFoodData(name: 'Peas, split, boiled, 1/2 cup', calories: 215, protein: 8.0, fat: 0.0, saturatedFat: 0.0, fiber: 8.0),
            new MediterraneanDietFoodData(name: 'White beans, boiled, 1/2 cup', calories: 125, protein: 8.5, fat: 0.0, saturatedFat: 0.0, fiber: 5.5),
            new MediterraneanDietFoodData(name: 'Apple, raw with skin, 1 medium', calories: 80, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 2.5),
            new MediterraneanDietFoodData(name: 'Apricots, raw, 3 medium', calories: 50, protein: 1.5, fat: 0.0, saturatedFat: 0.0, fiber: 2.5),
            new MediterraneanDietFoodData(name: 'Cherries, sweet, 10 raw', calories: 34, protein: 1.0, fat: 0.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Dates, 4 dried', calories: 100, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 3.0),
            new MediterraneanDietFoodData(name: 'Figs, 2 dried', calories: 125, protein: 2.0, fat: 0.0, saturatedFat: 0.0, fiber: 4.0),
            new MediterraneanDietFoodData(name: 'Orange, navel, raw, 1', calories: 60, protein: 1.5, fat: 0.0, saturatedFat: 0.0, fiber: 3.0),
            new MediterraneanDietFoodData(name: 'Pear, raw, 1 medium', calories: 100, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 4.5),
            new MediterraneanDietFoodData(name: 'Plums, raw, 1 medium', calories: 36, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 1.0),
            new MediterraneanDietFoodData(name: 'Raspberries, raw, 1/2 cup', calories: 30, protein: 0.5, fat: 0.0, saturatedFat: 0.0, fiber: 4.5),
            new MediterraneanDietFoodData(name: 'Whole grain bread, 1 slice', calories: 70, protein: 3.0, fat: 1.0, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Whole wheat pita, 1/2 large', calories: 100, protein: 4.5, fat: 0.5, saturatedFat: 0.0, fiber: 2.5),
            new MediterraneanDietFoodData(name: 'Brown rice, long grain, cooked, 1/2 cup', calories: 108, protein: 2.5, fat: 1.4, saturatedFat: 0.0, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Spaghetti, whole wheat, cooked, 1 cup', calories: 175, protein: 7.5, fat: 1.0, saturatedFat: 0.0, fiber: 6.5),
            new MediterraneanDietFoodData(name: 'Macaroni, whole wheat, cooked, 1 cup', calories: 175, protein: 7.5, fat: 1.0, saturatedFat: 0.0, fiber: 4.0),
            new MediterraneanDietFoodData(name: 'Almonds, dried, 1/2 oz (12 nuts)', calories: 82, protein: 3.0, fat: 7.5, saturatedFat: 0.5, fiber: 1.5),
            new MediterraneanDietFoodData(name: 'Cashews, dry roasted, 1 oz (9 nuts)', calories: 82, protein: 2.0, fat: 6.5, saturatedFat: 1.0, fiber: 0.5),
            new MediterraneanDietFoodData(name: 'Chestnuts, European, raw (2 1/2 nuts)', calories: 60, protein: 0.5, fat: 0.5, saturatedFat: 0.0, fiber: 2.5),
            new MediterraneanDietFoodData(name: 'Pistachios, dried, 1/4 oz (12 nuts)', calories: 40, protein: 1.5, fat: 3.5, saturatedFat: 0.0, fiber: 0.5),
            new MediterraneanDietFoodData(name: 'Pumpkin seeds, 1/3 cup', calories: 110, protein: 5.0, fat: 5.0, saturatedFat: 1.0, fiber: 2.0),
            new MediterraneanDietFoodData(name: 'Sesame seeds, whole, dried, 1 tablespoon', calories: 52, protein: 1.6, fat: 4.5, saturatedFat: 0.5, fiber: 1.0),
            new MediterraneanDietFoodData(name: 'Mozzarella, part skim, low moisture, 1 oz', calories: 80, protein: 8.0, fat: 5.0, saturatedFat: 3.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Ricotta, part skim, 1/4 cup', calories: 135, protein: 7.0, fat: 10.0, saturatedFat: 6.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Romano, 1 oz', calories: 110, protein: 9.0, fat: 7.5, saturatedFat: 5.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Provolone, 1 oz', calories: 100, protein: 7.0, fat: 7.5, saturatedFat: 5.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Feta, 1 oz', calories: 75, protein: 4.0, fat: 6.0, saturatedFat: 4.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Cottage cheese, 1% fat, 1/2 cup', calories: 82, protein: 14.0, fat: 2.5, saturatedFat: 1.5, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Yogurt, plain, 1.5% milk fat, 4 oz', calories: 115, protein: 5.0, fat: 1.5, saturatedFat: 1.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Milk, 1% fat, 8 fl oz', calories: 102, protein: 8.0, fat: 2.5, saturatedFat: 1.5, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Egg, chicken, 1 large, boiled', calories: 80, protein: 6.0, fat: 5.5, saturatedFat: 1.5, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Chicken/Turkey, light meat, w/o skin, roasted, 3.5 oz', calories: 175, protein: 30.0, fat: 4.5, saturatedFat: 1.5, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Scallops, sea, raw, 3.5 oz', calories: 60, protein: 11.0, fat: 1.0, saturatedFat: 0.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Haddock, dry heat cooked, 3 oz', calories: 95, protein: 21.0, fat: 1.0, saturatedFat: 0.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Halibut, dry heat cooked, 3 oz', calories: 119, protein: 23.0, fat: 2.5, saturatedFat: 1.0, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Snapper, dry heat cooked, 3 oz', calories: 110, protein: 23.0, fat: 1.5, saturatedFat: 0.5, fiber: 0.0),
            new MediterraneanDietFoodData(name: 'Salmon, Atlantic, wild, dry heat cooked, 3 oz', calories: 155, protein: 22.0, fat: 7.0, saturatedFat: 1.0, fiber: 0.0),
        ];
    }
}
