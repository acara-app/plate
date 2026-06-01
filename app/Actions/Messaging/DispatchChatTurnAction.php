<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Contracts\ProcessesAdvisorMessage;
use App\Models\AgentApproval;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Files\Base64Image;
use LogicException;

final readonly class DispatchChatTurnAction
{
    public function __construct(
        private ProcessesAdvisorMessage $advisor,
    ) {}

    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array{response: string, conversation_id: string, pending_approvals: array<int, AgentApproval>}
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

        return [
            ...$result,
            'pending_approvals' => $this->collectPendingApprovals($user),
        ];
    }

    /**
     * @return array<int, AgentApproval>
     */
    private function collectPendingApprovals(User $user): array
    {
        $ids = Context::get('chat.created_approvals', []);

        if (! is_array($ids) || $ids === []) {
            return [];
        }

        return AgentApproval::query()
            ->whereIn('id', $ids)
            ->where('user_id', $user->id)
            ->pending()
            ->get()
            ->all();
    }
}
