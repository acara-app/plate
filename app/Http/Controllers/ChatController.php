<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Approvals\BuildConversationApprovalStates;
use App\Actions\Billing\BuildCreditWarning;
use App\Actions\BuildAssistantAgentAction;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\GetOrCreateConversationAction;
use App\Actions\ReplayAgentStreamAction;
use App\Http\Requests\StoreChatConversationRequest;
use App\Http\Requests\StreamChatRequest;
use App\Models\AgentStreamRun;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ChatController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private BuildConversationMessagesAction $messagesAction,
        private BuildAssistantAgentAction $agentAction,
        private GetOrCreateConversationAction $conversationAction,
        private BuildCreditWarning $buildCreditWarning,
        private BuildConversationApprovalStates $approvalStates,
        private ReplayAgentStreamAction $replay,
    ) {}

    public function index(): Response
    {
        return Inertia::render('chat/index', [
            'conversations' => Inertia::scroll(
                fn (): LengthAwarePaginator => $this->user->paginatedConversations()
            ),
        ]);
    }

    public function create(
        StoreChatConversationRequest $request,
        string $conversationId
    ): Response {
        $conversation = $this->conversationAction->handle($conversationId, $this->user);
        Gate::authorize('view', $conversation);

        return Inertia::render('chat/create-chat', [
            'conversationId' => $conversation->id,
            'messages' => fn (): array => $this->messagesAction->handle($conversation),
            'initialPrompt' => $request->initialPrompt(),
            'creditWarning' => $this->buildCreditWarning
                ->currentState($this->user)
                ?->toArray(),
            'approvals' => fn (): array => $this->approvalStates->handle($conversation),
            'activeStream' => AgentStreamRun::query()
                ->active()
                ->where('conversation_id', $conversation->id)
                ->latest()
                ->first()
                ?->toActiveStreamData()
                ->toArray(),
        ]);
    }

    public function stream(
        StreamChatRequest $request,
        Conversation $conversation
    ): StreamedResponse {
        Gate::authorize('view', $conversation);

        return $this->agentAction->handle($request, $this->user, $conversation->id);
    }

    public function resume(
        Request $request,
        Conversation $conversation,
        string $run
    ): HttpResponse {
        Gate::authorize('view', $conversation);

        $streamRun = AgentStreamRun::query()
            ->whereKey($run)
            ->where('conversation_id', $conversation->id)
            ->first();

        $from = $request->integer('from', -1);

        if (! $streamRun instanceof AgentStreamRun || ! $this->replay->isResumable($streamRun, $from)) {
            return response()->noContent();
        }

        return $this->replay->handle($streamRun->id, $from);
    }
}
