<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Ai\Agents\AgentRunner;
use App\Models\AiUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiUsage>
 */
final class AiUsageFactory extends Factory
{
    protected $model = AiUsage::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'agent' => AgentRunner::class,
            'model' => 'gemini-3-flash-preview',
            'provider' => 'gemini',
            'prompt_tokens' => $this->faker->numberBetween(100, 10000),
            'completion_tokens' => $this->faker->numberBetween(50, 5000),
            'cache_read_input_tokens' => 0,
            'reasoning_tokens' => 0,
            'cost' => $this->faker->randomFloat(6, 0.001, 1.0),
        ];
    }
}
