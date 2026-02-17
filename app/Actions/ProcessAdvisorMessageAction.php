<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Ai\Advisor;
use App\Contracts\ProcessesAdvisorMessage;
use App\Models\User;
use Laravel\Ai\Contracts\ConversationStore;

final readonly class ProcessAdvisorMessageAction implements ProcessesAdvisorMessage
{
    public function __construct(
        private Advisor $advisor,
        private ConversationStore $conversationStore,
    ) {}

    /**
     * @return array{response: string, conversation_id: string}
     */
    public function handle(User $user, string $message, ?string $conversationId = null): array
    {
        $conversationId ??= $this->conversationStore->latestConversationId($user->id)
            ?? $this->conversationStore->storeConversation($user->id, 'Telegram Chat');

        $agent = $this->advisor->continue($conversationId, $user);
        $response = $agent->prompt($message);

        return [
            'response' => $response->text,
            'conversation_id' => $conversationId,
        ];
    }

    public function resetConversation(User $user): string
    {
        return $this->conversationStore->storeConversation($user->id, 'Telegram Chat');
    }
}
