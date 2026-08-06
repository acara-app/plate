<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Contracts\ProcessesAdvisorMessage;
use App\Models\Conversation;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Files\Base64Image;
use LogicException;

final readonly class DispatchChatTurnAction
{
    public function __construct(
        private ProcessesAdvisorMessage $advisor,
    ) {}

    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    public function handle(UserChatPlatformLink $link, string $message, array $attachments = []): array
    {
        $user = $link->user;

        throw_if($user === null, LogicException::class, 'Cannot dispatch a chat turn for an unlinked platform user.');

        Context::add('chat.channel', $link->platform->value);

        $result = $this->advisor->handle(
            user: $user,
            message: $message,
            conversationId: $link->conversation_id,
            attachments: $attachments,
        );

        if ($link->conversation_id !== $result['conversation_id']) {
            $link->update(['conversation_id' => $result['conversation_id']]);
        }

        Conversation::query()
            ->whereKey($result['conversation_id'])
            ->whereNull('kept_at')
            ->update(['kept_at' => now()]);

        return $result;
    }

    /**
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    public function resume(UserChatPlatformLink $link, Decisions $decisions): array
    {
        $user = $link->user;

        throw_if($user === null, LogicException::class, 'Cannot resume a chat turn for an unlinked platform user.');
        throw_if($link->conversation_id === null, LogicException::class, 'Cannot resume a chat turn without a conversation.');

        Context::add('chat.channel', $link->platform->value);

        return $this->advisor->resume($user, $link->conversation_id, $decisions);
    }
}
