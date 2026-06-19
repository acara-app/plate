<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AgentApproval;
use App\Models\Conversation;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\DB;

/** @codeCoverageIgnore */
final readonly class DeleteConversationHistory
{
    public function handle(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation): void {
            $conversation->summaries()->delete();

            AgentApproval::query()
                ->where('conversation_id', $conversation->id)
                ->delete();

            UserChatPlatformLink::query()
                ->where('conversation_id', $conversation->id)
                ->update(['conversation_id' => null]);

            $conversation->messages()->delete();

            $conversation->delete();
        });
    }
}
