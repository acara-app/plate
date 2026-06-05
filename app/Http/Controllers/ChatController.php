<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Approvals\BuildConversationApprovalStates;
use App\Actions\Billing\BuildCreditWarning;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\GetOrCreateConversationAction;
use App\Http\Requests\StoreChatConversationRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
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
        ]);
    }
}
