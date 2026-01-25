<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GlucoseUnit;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\UserProfile
 *
 * @extends Factory<TModel>
 */
final class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var Sex $sex */
        $sex = fake()->randomElement(Sex::cases());
        /** @var GoalChoice $goal */
        $goal = fake()->randomElement(GoalChoice::cases());
        /** @var GlucoseUnit $unit */
        $unit = fake()->randomElement(GlucoseUnit::cases());

        return [
            'user_id' => User::factory(),
            'age' => fake()->numberBetween(18, 80),
            'height' => fake()->numberBetween(150, 200),
            'weight' => fake()->numberBetween(50, 120),
            'sex' => $sex->value,
            'goal_choice' => $goal->value,
            'units_preference' => $unit->value,
        ];
    }
}
