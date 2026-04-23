<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildAssistantAgentAction;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\GetOrCreateConversationAction;
use App\Enums\AgentMode;
use App\Http\Requests\StoreChatConversationRequest;
use App\Http\Requests\StreamChatRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class ChatController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private BuildConversationMessagesAction $messagesAction,
        private BuildAssistantAgentAction $agentAction,
        private GetOrCreateConversationAction $conversationAction,
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
            'messages' => $this->messagesAction->handle($conversation),
            'mode' => $request->enum('mode', AgentMode::class),
        ]);
    }

    public function stream(
        StreamChatRequest $request,
        Conversation $conversation
    ): StreamableAgentResponse {
        Gate::authorize('view', $conversation);

        return $this->agentAction->handle($request, $this->user, $conversation->id);
    }
}
