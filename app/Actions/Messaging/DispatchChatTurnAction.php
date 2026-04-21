<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Contracts\ProcessesAdvisorMessage;
use App\Models\UserChatPlatformLink;
use Laravel\Ai\Files\Base64Image;
use LogicException;

final readonly class DispatchChatTurnAction
{
    public function __construct(
        private ProcessesAdvisorMessage $advisor,
    ) {}

    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array{response: string, conversation_id: string}
     */
    public function handle(UserChatPlatformLink $link, string $message, array $attachments = []): array
    {
        $user = $link->user;

        throw_if($user === null, LogicException::class, 'Cannot dispatch a chat turn for an unlinked platform user.');

        $result = $this->advisor->handle(
            user: $user,
            message: $message,
            conversationId: $link->conversation_id,
            attachments: $attachments,
        );

        if ($link->conversation_id !== $result['conversation_id']) {
            $link->update(['conversation_id' => $result['conversation_id']]);
        }

        return $result;
    }
}
