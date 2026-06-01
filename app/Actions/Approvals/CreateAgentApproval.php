<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\User;
use App\Utilities\ConfigHelper;

final readonly class CreateAgentApproval
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $toolName, array $payload, string $summary, ?Conversation $conversation, User $user, ?string $channel = null): AgentApproval
    {
        $ttlHours = ConfigHelper::int(
            'plate.approvals.ttl_hours.'.$toolName,
            ConfigHelper::int('plate.approvals.ttl_hours.default', 24),
        );

        return AgentApproval::query()->create([
            'user_id' => $user->id,
            'conversation_id' => $conversation?->id,
            'tool_name' => $toolName,
            'channel' => $channel,
            'status' => AgentApprovalStatus::Pending,
            'payload' => $payload,
            'summary' => $summary,
            'expires_at' => now()->addHours($ttlHours),
        ]);
    }
}
