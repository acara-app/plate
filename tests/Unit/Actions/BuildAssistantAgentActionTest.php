<?php

declare(strict_types=1);

use App\Actions\BuildAssistantAgentAction;
use App\Ai\Agents\AssistantAgent;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StoreAgentConversationRequest;
use App\Models\User;

beforeEach(function (): void {
    $this->action = resolve(BuildAssistantAgentAction::class);
});

/**
 * Build a request mock for the given model and mode.
 *
 * @param  array<array{role: string, parts: list<array{type: string, text?: string}>}>  $messages
 */
function makeStreamRequest(
    ModelName $model = ModelName::GPT_5_MINI,
    AgentMode $mode = AgentMode::Ask,
    array $messages = [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
    ?string $conversationId = null,
): StoreAgentConversationRequest {
    $conversationId ??= (string) fake()->uuid();

    $request = StoreAgentConversationRequest::create(
        route('chat.stream', $conversationId),
        'POST',
        [
            'model' => $model->value,
            'mode' => $mode->value,
            'messages' => $messages,
        ],
    );

    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}

it('returns an AssistantAgent instance', function (): void {
    AssistantAgent::fake(['OK']);

    $user = User::factory()->create();
    $request = makeStreamRequest();

    $agent = $this->action->handle($request, $user);

    expect($agent)->toBeInstanceOf(AssistantAgent::class);
});

it('enables web search for a model that supports it', function (): void {
    AssistantAgent::fake(['OK']);

    $user = User::factory()->create();
    $request = makeStreamRequest(model: ModelName::GPT_5_MINI); // supportsWebSearch = true

    $agent = $this->action->handle($request, $user);

    // Web search tool should be present — verify the tools list contains WebSearch
    $toolNames = collect($agent->tools())
        ->map(fn (mixed $tool): string => class_basename($tool))
        ->all();

    expect($toolNames)->toContain('WebSearch');
});

it('does not enable web search for a model that does not support it', function (): void {
    AssistantAgent::fake(['OK']);

    $user = User::factory()->create();
    $request = makeStreamRequest(model: ModelName::GEMINI_2_5_FLASH); // supportsWebSearch = false

    $agent = $this->action->handle($request, $user);

    $toolNames = collect($agent->tools())
        ->map(fn (mixed $tool): string => class_basename($tool))
        ->all();

    expect($toolNames)->not->toContain('WebSearch');
});
