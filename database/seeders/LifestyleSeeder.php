<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Lifestyle;
use Illuminate\Database\Seeder;

final class LifestyleSeeder extends Seeder
{
    public function run(): void
    {
        $lifestyles = [
            [
                'name' => 'Sedentary',
                'activity_level' => 'Sedentary',
                'sleep_hours' => '7-8 hours',
                'occupation' => 'Desk Job',
                'description' => 'Little or no exercise, desk job with minimal physical activity throughout the day.',
                'activity_multiplier' => 1.2,
            ],
            [
                'name' => 'Lightly Active',
                'activity_level' => 'Lightly Active',
                'sleep_hours' => '7-8 hours',
                'occupation' => 'Mixed Activity',
                'description' => 'Light exercise or sports 1-3 days per week, some walking or light physical activity during the day.',
                'activity_multiplier' => 1.375,
            ],
            [
                'name' => 'Moderately Active',
                'activity_level' => 'Moderately Active',
                'sleep_hours' => '7-8 hours',
                'occupation' => 'Retail',
                'description' => 'Moderate exercise or sports 3-5 days per week, regular physical activity as part of daily routine.',
                'activity_multiplier' => 1.55,
            ],
            [
                'name' => 'Very Active',
                'activity_level' => 'Very Active',
                'sleep_hours' => '7-9 hours',
                'occupation' => 'Physical Labor',
                'description' => 'Hard exercise or sports 6-7 days per week, physically demanding job or intensive training regimen.',
                'activity_multiplier' => 1.725,
            ],
            [
                'name' => 'Extremely Active',
                'activity_level' => 'Extremely Active',
                'sleep_hours' => '8-9 hours',
                'occupation' => 'Athlete',
                'description' => 'Very hard exercise or sports, physical job, or training twice per day. Professional athletes or those with very demanding physical requirements.',
                'activity_multiplier' => 1.9,
            ],
        ];

        foreach ($lifestyles as $lifestyle) {
            Lifestyle::create($lifestyle);
        }
    }
}
