<?php

declare(strict_types=1);

use App\Contracts\Ai\Memory\GeneratesMemoryQueries;
use App\Enums\MemoryType;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use App\Services\Memory\ContextRetriever;
use Laravel\Ai\Embeddings;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.retrieval.similarity_threshold', 0.3);
    config()->set('memory.retrieval.max_results', 3);
});

function stubQueryAgent(array $queries): void
{
    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldReceive('generateQueries')->andReturn($queries);
    app()->instance(GeneratesMemoryQueries::class, $mock);
}

it('returns an empty collection when the user id is zero', function (): void {
    $retriever = resolve(ContextRetriever::class);

    expect($retriever->recall(0, 'hi'))->toBeEmpty();
});

it('recalls memories above the similarity threshold and records access', function (): void {
    $user = User::factory()->create();

    stubQueryAgent(['coffee']);

    $queryVector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    Embeddings::fake(fn (): array => [$queryVector]);

    $match = MemoryModel::factory()->for($user)
        ->withVector($queryVector)
        ->create(['content' => 'User prefers oat milk', 'access_count' => 0]);

    MemoryModel::factory()->for($user)
        ->withVector([0.0, 1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0])
        ->create(['content' => 'unrelated']);

    $results = resolve(ContextRetriever::class)->recall($user->id, 'what milk do I like');

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($match->id)
        ->and($match->fresh()->access_count)->toBe(1);
});

it('applies recency and type bonuses in composite scoring', function (): void {
    $user = User::factory()->create();

    stubQueryAgent(['preference']);

    $queryVector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    Embeddings::fake(fn (): array => [$queryVector]);

    $relationship = MemoryModel::factory()->for($user)
        ->withVector([0.9, 0.1, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0])
        ->create(['memory_type' => MemoryType::Relationship, 'access_count' => 10, 'last_accessed_at' => now()]);

    $plainFact = MemoryModel::factory()->for($user)
        ->withVector([0.95, 0.05, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0])
        ->create(['memory_type' => MemoryType::Fact, 'access_count' => 0, 'last_accessed_at' => now()->subDays(80)]);

    $results = resolve(ContextRetriever::class)->recall($user->id, 'partner preference');

    expect($results)->toHaveCount(2)
        ->and($results->first()->id)->toBe($relationship->id)
        ->and($results->last()->id)->toBe($plainFact->id);
});

it('falls back to the user message when the query agent fails', function (): void {
    $user = User::factory()->create();

    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldReceive('generateQueries')->andThrow(new RuntimeException('LLM down'));
    app()->instance(GeneratesMemoryQueries::class, $mock);

    $queryVector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    Embeddings::fake(fn (): array => [$queryVector]);

    MemoryModel::factory()->for($user)->withVector($queryVector)->create(['content' => 'matched by fallback']);

    $results = resolve(ContextRetriever::class)->recall($user->id, 'the user message used as query');

    expect($results)->toHaveCount(1);
});
