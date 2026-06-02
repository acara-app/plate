<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AgentApprovalStatus;
use App\Enums\ChatPlatform;
use App\Events\AgentApprovalResolved;
use App\Models\AgentApproval;
use App\Models\UserChatPlatformLink;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyTelegramOfApprovalOutcome implements ShouldQueue
{
    public function handle(AgentApprovalResolved $event): void
    {
        $approval = AgentApproval::query()->find($event->approvalId);

        if (! $approval instanceof AgentApproval || $approval->channel !== ChatPlatform::Telegram->value) {
            return;
        }

        $message = $this->outcomeMessage($approval);

        if ($message === null) {
            return;
        }

        $link = UserChatPlatformLink::query()
            ->where('user_id', $approval->user_id)
            ->where('platform', ChatPlatform::Telegram)
            ->linked()
            ->first();

        if (! $link instanceof UserChatPlatformLink || $link->platform_user_id === null) {
            return;
        }

        $chat = TelegraphChat::query()->where('chat_id', $link->platform_user_id)->first();

        if (! $chat instanceof TelegraphChat) {
            return;
        }

        $chat->message($message)->dispatch();
    }

    private function outcomeMessage(AgentApproval $approval): ?string
    {
        return match ($approval->status) {
            AgentApprovalStatus::Executed => '✅ Saved: '.$approval->summary,
            AgentApprovalStatus::Failed => '⚠️ I couldn’t save that entry. Please try again.',
            default => null,
        };
    }
}
