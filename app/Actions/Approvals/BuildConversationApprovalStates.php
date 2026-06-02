<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Models\AgentApproval;
use App\Models\Conversation;

final readonly class BuildConversationApprovalStates
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function handle(Conversation $conversation): array
    {
        $approvals = AgentApproval::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $conversation->user_id)
            ->get();

        $states = [];

        foreach ($approvals as $approval) {
            /** @var array<string, mixed> $card */
            $card = $approval->toCardData()->toArray();
            $states[$approval->id] = $card;
        }

        return $states;
    }
}
