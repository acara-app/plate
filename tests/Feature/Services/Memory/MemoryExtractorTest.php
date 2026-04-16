<?php

declare(strict_types=1);

use App\Contracts\Ai\Memory\ExtractsMemoriesFromConversation;
use App\Enums\MemoryType;
use App\Models\Conversation;
use App\Models\History;
use App\Models\Memory as MemoryModel;
use App\Models\MemoryExtractionCheckpoint;
use App\Models\User;
use App\Services\Memory\MemoryExtractor;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.extraction.max_memories', 5);
    config()->set('memory.extraction.max_turns', 40);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

function stubExtractorAgent(array $payload): void
{
    $mock = Mockery::mock(ExtractsMemoriesFromConversation::class);
    $mock->shouldReceive('extractFromConversation')->andReturn($payload);
    app()->instance(ExtractsMemoriesFromConversation::class, $mock);
}

it('returns zero when no pending messages exist for the user', function (): void {
    $user = User::factory()->create();

    expect(resolve(MemoryExtractor::class)->extractForUser($user->id))->toBe(0);
});

it('persists extracted memories with source=extraction and updates the checkpoint', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'I cannot do dairy anymore.',
    ]);
    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'content' => 'Got it, will plan around that.',
    ]);

    stubExtractorAgent([
        'should_extract' => true,
        'memories' => [
            ['content' => 'User is lactose intolerant', 'memory_type' => 'preference', 'categories' => ['health'], 'importance' => 8, 'context' => 'mentioned during dinner planning'],
            ['content' => 'User prefers 40-min meals on weeknights', 'memory_type' => 'preference', 'categories' => ['time'], 'importance' => 6, 'context' => null],
        ],
    ]);

    $count = resolve(MemoryExtractor::class)->extractForUser($user->id);

    expect($count)->toBe(2);

    $rows = MemoryModel::query()->where('user_id', $user->id)->get();
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('source')->unique()->values()->all())->toBe(['extraction'])
        ->and($rows->pluck('importance')->sort()->values()->all())->toBe([6, 8])
        ->and($rows->pluck('memory_type')->unique()->values()->all())->toBe([MemoryType::Preference]);

    $checkpoint = MemoryExtractionCheckpoint::query()->where('user_id', $user->id)->first();
    expect($checkpoint)->not->toBeNull()
        ->and($checkpoint->extracted_count)->toBe(2)
        ->and($checkpoint->last_extracted_message_id)->not->toBeNull()
        ->and($checkpoint->last_extracted_message_at)->not->toBeNull();
});

it('caps extraction at max_memories', function (): void {
    config()->set('memory.extraction.max_memories', 2);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'tell me things',
    ]);

    stubExtractorAgent([
        'should_extract' => true,
        'memories' => array_map(
            static fn (int $i): array => [
                'content' => 'fact '.$i,
                'memory_type' => 'fact',
                'categories' => [],
                'importance' => 5,
            ],
            range(1, 5),
        ),
    ]);

    $count = resolve(MemoryExtractor::class)->extractForUser($user->id);

    expect($count)->toBe(2)
        ->and(MemoryModel::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('advances the checkpoint with zero count when agent decides nothing is worth extracting', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'just saying hi',
    ]);

    stubExtractorAgent(['should_extract' => false, 'memories' => []]);

    $count = resolve(MemoryExtractor::class)->extractForUser($user->id);

    expect($count)->toBe(0)
        ->and(MemoryExtractionCheckpoint::query()->where('user_id', $user->id)->value('extracted_count'))->toBe(0)
        ->and(MemoryExtractionCheckpoint::query()->where('user_id', $user->id)->value('last_extracted_message_id'))->not->toBeNull();
});

it('determines whether extraction should run based on pending messages against the threshold', function (): void {
    config()->set('memory.extraction.threshold', 3);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    $extractor = resolve(MemoryExtractor::class);

    expect($extractor->shouldExtract($user->id))->toBeFalse();

    History::factory()->forConversation($conversation)->userMessage()->count(2)->create();
    expect($extractor->shouldExtract($user->id))->toBeFalse();

    History::factory()->forConversation($conversation)->userMessage()->count(2)->create();
    expect($extractor->shouldExtract($user->id))->toBeTrue();
});
