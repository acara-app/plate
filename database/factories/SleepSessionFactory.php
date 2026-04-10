<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SleepSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SleepSession>
 */
final class SleepSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-7 days', 'now');
        $endedAt = (clone $startedAt)->modify('+'.fake()->numberBetween(30, 120).' minutes');

        return [
            'user_id' => User::factory(),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'stage' => fake()->randomElement([
                SleepSession::STAGE_ASLEEP_CORE,
                SleepSession::STAGE_ASLEEP_DEEP,
                SleepSession::STAGE_ASLEEP_REM,
                SleepSession::STAGE_AWAKE,
                SleepSession::STAGE_IN_BED,
            ]),
            'source' => 'Apple Watch',
        ];
    }
}
