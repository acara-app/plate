<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Approvals\BuildConversationApprovalStates;
use App\Actions\Billing\BuildCreditWarning;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\DeleteConversationHistory;
use App\Actions\GetOrCreateConversationAction;
use App\Actions\StartChatStream;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Http\Requests\StoreChatConversationRequest;
use App\Http\Requests\StreamChatRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ChatController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private BuildConversationMessagesAction $messagesAction,
        private GetOrCreateConversationAction $conversationAction,
        private BuildCreditWarning $buildCreditWarning,
        private BuildConversationApprovalStates $approvalStates,
        private DeleteConversationHistory $deleteConversationHistory,
        private StartChatStream $startChatStream,
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
            'initialStreaming' => $conversation->hasPendingChatStream(),
            'creditWarning' => $this->buildCreditWarning
                ->currentState($this->user)
                ?->toArray(),
            'approvals' => fn (): array => $this->approvalStates->handle($conversation),
        ]);
    }

    public function store(
        StreamChatRequest $request,
        string $conversationId
    ): RedirectResponse {
        abort_unless(Str::isUuid($conversationId), 400, 'Invalid conversation ID format');

        $conversation = $this->conversationAction->handle($conversationId, $this->user, withMessages: false);
        Gate::authorize('view', $conversation);

        try {
            $this->startChatStream->handle($request, $this->user, $conversation);
        } catch (UsageLimitExceededException $usageLimitExceededException) {
            return back()->withErrors(['message' => $usageLimitExceededException->userMessage()]);
        }

        return to_route('chat.create', $conversation->id);
    }

    public function destroy(Conversation $conversation): RedirectResponse
    {
        Gate::authorize('delete', $conversation);

        $this->deleteConversationHistory->handle($conversation);

        return to_route('chat.index');
    }
}
