<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\BuildAssistantAgentAction;
use App\Actions\BuildConversationMessagesAction;
use App\Actions\GetOrCreateConversationAction;
use App\Actions\ReplayAgentStreamAction;
use App\Http\Requests\Api\V2\ChatStreamRequest;
use App\Models\AgentStreamRun;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ChatController
{
    public function __construct(
        private BuildConversationMessagesAction $messagesAction,
        private BuildAssistantAgentAction $agentAction,
        private GetOrCreateConversationAction $conversationAction,
        private ReplayAgentStreamAction $replay,
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
            'active_stream' => AgentStreamRun::query()
                ->active()
                ->where('conversation_id', $conversation->id)
                ->latest()
                ->first()
                ?->toActiveStreamData()
                ->toArray(),
        ]);
    }

    public function stream(
        ChatStreamRequest $request,
        #[CurrentUser] User $user,
        string $conversationId
    ): StreamedResponse {
        $conversation = $this->conversationAction->handle($conversationId, $user);
        Gate::authorize('view', $conversation);

        return $this->agentAction->handle($request, $user, $conversation->id, 'mobile');
    }

    public function resume(
        Request $request,
        #[CurrentUser] User $user,
        string $conversationId,
        string $run
    ): Response {
        $conversation = $this->conversationAction->handle($conversationId, $user);
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
