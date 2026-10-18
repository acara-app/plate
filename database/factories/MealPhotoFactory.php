<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MealPhoto>
 */
final class MealPhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'analysis_draft_id' => null,
            'health_group_id' => null,
            'disk' => MealPhoto::DISK,
            'path' => MealPhoto::DIRECTORY.'/'.Str::uuid().'.jpg',
        ];
    }
}
