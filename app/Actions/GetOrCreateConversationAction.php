<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\User;

final readonly class GetOrCreateConversationAction
{
    public function handle(string $conversationId, User $user): Conversation
    {
        return Conversation::query()
            ->with('messages')
            ->find($conversationId)
            ?? Conversation::query()->create([
                'id' => $conversationId,
                'user_id' => $user->id,
                'title' => 'New Chat',
            ])->load('messages');
    }
}
