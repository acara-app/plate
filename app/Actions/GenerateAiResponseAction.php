<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\NutritionAdvisor;
use App\Contracts\GeneratesAiResponse;
use App\Models\User;
use Laravel\Ai\Contracts\ConversationStore;

final readonly class GenerateAiResponseAction implements GeneratesAiResponse
{
    public function __construct(
        private NutritionAdvisor $nutritionAdvisor,
        private ConversationStore $conversationStore,
    ) {}

    /**
     * @return array{response: string, conversation_id: string}
     */
    public function handle(User $user, string $message, ?string $conversationId = null): array
    {
        $conversationId ??= $this->conversationStore->latestConversationId($user->id)
            ?? $this->conversationStore->storeConversation($user->id, 'Telegram Chat');

        $agent = $this->nutritionAdvisor->continue($conversationId, $user);
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
