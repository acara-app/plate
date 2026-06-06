<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Contracts\ProcessesAdvisorMessage;
use App\Enums\ModelName;
use App\Jobs\GenerateConversationTitleJob;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
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
            ?? $this->conversationStore->storeConversation($user->id, Conversation::DEFAULT_TITLE);

        Context::add('chat.conversation_id', $conversationId);

        $conversation = Conversation::query()->find($conversationId);
        $isFirstTurn = $conversation?->messages()->doesntExist() ?? false;

        $request = new AgentRequest(
            message: $message,
            images: $attachments,
            modelName: ModelName::GPT_5_4_MINI,
            conversationId: $conversationId,
        );

        $response = $this->agentRunner->runSync($request, $user);

        if ($isFirstTurn && $conversation instanceof Conversation) {
            dispatch(new GenerateConversationTitleJob($conversation));
        }

        return [
            'response' => $response->text,
            'conversation_id' => $conversationId,
        ];
    }

    public function resetConversation(User $user): string
    {
        return $this->conversationStore->storeConversation($user->id, Conversation::DEFAULT_TITLE);
    }
}
