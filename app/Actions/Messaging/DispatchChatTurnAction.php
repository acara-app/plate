<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Contracts\ProcessesAdvisorMessage;
use App\Models\UserChatPlatformLink;
use LogicException;

final readonly class DispatchChatTurnAction
{
    public function __construct(
        private ProcessesAdvisorMessage $advisor,
    ) {}

    /**
     * @return array{response: string, conversation_id: string, plate_user_id: int}
     */
    public function handle(UserChatPlatformLink $link, string $message): array
    {
        $user = $link->user;

        throw_if($user === null, LogicException::class, 'Cannot dispatch advisor for an unlinked platform user.');

        $result = $this->advisor->handle(
            user: $user,
            message: $message,
            conversationId: $link->conversation_id,
        );

        if ($link->conversation_id !== $result['conversation_id']) {
            $link->update(['conversation_id' => $result['conversation_id']]);
        }

        return [
            'response' => $result['response'],
            'conversation_id' => $result['conversation_id'],
            'plate_user_id' => $user->id,
        ];
    }
}
