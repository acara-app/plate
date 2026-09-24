<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\History;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\MessageRole;

final readonly class AbandonPendingApprovals
{
    public function handle(string $conversationId): void
    {
        History::query()
            ->where('conversation_id', $conversationId)
            ->where('role', MessageRole::Assistant->value)
            ->where('status', MessageStatus::Paused)
            ->get()
            ->each(fn (History $message) => $message->forceFill([
                'status' => MessageStatus::Completed,
                'steps' => array_map(
                    fn (array $step): array => [...$step, 'replay_blocks' => []],
                    $message->steps ?? [],
                ),
            ])->save());
    }
}
