<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Goal;
use Illuminate\Database\Seeder;

final class GoalSeeder extends Seeder
{
    public function run(): void
    {
        Goal::query()->insert([
            ['name' => 'Lose Weight'],
            ['name' => 'Muscle Gain'],
            ['name' => 'Maintain Weight'],
            ['name' => 'Health Condition'],
            ['name' => 'Improve Endurance'],
            ['name' => 'Enhance Flexibility'],
        ]);
    }
}
