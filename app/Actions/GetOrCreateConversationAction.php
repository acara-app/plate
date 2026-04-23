<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\User;

final readonly class GetOrCreateConversationAction
{
    public function handle(string $conversationId, User $user): Conversation
    {
        $conversation = Conversation::query()
            ->with('messages')
            ->find($conversationId);

        if ($conversation instanceof Conversation) {
            return $conversation;
        }

        return Conversation::query()->create([
            'id' => $conversationId,
            'user_id' => $user->id,
            'title' => 'New Chat',
        ])->load('messages');
    }
}
