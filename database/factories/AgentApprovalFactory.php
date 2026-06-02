<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgentApprovalStatus;
use App\Enums\HealthEntryType;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentApproval>
 */
final class AgentApprovalFactory extends Factory
{
    protected $model = AgentApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'conversation_id' => null,
            'tool_name' => 'log_health_entry',
            'channel' => null,
            'status' => AgentApprovalStatus::Pending,
            'payload' => [
                'log_type' => HealthEntryType::Glucose->value,
                'glucose_value' => 140,
                'glucose_reading_type' => 'fasting',
                'measured_at' => now()->toIso8601String(),
            ],
            'summary' => 'Glucose 140 mg/dL (fasting)',
            'result' => null,
            'error' => null,
            'expires_at' => now()->addHours(24),
            'resolved_at' => null,
            'executed_at' => null,
        ];
    }

    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn (array $attributes): array => [
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
        ]);
    }

    public function telegram(): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => 'telegram',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AgentApprovalStatus::Approved,
            'resolved_at' => now(),
        ]);
    }

    public function executed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AgentApprovalStatus::Executed,
            'resolved_at' => now(),
            'executed_at' => now(),
            'result' => ['entry_id' => fake()->numberBetween(1, 1000)],
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AgentApprovalStatus::Rejected,
            'resolved_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AgentApprovalStatus::Expired,
            'resolved_at' => now(),
        ]);
    }

    public function stale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AgentApprovalStatus::Pending,
            'expires_at' => now()->subHour(),
        ]);
    }
}
