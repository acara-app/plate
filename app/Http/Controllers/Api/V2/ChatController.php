<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\BuildAssistantAgentAction;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\GetOrCreateConversationAction;
use App\Http\Requests\Api\V2\ChatStreamRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class ChatController
{
    public function __construct(
        private BuildConversationMessagesAction $messagesAction,
        private BuildAssistantAgentAction $agentAction,
        private GetOrCreateConversationAction $conversationAction,
    ) {}

    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $conversations = $user->conversations()
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at'])
            ->map(fn (Conversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'updated_at' => $conversation->updated_at->toIso8601String(),
            ]);

        return response()->json(['data' => $conversations]);
    }

    public function show(
        #[CurrentUser] User $user,
        string $conversationId
    ): JsonResponse {
        $conversation = $this->conversationAction->handle($conversationId, $user);
        Gate::authorize('view', $conversation);

        return response()->json([
            'id' => $conversation->id,
            'title' => $conversation->title,
            'messages' => $this->messagesAction->handle($conversation),
        ]);
    }

    public function stream(
        ChatStreamRequest $request,
        #[CurrentUser] User $user,
        string $conversationId
    ): StreamableAgentResponse {
        $conversation = $this->conversationAction->handle($conversationId, $user);
        Gate::authorize('view', $conversation);

        return $this->agentAction->handle($request, $user, $conversation->id, 'mobile');
    }

    public function destroy(string $conversationId): JsonResponse
    {
        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation instanceof Conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        Gate::authorize('view', $conversation);
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted.']);
    }
}
