<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\History;
use Laravel\Ai\Messages\MessageRole;

final readonly class AbandonPendingApprovals
{
    public function handle(string $conversationId): void
    {
        History::query()
            ->where('conversation_id', $conversationId)
            ->where('role', MessageRole::Assistant->value)
            ->whereNotNull('approval_state')
            ->get()
            ->filter(fn (History $message): bool => $message->hasPendingApprovals())
            ->each(fn (History $message) => $message->forceFill([
                'approval_state' => ['pending' => []],
            ])->save());
    }
}
