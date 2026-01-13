<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Goal;
use Illuminate\Database\Seeder;

final class GoalSeeder extends Seeder
{
    public function run(): void
    {
        $goals = [
            [
                'name' => 'Lose Weight',
                'description' => 'Achieve sustainable weight loss through a moderate calorie deficit while maintaining muscle mass.',
                'protein_ratio' => 0.30,
                'carb_ratio' => 0.40,
                'fat_ratio' => 0.30,
                'calorie_adjustment' => -0.20,
            ],
            [
                'name' => 'Muscle Gain',
                'description' => 'Build lean muscle mass through increased protein intake and a slight caloric surplus.',
                'protein_ratio' => 0.35,
                'carb_ratio' => 0.40,
                'fat_ratio' => 0.25,
                'calorie_adjustment' => 0.10,
            ],
            [
                'name' => 'Maintain Weight',
                'description' => 'Maintain current weight with balanced nutrition to support overall health.',
                'protein_ratio' => 0.25,
                'carb_ratio' => 0.45,
                'fat_ratio' => 0.30,
                'calorie_adjustment' => 0.00,
            ],
            [
                'name' => 'Health Condition',
                'description' => 'Support management of health conditions through targeted nutrition and medical guidance.',
                'protein_ratio' => 0.25,
                'carb_ratio' => 0.45,
                'fat_ratio' => 0.30,
                'calorie_adjustment' => 0.00,
            ],
            [
                'name' => 'Improve Endurance',
                'description' => 'Enhance cardiovascular endurance with carbohydrate-focused nutrition for sustained energy.',
                'protein_ratio' => 0.20,
                'carb_ratio' => 0.55,
                'fat_ratio' => 0.25,
                'calorie_adjustment' => 0.05,
            ],
            [
                'name' => 'Enhance Flexibility',
                'description' => 'Support joint health and recovery with anti-inflammatory nutrition and adequate protein.',
                'protein_ratio' => 0.25,
                'carb_ratio' => 0.45,
                'fat_ratio' => 0.30,
                'calorie_adjustment' => 0.00,
            ],
        ];

        foreach ($goals as $goal) {
            Goal::query()->updateOrCreate(
                ['name' => $goal['name']],
                $goal
            );
        }
    }
}
