<?php

declare(strict_types=1);

use App\Actions\BuildAssistantAgentAction;
use App\Contracts\Ai\Memory\ExtractsMemoriesFromConversation;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\Memory\ExtractUserMemoriesJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\MemoryExtractionCheckpoint;
use App\Models\User;
use App\Services\Memory\MemoryExtractor;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

covers(BuildAssistantAgentAction::class);

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.extraction.threshold', 3);
    config()->set('memory.extraction.cooldown_minutes', 5);
    config()->set('memory.extraction.max_turns', 40);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

function makeMemoryDispatchStreamRequest(string $conversationId): StreamChatRequest
{
    $request = StreamChatRequest::create(
        route('chat.stream', $conversationId),
        'POST',
        [
            'model' => ModelName::GPT_5_MINI->value,
            'mode' => AgentMode::Ask->value,
            'messages' => [['role' => 'user', 'parts' => [['type' => 'text', 'text' => 'Hello']]]],
        ],
    );

    $request->setContainer(app());
    $request->validateResolved();

    return $request;
}

it('dispatches ExtractUserMemoriesJob when pending messages cross the threshold', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()
        ->forConversation($conversation)
        ->count(4)
        ->sequence(fn ($seq): array => [
            'role' => $seq->index % 2 === 0 ? 'user' : 'assistant',
            'content' => 'turn-'.$seq->index,
            'created_at' => now()->subMinutes(10 - $seq->index),
        ])
        ->create();

    resolve(BuildAssistantAgentAction::class)
        ->handle(makeMemoryDispatchStreamRequest($conversation->id), $user, $conversation->id);

    Queue::assertPushed(
        ExtractUserMemoriesJob::class,
        fn (ExtractUserMemoriesJob $job): bool => $job->userId === $user->id,
    );

    expect(MemoryExtractionCheckpoint::query()->where('user_id', $user->id)->value('last_extracted_at'))
        ->not->toBeNull();
});

it('does not dispatch when pending messages are below the threshold', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->count(2)->create();

    resolve(BuildAssistantAgentAction::class)
        ->handle(makeMemoryDispatchStreamRequest($conversation->id), $user, $conversation->id);

    Queue::assertNotPushed(ExtractUserMemoriesJob::class);
});

it('does not dispatch when within the cooldown window', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    MemoryExtractionCheckpoint::query()->create([
        'user_id' => $user->id,
        'last_extracted_at' => now()->subMinutes(2),
        'extracted_count' => 0,
    ]);

    History::factory()->forConversation($conversation)->count(10)->create();

    resolve(BuildAssistantAgentAction::class)
        ->handle(makeMemoryDispatchStreamRequest($conversation->id), $user, $conversation->id);

    Queue::assertNotPushed(ExtractUserMemoriesJob::class);
});

it('passes the planted conversation turns to the extractor when the job runs', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    foreach (['first-turn', 'second-turn', 'third-turn'] as $i => $content) {
        History::factory()->forConversation($conversation)->create([
            'role' => $i % 2 === 0 ? 'user' : 'assistant',
            'content' => $content,
            'created_at' => now()->subMinutes(10 - $i),
        ]);
    }

    $captured = [];
    $mock = Mockery::mock(ExtractsMemoriesFromConversation::class);
    $mock->shouldReceive('extractFromConversation')
        ->andReturnUsing(function (string $formatted) use (&$captured): array {
            $captured[] = $formatted;

            return ['should_extract' => false, 'memories' => []];
        });
    app()->instance(ExtractsMemoriesFromConversation::class, $mock);

    Queue::fake();
    resolve(BuildAssistantAgentAction::class)
        ->handle(makeMemoryDispatchStreamRequest($conversation->id), $user, $conversation->id);

    Queue::assertPushed(ExtractUserMemoriesJob::class);

    new ExtractUserMemoriesJob($user->id)->handle(resolve(MemoryExtractor::class));

    expect($captured)->toHaveCount(1)
        ->and($captured[0])->toContain('first-turn')
        ->and($captured[0])->toContain('second-turn')
        ->and($captured[0])->toContain('third-turn');
});
