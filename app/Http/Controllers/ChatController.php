<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Ai\Advisor;
use App\Contracts\Ai\HealthCoachAdvisorContract;
use App\Contracts\Ai\PersonalTrainerAdvisorContract;
use App\Enums\AgentMode;
use App\Enums\AgentType;
use App\Http\Requests\StoreAgentConversationRequest;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Ai\Responses\StreamableAgentResponse;

final class ChatController
{
    public function create(
        Request $request,
        AgentType $agentType,
        string $conversationId = ''
    ): \Inertia\Response {
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
            'agentType' => $agentType->value,
        ]);
    }

    public function stream(
        StoreAgentConversationRequest $request
    ): StreamableAgentResponse {
        $agent = $this->resolveAgent($request->agentType(), $request->user())
            ->withMode($request->mode())
            ->forUser($request->user());

        return $agent
            ->stream(
                prompt: $request->userMessage(),
                model: $request->modelName()->value
            )
            ->usingVercelDataProtocol();
    }

    private function resolveAgent(AgentType $type, User $user): Advisor|HealthCoachAdvisorContract|PersonalTrainerAdvisorContract
    {
        return match ($type) {
            AgentType::Nutrition => resolve(Advisor::class, ['user' => $user]),
            AgentType::HealthCoach => resolve(HealthCoachAdvisorContract::class, ['user' => $user]),
            AgentType::PersonalTrainer => resolve(PersonalTrainerAdvisorContract::class, ['user' => $user]),
        };
    }
}
