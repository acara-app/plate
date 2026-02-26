<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Ai\Advisor;
use App\Enums\AgentMode;
use App\Http\Requests\StoreAgentConversationRequest;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Providers\Tools\WebSearch;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class ChatController
{
    public function __construct(
        #[CurrentUser] private User $user,
    ) {}

    public function create(
        Request $request,
        string $conversationId = ''
    ): Response {
        $conversation = $conversationId !== ''
            ? Conversation::query()->with('messages')->find($conversationId)
            : null;

        $messages = $conversation?->messages->map(fn (History $message): array => [
            'id' => $message->id,
            'role' => $message->role->value,
            'parts' => [
                ['type' => 'text', 'text' => $message->content],
            ],
        ])->all() ?? [];

        return Inertia::render('chat/create-chat', [
            'conversationId' => $conversation?->id,
            'messages' => $messages,
            'mode' => $request->enum('mode', AgentMode::class),
        ]);
    }

    public function stream(
        StoreAgentConversationRequest $request
    ): StreamableAgentResponse {

        $agent = resolve(Advisor::class, ['user' => $this->user])
            ->withMode($request->mode())
            ->forUser($this->user);

        $model = $request->modelName()->value;

        if (in_array($model, ['gpt-5-mini', 'gpt-5-nano'], true)) {
            $agent->addTool(new WebSearch);
        }

        $providers = match ($model) {
            'gpt-5-mini', 'gpt-5-nano' => [Lab::OpenAI, Lab::Gemini],
            default => [Lab::Gemini],
        };

        return $agent
            ->stream(
                prompt: $request->userMessage(),
                model: $model,
                provider: $providers
            )
            ->usingVercelDataProtocol();
    }
}
