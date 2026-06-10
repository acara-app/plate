<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\User;

final readonly class GetOrCreateConversationAction
{
    public function handle(string $conversationId, User $user, bool $withMessages = true): Conversation
    {
        $conversation = Conversation::query()
            ->when($withMessages, fn ($query) => $query->with('messages'))
            ->find($conversationId);

        if ($conversation instanceof Conversation) {
            return $conversation;
        }

        $conversation = Conversation::query()->create([
            'id' => $conversationId,
            'user_id' => $user->id,
            'title' => Conversation::DEFAULT_TITLE,
        ]);

        return $withMessages ? $conversation->load('messages') : $conversation;
    }
}
