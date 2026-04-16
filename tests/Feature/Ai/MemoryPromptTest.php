<?php

declare(strict_types=1);

use App\Ai\MemoryPrompt;
use App\Contracts\Ai\Memory\GeneratesMemoryQueries;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use Laravel\Ai\Embeddings;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.retrieval.similarity_threshold', 0.3);
    config()->set('memory.retrieval.max_results', 3);
});

function bindQueryAgent(array $queries): void
{
    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldReceive('generateQueries')->andReturn($queries);
    app()->instance(GeneratesMemoryQueries::class, $mock);
}

it('renders an empty string when no memories are recalled', function (): void {
    $user = User::factory()->create();
    bindQueryAgent(['anything']);
    Embeddings::fake(fn (): array => [[1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]]);

    $rendered = resolve(MemoryPrompt::class)->for($user->id, 'hi')->render();

    expect($rendered)->toBe('');
});

it('renders a recalled memories block when there are results', function (): void {
    $user = User::factory()->create();
    bindQueryAgent(['dairy']);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    Embeddings::fake(fn (): array => [$vector]);

    MemoryModel::factory()
        ->for($user)
        ->withVector($vector)
        ->withCategories(['health', 'preference'])
        ->create(['content' => 'User avoids dairy']);

    $rendered = resolve(MemoryPrompt::class)->for($user->id, 'what can I eat tonight')->render();

    expect($rendered)->toContain('# RECALLED MEMORIES')
        ->and($rendered)->toContain('User avoids dairy')
        ->and($rendered)->toContain('[health, preference]');
});

it('returns an empty string when userId is invalid', function (): void {
    $rendered = resolve(MemoryPrompt::class)->for(0, 'hi')->render();

    expect($rendered)->toBe('');
});

it('renders pinned memories as CORE TRUTHS and suppresses them from RECALLED MEMORIES', function (): void {
    $user = User::factory()->create();
    bindQueryAgent(['dairy']);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    Embeddings::fake(fn (): array => [$vector]);

    $pinned = MemoryModel::factory()
        ->for($user)
        ->withVector($vector)
        ->withCategories(['health', 'allergy'])
        ->create([
            'content' => 'User has celiac disease',
            'is_pinned' => true,
            'importance' => 10,
        ]);

    MemoryModel::factory()
        ->for($user)
        ->withVector($vector)
        ->withCategories(['preference'])
        ->create(['content' => 'User prefers oat milk']);

    $rendered = resolve(MemoryPrompt::class)->for($user->id, 'what should I eat tonight')->render();

    expect($rendered)->toContain('# CORE TRUTHS')
        ->and($rendered)->toContain('User has celiac disease')
        ->and($rendered)->toContain('# RECALLED MEMORIES')
        ->and($rendered)->toContain('User prefers oat milk')
        ->and(mb_substr_count($rendered, 'User has celiac disease'))->toBe(1);

    expect($pinned->id)->not->toBeIn([]);
});
