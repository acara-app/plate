<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\NutritionAdvisor;
use App\Models\User;
use Laravel\Ai\Contracts\ConversationStore;

final readonly class GenerateAiResponseAction
{
    public function __construct(
        private NutritionAdvisor $nutritionAdvisor,
        private ConversationStore $conversationStore,
    ) {}

    public function handle(User $user, string $message, ?string $conversationId = null): array
    {
        if ($conversationId === null) {
            $conversationId = $this->conversationStore->latestConversationId($user->id);

            if ($conversationId === null) {
                $conversationId = $this->conversationStore->storeConversation($user->id, 'Telegram Chat');
            }
        }

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
