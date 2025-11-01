<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Jobs\ProcessMealPlanJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobTracking>
 */
final class JobTrackingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'job_type' => ProcessMealPlanJob::JOB_TYPE,
            'status' => fake()->randomElement(JobStatus::cases()),
            'progress' => fake()->numberBetween(0, 100),
            'message' => fake()->optional(0.7)->sentence(),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the job is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobStatus::Pending,
            'progress' => 0,
            'message' => 'Job pending...',
        ]);
    }

    /**
     * Indicate that the job is processing.
     */
    public function processing(int $progress = 50): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobStatus::Processing,
            'progress' => $progress,
            'message' => 'Job processing...',
            'started_at' => now(),
        ]);
    }

    /**
     * Indicate that the job is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobStatus::Completed,
            'progress' => 100,
            'message' => 'Job completed successfully!',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the job has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JobStatus::Failed,
            'message' => 'Job failed: '.fake()->sentence(),
            'started_at' => now()->subMinutes(2),
            'failed_at' => now(),
        ]);
    }
}
