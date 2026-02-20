<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthCondition;
use App\Models\UserProfile;
use App\Models\UserProfileHealthCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfileHealthCondition>
 */
final class UserProfileHealthConditionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_profile_id' => UserProfile::factory(),
            'health_condition_id' => HealthCondition::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
