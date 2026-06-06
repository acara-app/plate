<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

final readonly class DeleteConversationHistory
{
    public function handle(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation): void {
            DB::table('conversation_summaries')
                ->where('conversation_id', $conversation->id)
                ->delete();

            DB::table('agent_approvals')
                ->where('conversation_id', $conversation->id)
                ->delete();

            DB::table('user_chat_platform_links')
                ->where('conversation_id', $conversation->id)
                ->update(['conversation_id' => null]);

            DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversation->id)
                ->delete();

            $conversation->delete();
        });
    }
}
