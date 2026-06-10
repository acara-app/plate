<?php

declare(strict_types=1);

use App\Actions\StartChatStream;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

covers(StartChatStream::class);

function makeSummarizationStreamRequest(string $conversationId): StreamChatRequest
{
    $request = StreamChatRequest::create(
        route('chat.stream', $conversationId),
        'POST',
        [
            'model' => ModelName::GPT_5_MINI->value,
            'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
        ],
    );

    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}

it('dispatches summarization when messages exceed buffer plus threshold', function (): void {
    Queue::fake();

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

    resolve(StartChatStream::class)->handle(makeSummarizationStreamRequest($conversation->id), $user, $conversation);

    Queue::assertPushed(SummarizeConversationJob::class, fn ($job): bool => $job->conversation->id === $conversation->id);

    $conversation->refresh();
    expect($conversation->summarization_dispatched_at)->not->toBeNull();
});

it('does not dispatch summarization when it was recently dispatched', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create([
        'summarization_dispatched_at' => now()->subMinutes(3),
    ]);

    History::factory()
        ->count(50)
        ->forConversation($conversation)
        ->create();

    resolve(StartChatStream::class)->handle(makeSummarizationStreamRequest($conversation->id), $user, $conversation);

    Queue::assertNotPushed(SummarizeConversationJob::class);
});

it('does not dispatch summarization when message count is below threshold', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()
        ->count(10)
        ->forConversation($conversation)
        ->create();

    resolve(StartChatStream::class)->handle(makeSummarizationStreamRequest($conversation->id), $user, $conversation);

    Queue::assertNotPushed(SummarizeConversationJob::class);
});
