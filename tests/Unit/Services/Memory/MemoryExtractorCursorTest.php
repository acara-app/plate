<?php

declare(strict_types=1);

use App\Contracts\Ai\Memory\ExtractsMemoriesFromConversation;
use App\Models\Conversation;
use App\Models\History;
use App\Models\MemoryExtractionCheckpoint;
use App\Models\User;
use App\Services\Memory\MemoryExtractor;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.extraction.max_turns', 40);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

it('advances the cursor so subsequent runs process only the new rows', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'turn-1',
        'created_at' => now()->subMinutes(10),
    ]);
    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'content' => 'turn-2',
        'created_at' => now()->subMinutes(9),
    ]);
    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'turn-3',
        'created_at' => now()->subMinutes(8),
    ]);

    $captured = [];
    $mock = Mockery::mock(ExtractsMemoriesFromConversation::class);
    $mock->shouldReceive('extractFromConversation')
        ->andReturnUsing(function (string $conversationText) use (&$captured): array {
            $captured[] = $conversationText;

            return ['should_extract' => false, 'memories' => []];
        });
    app()->instance(ExtractsMemoriesFromConversation::class, $mock);

    resolve(MemoryExtractor::class)->extractForUser($user->id);

    expect($captured)->toHaveCount(1)
        ->and($captured[0])->toContain('turn-1')
        ->and($captured[0])->toContain('turn-2')
        ->and($captured[0])->toContain('turn-3');

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'turn-4',
        'created_at' => now()->subMinutes(5),
    ]);
    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'content' => 'turn-5',
        'created_at' => now()->subMinutes(4),
    ]);

    resolve(MemoryExtractor::class)->extractForUser($user->id);

    expect($captured)->toHaveCount(2)
        ->and($captured[1])->not->toContain('turn-1')
        ->and($captured[1])->not->toContain('turn-2')
        ->and($captured[1])->not->toContain('turn-3')
        ->and($captured[1])->toContain('turn-4')
        ->and($captured[1])->toContain('turn-5');

    $checkpoint = MemoryExtractionCheckpoint::query()->where('user_id', $user->id)->firstOrFail();
    expect($checkpoint->last_extracted_message_id)->not->toBeNull();
});

it('respects the max_turns limit when loading pending messages', function (): void {
    config()->set('memory.extraction.max_turns', 2);

    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    foreach (range(1, 5) as $i) {
        History::factory()->forConversation($conversation)->userMessage()->create([
            'content' => 'turn-'.$i,
            'created_at' => now()->subMinutes(10 - $i),
        ]);
    }

    $pending = resolve(MemoryExtractor::class)->pendingHistoryFor($user->id);

    expect($pending)->toHaveCount(2)
        ->and($pending->first()->content)->toBe('turn-1');
});
