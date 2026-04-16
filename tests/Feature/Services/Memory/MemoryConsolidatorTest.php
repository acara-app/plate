<?php

declare(strict_types=1);

use App\Contracts\Ai\Memory\DecidesMemoryMerge;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use App\Services\Memory\MemoryConsolidator;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Mockery\MockInterface;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.consolidation.similarity_threshold', 0.80);
    config()->set('memory.consolidation.min_cluster_size', 2);
    config()->set('memory.consolidation.max_cluster_size', 5);
    config()->set('memory.consolidation.max_memories_per_run', 100);
    config()->set('memory.consolidation.max_generation', 5);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

function stubDecider(array $decision): MockInterface
{
    $mock = Mockery::mock(DecidesMemoryMerge::class);
    $mock->shouldReceive('decide')->andReturn($decision);
    app()->instance(DecidesMemoryMerge::class, $mock);

    return $mock;
}

it('returns an empty result when there are not enough candidates', function (): void {
    $user = User::factory()->create();
    MemoryModel::factory()->for($user)->create();

    $result = resolve(MemoryConsolidator::class)->consolidateForUser($user->id);

    expect($result->clustersProcessed)->toBe(0)
        ->and($result->memoriesCreated)->toBe(0);
});

it('clusters near-duplicate memories and merges them via the decider', function (): void {
    $user = User::factory()->create();

    $similarA = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $similarB = [0.99, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $unrelated = [0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    $m1 = MemoryModel::factory()->for($user)->withVector($similarA)->create(['content' => 'User avoids dairy']);
    $m2 = MemoryModel::factory()->for($user)->withVector($similarB)->create(['content' => 'User is lactose intolerant']);
    MemoryModel::factory()->for($user)->withVector($unrelated)->create(['content' => 'User enjoys jogging']);

    stubDecider([
        'should_merge' => true,
        'reasoning' => 'Both describe the same dietary restriction.',
        'synthesized_content' => 'User avoids dairy due to lactose intolerance.',
        'importance' => 7,
        'categories' => ['health'],
    ]);

    $result = resolve(MemoryConsolidator::class)->consolidateForUser($user->id);

    expect($result->clustersProcessed)->toBe(1)
        ->and($result->memoriesCreated)->toBe(1)
        ->and($result->memoriesConsolidated)->toBe(2);

    expect(MemoryModel::query()->find($m1->id))->toBeNull()
        ->and(MemoryModel::query()->find($m2->id))->toBeNull();

    $newMemory = MemoryModel::query()->where('user_id', $user->id)->where('source', 'consolidation')->first();
    expect($newMemory)->not->toBeNull()
        ->and($newMemory->content)->toBe('User avoids dairy due to lactose intolerance.')
        ->and($newMemory->consolidation_generation)->toBe(1)
        ->and($newMemory->consolidated_from)->toBe([$m1->id, $m2->id]);
});

it('keeps clusters separate when the decider rejects them', function (): void {
    $user = User::factory()->create();

    $similarA = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $similarB = [0.99, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($similarA)->create();
    MemoryModel::factory()->for($user)->withVector($similarB)->create();

    stubDecider([
        'should_merge' => false,
        'reasoning' => 'Separate moments, do not merge.',
        'synthesized_content' => null,
        'importance' => null,
        'categories' => [],
    ]);

    $result = resolve(MemoryConsolidator::class)->consolidateForUser($user->id);

    expect($result->clustersProcessed)->toBe(1)
        ->and($result->memoriesCreated)->toBe(0)
        ->and($result->clustersKeptSeparate)->toBe(1)
        ->and(MemoryModel::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('skips clusters whose sources have reached max generation', function (): void {
    config()->set('memory.consolidation.max_generation', 2);

    $user = User::factory()->create();

    $similarA = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $similarB = [0.99, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($similarA)->create(['consolidation_generation' => 2]);
    MemoryModel::factory()->for($user)->withVector($similarB)->create(['consolidation_generation' => 1]);

    stubDecider([
        'should_merge' => true,
        'reasoning' => 'irrelevant',
        'synthesized_content' => 'should not be used',
        'importance' => 5,
        'categories' => [],
    ]);

    $result = resolve(MemoryConsolidator::class)->consolidateForUser($user->id);

    expect($result->clustersKeptSeparate)->toBe(1)
        ->and($result->memoriesCreated)->toBe(0);
});

it('persists the refined categories returned by the decider on the consolidated memory', function (): void {
    $user = User::factory()->create();

    $similarA = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $similarB = [0.99, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($similarA)->withCategories(['preference'])->create();
    MemoryModel::factory()->for($user)->withVector($similarB)->withCategories(['habit'])->create();

    stubDecider([
        'should_merge' => true,
        'reasoning' => 'same underlying dietary restriction',
        'synthesized_content' => 'User is lactose intolerant',
        'importance' => 8,
        'categories' => ['health', 'allergy'],
    ]);

    resolve(MemoryConsolidator::class)->consolidateForUser($user->id);

    $newMemory = MemoryModel::query()
        ->where('user_id', $user->id)
        ->where('source', 'consolidation')
        ->firstOrFail();

    expect($newMemory->categories)->toBe(['health', 'allergy'])
        ->and($newMemory->categories)->not->toContain('preference')
        ->and($newMemory->categories)->not->toContain('habit');
});

it('reports a dry-run without persisting a consolidated memory', function (): void {
    $user = User::factory()->create();

    $similarA = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    $similarB = [0.99, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    MemoryModel::factory()->for($user)->withVector($similarA)->create();
    MemoryModel::factory()->for($user)->withVector($similarB)->create();

    stubDecider([
        'should_merge' => true,
        'reasoning' => 'merge',
        'synthesized_content' => 'merged',
        'importance' => 5,
        'categories' => [],
    ]);

    $result = resolve(MemoryConsolidator::class)->consolidateForUser($user->id, dryRun: true);

    expect($result->memoriesCreated)->toBe(1)
        ->and(MemoryModel::query()->where('user_id', $user->id)->count())->toBe(2);
});
