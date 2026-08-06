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
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Responses\AgentResponse;

final readonly class ProcessAdvisorMessageAction implements ProcessesAdvisorMessage
{
    public function __construct(
        private AgentRunner $agentRunner,
        private ConversationStore $conversationStore,
    ) {}

    /**
     * @param  array<int, Base64Image>  $attachments
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    public function handle(User $user, string $message, ?string $conversationId = null, array $attachments = []): array
    {
        Auth::login($user);

        $conversationId ??= $this->conversationStore->latestConversationId($user->getMorphClass(), $user->id)
            ?? $this->newConversationFor($user);

        Context::add('chat.conversation_id', $conversationId);

        $conversation = Conversation::query()->find($conversationId);
        $isFirstTurn = $conversation?->messages()->doesntExist() ?? false;

        $response = $this->agentRunner->runSync(
            $this->request($message, $conversationId, $attachments),
            $user,
        );

        if ($conversation instanceof Conversation && $isFirstTurn) {
            dispatch(new GenerateConversationTitleJob($conversation));
        }

        return $this->present($response, $conversationId);
    }

    /**
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    public function resume(User $user, string $conversationId, Decisions $decisions): array
    {
        Auth::login($user);

        Context::add('chat.conversation_id', $conversationId);

        $response = $this->agentRunner->resumeSync(
            $this->request('', $conversationId),
            $user,
            $decisions,
        );

        return $this->present($response, $conversationId);
    }

    public function resetConversation(User $user): string
    {
        return $this->newConversationFor($user);
    }

    /**
     * @param  array<int, Base64Image>  $attachments
     */
    private function request(string $message, string $conversationId, array $attachments = []): AgentRequest
    {
        return new AgentRequest(
            message: $message,
            images: $attachments,
            modelName: ModelName::GPT_5_4_MINI,
            conversationId: $conversationId,
        );
    }

    /**
     * @return array{response: string, conversation_id: string, pending_approvals: list<PendingApproval>}
     */
    private function present(AgentResponse $response, string $conversationId): array
    {
        return [
            'response' => $response->text,
            'conversation_id' => $conversationId,
            'pending_approvals' => array_values($response->pendingApprovals->all()),
        ];
    }

    private function newConversationFor(User $user): string
    {
        return $this->conversationStore->storeConversation(
            $user->getMorphClass(),
            $user->id,
            Conversation::DEFAULT_TITLE,
        );
    }
}
