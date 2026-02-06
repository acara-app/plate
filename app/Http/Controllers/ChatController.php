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
    public function create(
        string $conversationId = ''
    ): \Inertia\Response {
        $conversation = $conversationId !== '' ? Conversation::query()->find($conversationId) : null;

        $messages = $conversation?->messages->map(fn ($message): array => [
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
        $agent = resolve(NutritionAdvisor::class, ['user' => $request->user()])
            ->withMode($request->mode())
            ->forUser($request->user());

        return $agent
            ->stream($request->userMessage())
            ->usingVercelDataProtocol();
    }
}
