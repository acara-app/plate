<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Ai\Agents\NutritionAdvisor;
use App\Http\Requests\StoreAgentConversationRequest;
use App\Models\Conversation;
use Inertia\Inertia;
use Laravel\Ai\Responses\StreamableAgentResponse;

final class ChatController
{
    public function create(?string $conversationId): \Inertia\Response
    {
        $conversation = Conversation::query()->find($conversationId);

        $messages = $conversation?->messages->map(fn ($message) => [
            'id' => $message->id,
            'role' => $message->role->value,
            'parts' => [
                ['type' => 'text', 'text' => $message->content],
            ],
        ])->all() ?? [];

        return Inertia::render('chat/create-chat', [
            'conversationId' => $conversation?->id,
            'messages' => $messages,
        ]);
    }

    public function stream(
        StoreAgentConversationRequest $request
    ): StreamableAgentResponse {
        return (new NutritionAdvisor(user: $request->user()))
            ->forUser($request->user())
            ->stream($request->userMessage())
            ->usingVercelDataProtocol();
    }
}
