<?php

declare(strict_types=1);

use App\Actions\BuildAssistantAgentAction;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\StreamedResponse;

covers(BuildAssistantAgentAction::class);

beforeEach(function (): void {
    $this->action = resolve(BuildAssistantAgentAction::class);
});

/**
 * @param  array<array{role: string, parts: list<array{type: string, text?: string}>}>  $messages
 */
function makeStreamRequest(
    ModelName $model = ModelName::GPT_5_MINI,
    array $messages = [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
    ?string $conversationId = null,
): StreamChatRequest {
    $conversationId ??= (string) fake()->uuid();

    $request = StreamChatRequest::create(
        route('chat.stream', $conversationId),
        'POST',
        [
            'model' => $model->value,
            'messages' => $messages,
        ],
    );

    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}

it('returns a streamed response', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $request = makeStreamRequest();

    $conversationId = (string) fake()->uuid();
    $response = $this->action->handle($request, $user, $conversationId);

    expect($response)->toBeInstanceOf(StreamedResponse::class);
});
