<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Contracts\ProcessesAdvisorMessage;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Files\Base64Image;

final readonly class ProcessAdvisorMessageAction implements ProcessesAdvisorMessage
{
    public function __construct(
        private AgentRunner $agentRunner,
        private ConversationStore $conversationStore,
    ) {}

    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array{response: string, conversation_id: string}
     */
    public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
    {
        Auth::login($user);

        $conversationId ??= $this->conversationStore->latestConversationId($user->id)
            ?? $this->conversationStore->storeConversation($user->id, 'Telegram Chat');

        $payload = new AgentPayload(
            userId: $user->id,
            message: $message,
            images: $attachments,
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_MINI,
        );

        $response = $this->agentRunner->runSync($payload, $user, $conversationId);

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
