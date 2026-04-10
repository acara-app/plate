<?php

declare(strict_types=1);

use App\Actions\BuildAssistantAgentAction;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use App\Models\User;
use Laravel\Ai\Responses\StreamableAgentResponse;

covers(BuildAssistantAgentAction::class);

beforeEach(function (): void {
    $this->action = resolve(BuildAssistantAgentAction::class);
});

/**
 * @param  array<array{role: string, parts: list<array{type: string, text?: string}>}>  $messages
 */
function makeStreamRequest(
    ModelName $model = ModelName::GPT_5_MINI,
    AgentMode $mode = AgentMode::Ask,
    array $messages = [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
    ?string $conversationId = null,
): StreamChatRequest {
    $conversationId ??= (string) fake()->uuid();

    $request = StreamChatRequest::create(
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

it('returns a StreamableAgentResponse', function (): void {
    $user = User::factory()->create();
    $request = makeStreamRequest();

    $conversationId = (string) fake()->uuid();
    $response = $this->action->handle($request, $user, $conversationId);

    expect($response)->toBeInstanceOf(StreamableAgentResponse::class);
});
