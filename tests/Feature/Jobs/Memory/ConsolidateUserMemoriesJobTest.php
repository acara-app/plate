<?php

declare(strict_types=1);

use App\Contracts\Ai\Memory\DecidesMemoryMerge;
use App\Jobs\Memory\ConsolidateUserMemoriesJob;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use App\Services\Memory\MemoryConsolidator;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.consolidation.similarity_threshold', 0.80);
    config()->set('memory.consolidation.min_cluster_size', 2);
    config()->set('memory.consolidation.days_lookback', 3);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

it('dispatches onto the queue with the supplied user id and flags', function (): void {
    Queue::fake();

    dispatch(new ConsolidateUserMemoriesJob(42, true, 5, 0.9));

    Queue::assertPushed(ConsolidateUserMemoriesJob::class, fn (ConsolidateUserMemoriesJob $job): bool => $job->userId === 42
        && $job->dryRun
        && $job->daysLookback === 5
        && $job->threshold === 0.9);
});

it('invokes the consolidator and merges near-duplicate memories when handled', function (): void {
    $user = User::factory()->create();

    $vectorA = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $vectorB = [0.99, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($vectorA)->create(['content' => 'a']);
    MemoryModel::factory()->for($user)->withVector($vectorB)->create(['content' => 'b']);

    $mock = Mockery::mock(DecidesMemoryMerge::class);
    $mock->shouldReceive('decide')->andReturn([
        'should_merge' => true,
        'reasoning' => 'merge them',
        'synthesized_content' => 'combined',
        'importance' => 6,
        'categories' => [],
    ]);
    app()->instance(DecidesMemoryMerge::class, $mock);

    new ConsolidateUserMemoriesJob($user->id)->handle(resolve(MemoryConsolidator::class));

    $consolidated = MemoryModel::query()
        ->where('user_id', $user->id)
        ->where('source', 'consolidation')
        ->first();

    expect($consolidated)->not->toBeNull()
        ->and($consolidated->content)->toBe('combined')
        ->and($consolidated->consolidation_generation)->toBe(1);
});
