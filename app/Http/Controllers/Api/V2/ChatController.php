<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\BuildConversationMessagesAction;
use App\Actions\DeleteConversationHistory;
use App\Actions\GetOrCreateConversationAction;
use App\Actions\PinConversation;
use App\Actions\UnpinConversation;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ChatController
{
    public function __construct(
        private BuildConversationMessagesAction $messagesAction,
        private GetOrCreateConversationAction $conversationAction,
        private DeleteConversationHistory $deleteConversationHistory,
        private PinConversation $pinConversation,
        private UnpinConversation $unpinConversation,
    ) {}

    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $conversations = $user->conversations()
            ->reorder()
            ->orderByRaw('pinned_at is null')
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'pinned_at', 'updated_at'])
            ->map(fn (Conversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'updated_at' => $conversation->updated_at->toIso8601String(),
                'is_pinned' => $conversation->isPinned(),
                'pinned_at' => $conversation->pinned_at?->toIso8601String(),
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
            'is_pinned' => $conversation->isPinned(),
            'messages' => $this->messagesAction->handle($conversation),
        ]);
    }

    public function destroy(string $conversationId): JsonResponse
    {
        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation instanceof Conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        Gate::authorize('delete', $conversation);
        $this->deleteConversationHistory->handle($conversation);

        return response()->json(['message' => 'Conversation deleted.']);
    }

    public function pin(string $conversationId): JsonResponse
    {
        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation instanceof Conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        Gate::authorize('pin', $conversation);
        $this->pinConversation->handle($conversation);

        return response()->json([
            'id' => $conversation->id,
            'is_pinned' => $conversation->isPinned(),
            'pinned_at' => $conversation->pinned_at?->toIso8601String(),
        ]);
    }

    public function unpin(string $conversationId): JsonResponse
    {
        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation instanceof Conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        Gate::authorize('pin', $conversation);
        $this->unpinConversation->handle($conversation);

        return response()->json([
            'id' => $conversation->id,
            'is_pinned' => $conversation->isPinned(),
            'pinned_at' => $conversation->pinned_at?->toIso8601String(),
        ]);
    }
}
