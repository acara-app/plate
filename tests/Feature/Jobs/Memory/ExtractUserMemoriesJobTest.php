<?php

declare(strict_types=1);

use App\Contracts\Ai\Memory\ExtractsMemoriesFromConversation;
use App\Jobs\Memory\ExtractUserMemoriesJob;
use App\Models\Conversation;
use App\Models\History;
use App\Models\Memory as MemoryModel;
use App\Models\MemoryExtractionCheckpoint;
use App\Models\User;
use App\Services\Memory\MemoryExtractor;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

it('can be dispatched onto the queue', function (): void {
    Queue::fake();

    dispatch(new ExtractUserMemoriesJob(42));

    Queue::assertPushed(ExtractUserMemoriesJob::class, fn (ExtractUserMemoriesJob $job): bool => $job->userId === 42);
});

it('invokes the extractor and writes rows when handled', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'no peanuts please',
    ]);

    $mock = Mockery::mock(ExtractsMemoriesFromConversation::class);
    $mock->shouldReceive('extractFromConversation')->andReturn([
        'should_extract' => true,
        'memories' => [
            ['content' => 'User is allergic to peanuts', 'memory_type' => 'preference', 'categories' => ['health'], 'importance' => 9],
        ],
    ]);
    app()->instance(ExtractsMemoriesFromConversation::class, $mock);

    new ExtractUserMemoriesJob($user->id)->handle(resolve(MemoryExtractor::class));

    expect(MemoryModel::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(MemoryExtractionCheckpoint::query()->where('user_id', $user->id)->value('extracted_count'))->toBe(1);
});
