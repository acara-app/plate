<?php

declare(strict_types=1);

use App\Actions\StartChatStream;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\GenerateConversationTitleJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

covers(StartChatStream::class);

function makeStartStreamRequest(string $conversationId, string $text = 'Hello there'): StreamChatRequest
{
    $request = StreamChatRequest::create(
        route('chat.stream', $conversationId),
        'POST',
        [
            'model' => ModelName::GPT_5_MINI->value,
            'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => $text]]]],
        ],
    );

    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}

it('dispatches a title generation job on the first turn', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => Conversation::DEFAULT_TITLE]);

    $request = makeStartStreamRequest($conversation->id);

    resolve(StartChatStream::class)->handle($request, $user, $conversation);

    Queue::assertPushed(
        GenerateConversationTitleJob::class,
        fn (GenerateConversationTitleJob $job): bool => $job->conversation->id === $conversation->id,
    );
});

it('does not dispatch a title generation job on later turns', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create(['title' => 'Lowering morning glucose spikes']);

    History::factory()->forConversation($conversation)->create(['role' => 'user']);

    $request = makeStartStreamRequest($conversation->id);

    resolve(StartChatStream::class)->handle($request, $user, $conversation);

    Queue::assertNotPushed(GenerateConversationTitleJob::class);
});
