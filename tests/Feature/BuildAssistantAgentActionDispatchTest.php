<?php

declare(strict_types=1);

use App\Actions\BuildAssistantAgentAction;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

covers(BuildAssistantAgentAction::class);

function makeDispatchStreamRequest(
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

describe('dispatchSummarizationIfNeeded', function (): void {
    it('dispatches job when messages exceed buffer plus threshold', function (): void {
        Queue::fake();

        $action = resolve(BuildAssistantAgentAction::class);
        $user = User::factory()->create();
        $conversation = Conversation::factory()->forUser($user)->create([
            'summarization_dispatched_at' => null,
        ]);

        History::factory()
            ->count(50)
            ->forConversation($conversation)
            ->sequence(
                fn ($sequence): array => [
                    'role' => $sequence->index % 2 === 0 ? 'user' : 'assistant',
                    'created_at' => now()->subMinutes(50 - $sequence->index),
                ],
            )
            ->create();

        $request = makeDispatchStreamRequest(conversationId: $conversation->id);

        $action->handle($request, $user, $conversation->id);

        Queue::assertPushed(SummarizeConversationJob::class, fn ($job): bool => $job->conversation->id === $conversation->id);

        $conversation->refresh();
        expect($conversation->summarization_dispatched_at)->not->toBeNull();
    });

    it('does not dispatch when summarization was recently dispatched', function (): void {
        Queue::fake();

        $action = resolve(BuildAssistantAgentAction::class);
        $user = User::factory()->create();
        $conversation = Conversation::factory()->forUser($user)->create([
            'summarization_dispatched_at' => now()->subMinutes(3),
        ]);

        History::factory()
            ->count(50)
            ->forConversation($conversation)
            ->create();

        $request = makeDispatchStreamRequest(conversationId: $conversation->id);

        $action->handle($request, $user, $conversation->id);

        Queue::assertNotPushed(SummarizeConversationJob::class);
    });

    it('does not dispatch when message count is below threshold', function (): void {
        Queue::fake();

        $action = resolve(BuildAssistantAgentAction::class);
        $user = User::factory()->create();
        $conversation = Conversation::factory()->forUser($user)->create();

        History::factory()
            ->count(10)
            ->forConversation($conversation)
            ->create();

        $request = makeDispatchStreamRequest(conversationId: $conversation->id);

        $action->handle($request, $user, $conversation->id);

        Queue::assertNotPushed(SummarizeConversationJob::class);
    });
});
