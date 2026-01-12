<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HealthCondition;
use Illuminate\Database\Seeder;

final class HealthConditionSeeder extends Seeder
{
    public function run(): void
    {
        $conditions = [
            [
                'name' => 'Type 1 Diabetes',
                'description' => 'Autoimmune condition where the pancreas produces little or no insulin.',
                'nutritional_impact' => 'Requires precise carbohydrate counting and insulin matching. Focus on consistent carbohydrate intake, fiber-rich foods, and blood sugar monitoring throughout the day.',
                'recommended_nutrients' => ['Fiber', 'Chromium', 'Magnesium', 'Vitamin D', 'Omega-3 Fatty Acids'],
                'nutrients_to_limit' => ['Simple Carbohydrates', 'Added Sugars', 'Saturated Fat', 'Trans Fat'],
            ],
            [
                'name' => 'Type 2 Diabetes',
                'description' => 'Chronic condition affecting how the body processes blood sugar (glucose).',
                'nutritional_impact' => 'Requires careful carbohydrate management and blood sugar monitoring. Focus on complex carbohydrates, fiber, and portion control to maintain stable glucose levels.',
                'recommended_nutrients' => ['Fiber', 'Chromium', 'Magnesium', 'Vitamin D', 'Omega-3 Fatty Acids'],
                'nutrients_to_limit' => ['Simple Carbohydrates', 'Added Sugars', 'Saturated Fat', 'Trans Fat', 'Sodium'],
            ],
            [
                'name' => 'Pre-diabetes',
                'description' => 'Blood sugar levels higher than normal but not yet high enough to be diagnosed as Type 2 diabetes.',
                'nutritional_impact' => 'Focus on weight management and blood sugar control through diet modifications. Emphasize whole grains, fiber, and regular meal timing to prevent progression to Type 2 diabetes.',
                'recommended_nutrients' => ['Fiber', 'Magnesium', 'Chromium', 'Whole Grains', 'Lean Protein'],
                'nutrients_to_limit' => ['Added Sugars', 'Refined Carbohydrates', 'Saturated Fat', 'Processed Foods'],
            ],
            [
                'name' => 'Hypertension (High Blood Pressure)',
                'description' => 'Condition where blood pressure against artery walls is consistently too high.',
                'nutritional_impact' => 'Requires sodium restriction and emphasis on potassium-rich foods. DASH diet approach recommended with plenty of fruits, vegetables, and low-fat dairy.',
                'recommended_nutrients' => ['Potassium', 'Magnesium', 'Calcium', 'Fiber', 'Omega-3 Fatty Acids'],
                'nutrients_to_limit' => ['Sodium', 'Saturated Fat', 'Alcohol'],
            ],
            [
                'name' => 'Heart Disease',
                'description' => 'Cardiovascular conditions affecting heart function and blood vessels.',
                'nutritional_impact' => 'Focus on heart-healthy fats, lean proteins, and plant-based foods. Reduce saturated and trans fats while increasing omega-3 fatty acids.',
                'recommended_nutrients' => ['Omega-3 Fatty Acids', 'Fiber', 'Antioxidants', 'Potassium', 'Magnesium'],
                'nutrients_to_limit' => ['Saturated Fat', 'Trans Fat', 'Cholesterol', 'Sodium', 'Added Sugars'],
            ],
            [
                'name' => 'High Cholesterol (Hyperlipidemia)',
                'description' => 'Elevated levels of cholesterol and/or triglycerides in the blood.',
                'nutritional_impact' => 'Focus on reducing saturated fat and dietary cholesterol while increasing soluble fiber. Plant sterols and omega-3 fatty acids can help improve lipid profiles.',
                'recommended_nutrients' => ['Soluble Fiber', 'Omega-3 Fatty Acids', 'Plant Sterols', 'Monounsaturated Fats', 'Whole Grains'],
                'nutrients_to_limit' => ['Saturated Fat', 'Trans Fat', 'Dietary Cholesterol', 'Refined Carbohydrates', 'Added Sugars'],
            ],
            [
                'name' => 'Celiac Disease',
                'description' => 'Autoimmune disorder where gluten triggers immune system to attack the small intestine.',
                'nutritional_impact' => 'Requires complete elimination of gluten from wheat, barley, and rye. May need supplementation due to malabsorption issues.',
                'recommended_nutrients' => ['Iron', 'Folate', 'Vitamin B12', 'Calcium', 'Vitamin D', 'Zinc', 'Fiber'],
                'nutrients_to_limit' => ['Gluten'],
            ],
            [
                'name' => 'Irritable Bowel Syndrome (IBS)',
                'description' => 'Chronic condition affecting the large intestine causing cramping, bloating, and changes in bowel habits.',
                'nutritional_impact' => 'May benefit from low-FODMAP diet. Identify and avoid trigger foods while ensuring adequate fiber and hydration.',
                'recommended_nutrients' => ['Soluble Fiber', 'Probiotics', 'Peppermint', 'Ginger'],
                'nutrients_to_limit' => ['FODMAPs', 'Insoluble Fiber (during flares)', 'Caffeine', 'Alcohol', 'Fatty Foods'],
            ],
            [
                'name' => 'PCOS (Polycystic Ovary Syndrome)',
                'description' => 'Hormonal disorder common in women of reproductive age causing irregular periods and elevated androgen levels.',
                'nutritional_impact' => 'Focus on balanced blood sugar through low glycemic index foods, adequate protein, and healthy fats. Weight management crucial for symptom control.',
                'recommended_nutrients' => ['Fiber', 'Omega-3 Fatty Acids', 'Chromium', 'Inositol', 'Vitamin D', 'Magnesium'],
                'nutrients_to_limit' => ['Refined Carbohydrates', 'Added Sugars', 'Saturated Fat', 'Trans Fat'],
            ],
            [
                'name' => 'Hypothyroidism',
                'description' => 'Underactive thyroid gland producing insufficient thyroid hormone.',
                'nutritional_impact' => 'Support thyroid function with adequate iodine and selenium. Avoid goitrogenic foods in excess. Manage weight with balanced macronutrients.',
                'recommended_nutrients' => ['Iodine', 'Selenium', 'Zinc', 'Iron', 'Vitamin D', 'B Vitamins'],
                'nutrients_to_limit' => ['Soy (in excess)', 'Raw Cruciferous Vegetables (in excess)', 'Gluten (if sensitive)'],
            ],
            [
                'name' => 'Hyperthyroidism',
                'description' => 'Overactive thyroid gland producing excessive thyroid hormone.',
                'nutritional_impact' => 'May need to limit iodine intake. Focus on calcium and vitamin D due to increased bone loss risk. Adequate calories to prevent weight loss.',
                'recommended_nutrients' => ['Calcium', 'Vitamin D', 'Selenium', 'Iron', 'Omega-3 Fatty Acids'],
                'nutrients_to_limit' => ['Iodine (in excess)', 'Caffeine', 'Added Sugars'],
            ],
            [
                'name' => 'Osteoporosis',
                'description' => 'Condition where bones become weak and brittle, increasing fracture risk.',
                'nutritional_impact' => 'Maximize bone health through adequate calcium, vitamin D, and protein. Weight-bearing exercise essential alongside nutrition.',
                'recommended_nutrients' => ['Calcium', 'Vitamin D', 'Vitamin K', 'Magnesium', 'Protein', 'Phosphorus'],
                'nutrients_to_limit' => ['Sodium', 'Caffeine (excessive)', 'Alcohol', 'Phosphoric Acid (from sodas)'],
            ],
            [
                'name' => 'Chronic Kidney Disease',
                'description' => 'Progressive loss of kidney function over time.',
                'nutritional_impact' => 'Requires careful monitoring of protein, sodium, potassium, and phosphorus intake. Fluid restrictions may be necessary in advanced stages.',
                'recommended_nutrients' => ['High-Quality Protein (limited amount)', 'Omega-3 Fatty Acids', 'B Vitamins'],
                'nutrients_to_limit' => ['Sodium', 'Potassium', 'Phosphorus', 'Protein (excessive)', 'Saturated Fat'],
            ],
            [
                'name' => 'Gout',
                'description' => 'Form of arthritis caused by excess uric acid in the bloodstream.',
                'nutritional_impact' => 'Limit purine-rich foods to reduce uric acid production. Maintain healthy weight and stay well-hydrated.',
                'recommended_nutrients' => ['Vitamin C', 'Cherry Extract', 'Water', 'Low-Fat Dairy'],
                'nutrients_to_limit' => ['Purines', 'Alcohol (especially beer)', 'Fructose', 'Red Meat', 'Organ Meats', 'Certain Seafood'],
            ],
            [
                'name' => 'Anemia (Iron-Deficiency)',
                'description' => 'Condition where blood lacks adequate healthy red blood cells due to iron deficiency.',
                'nutritional_impact' => 'Increase iron-rich foods and pair with vitamin C for better absorption. Address underlying causes of deficiency.',
                'recommended_nutrients' => ['Iron', 'Vitamin C', 'Folate', 'Vitamin B12', 'Copper'],
                'nutrients_to_limit' => ['Calcium (with iron-rich meals)', 'Tea and Coffee (with meals)', 'Phytates (in excess)'],
            ],
            [
                'name' => 'Inflammatory Bowel Disease (IBD)',
                'description' => 'Chronic inflammation of the digestive tract, including Crohn\'s disease and ulcerative colitis.',
                'nutritional_impact' => 'Focus on anti-inflammatory foods during remission. During flares, may need low-residue diet. Address malnutrition from poor absorption.',
                'recommended_nutrients' => ['Omega-3 Fatty Acids', 'Probiotics', 'Vitamin D', 'Iron', 'Folate', 'Vitamin B12', 'Zinc'],
                'nutrients_to_limit' => ['Fiber (during flares)', 'Lactose (if intolerant)', 'FODMAPs', 'Alcohol', 'Saturated Fat'],
            ],
            [
                'name' => 'Gestational Diabetes',
                'description' => 'High blood sugar that develops during pregnancy.',
                'nutritional_impact' => 'Requires carbohydrate counting and blood sugar monitoring. Focus on balanced meals with complex carbs, protein, and healthy fats.',
                'recommended_nutrients' => ['Complex Carbohydrates', 'Fiber', 'Protein', 'Folate', 'Iron', 'Calcium'],
                'nutrients_to_limit' => ['Simple Carbohydrates', 'Added Sugars', 'Large Portions of Carbs at Once'],
            ],
            [
                'name' => 'Fatty Liver Disease (NAFLD)',
                'description' => 'Accumulation of excess fat in liver cells not caused by alcohol.',
                'nutritional_impact' => 'Focus on weight loss if overweight, Mediterranean diet patterns, and reducing refined carbohydrates. Limit added sugars especially fructose.',
                'recommended_nutrients' => ['Omega-3 Fatty Acids', 'Fiber', 'Antioxidants', 'Vitamin E', 'Whole Grains'],
                'nutrients_to_limit' => ['Added Sugars', 'Fructose', 'Refined Carbohydrates', 'Saturated Fat', 'Alcohol'],
            ],
            [
                'name' => 'Acid Reflux (GERD)',
                'description' => 'Chronic digestive disease where stomach acid flows back into the esophagus.',
                'nutritional_impact' => 'Avoid trigger foods and eating patterns that increase acid production or relax the lower esophageal sphincter. Smaller, more frequent meals and avoiding late-night eating can help.',
                'recommended_nutrients' => ['Fiber', 'Lean Proteins', 'Non-Citrus Fruits', 'Vegetables', 'Whole Grains'],
                'nutrients_to_limit' => ['Acidic Foods', 'Caffeine', 'Alcohol', 'Chocolate', 'Fatty Foods', 'Spicy Foods', 'Citrus', 'Tomatoes'],
            ],
        ];

        foreach ($conditions as $condition) {
            HealthCondition::query()->updateOrCreate(
                ['name' => $condition['name']],
                $condition
            );
        }
    }
}
