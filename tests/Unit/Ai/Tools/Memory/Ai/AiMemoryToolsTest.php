<?php

declare(strict_types=1);

use App\Ai\Tools\Memory\Ai\AiDeleteMemory;
use App\Ai\Tools\Memory\Ai\AiGetImportantMemories;
use App\Ai\Tools\Memory\Ai\AiGetMemory;
use App\Ai\Tools\Memory\Ai\AiLinkMemories;
use App\Ai\Tools\Memory\Ai\AiSearchMemory;
use App\Ai\Tools\Memory\Ai\AiStoreMemory;
use App\Ai\Tools\Memory\Ai\AiUpdateMemory;
use App\Enums\MemoryType;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);

    Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(
        fn (): array => Embeddings::fakeEmbedding($prompt->dimensions ?? 8),
        $prompt->inputs,
    ));
});

it('AiStoreMemory stores a memory via the facade and returns the id', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $tool = resolve(AiStoreMemory::class);

    $result = json_decode(
        $tool->handle(new Request([
            'content' => 'User avoids gluten',
            'importance' => 9,
            'categories' => ['health', 'allergy'],
            'memory_type' => MemoryType::Preference->value,
        ])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and($result['memory_id'])->toBeString();

    $row = MemoryModel::query()->findOrFail($result['memory_id']);
    expect($row->user_id)->toBe($user->id)
        ->and($row->memory_type)->toBe(MemoryType::Preference)
        ->and($row->importance)->toBe(9);

    $schema = $tool->schema(new TestJsonSchema);
    expect($schema)->toHaveKey('content')
        ->toHaveKey('importance')
        ->toHaveKey('categories')
        ->toHaveKey('memory_type');

    expect($tool->name())->toBe('store_memory')
        ->and($tool->description())->toContain('long-term memory');
});

it('AiSearchMemory returns JSON-encoded search results', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];
    Embeddings::fake(fn (): array => [$vector]);

    MemoryModel::factory()->for($user)->withVector($vector)->create([
        'content' => 'User is lactose intolerant',
    ]);

    $tool = resolve(AiSearchMemory::class);

    $result = json_decode(
        $tool->handle(new Request([
            'query' => 'dairy',
            'limit' => 3,
            'min_relevance' => 0.1,
        ])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and($result['results'])->toHaveCount(1)
        ->and($result['results'][0]['content'])->toBe('User is lactose intolerant');

    expect($tool->name())->toBe('search_memory');
});

it('AiGetMemory returns the memory by id', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create(['content' => 'fact']);

    $tool = resolve(AiGetMemory::class);

    $result = json_decode(
        $tool->handle(new Request(['memory_id' => $memory->id])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and($result['memory']['id'])->toBe($memory->id)
        ->and($result['memory']['content'])->toBe('fact');
});

it('AiGetMemory returns success=false when memory is missing', function (): void {
    $this->actingAs(User::factory()->create());

    $result = json_decode(
        resolve(AiGetMemory::class)->handle(new Request(['memory_id' => '01XXXXXXXXXXXXXXXXXXXXXXXX'])),
        true,
    );

    expect($result['success'])->toBeFalse()
        ->and($result)->toHaveKey('error');
});

it('AiUpdateMemory pins a memory', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create(['is_pinned' => false]);

    $result = json_decode(
        resolve(AiUpdateMemory::class)->handle(new Request([
            'memory_id' => $memory->id,
            'is_pinned' => true,
        ])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and($memory->refresh()->is_pinned)->toBeTrue();
});

it('AiDeleteMemory soft-deletes the row', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $memory = MemoryModel::factory()->for($user)->create();

    $result = json_decode(
        resolve(AiDeleteMemory::class)->handle(new Request(['memory_id' => $memory->id])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and(MemoryModel::query()->find($memory->id))->toBeNull();
});

it('AiGetImportantMemories filters by threshold', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    MemoryModel::factory()->for($user)->create(['importance' => 9, 'content' => 'critical']);
    MemoryModel::factory()->for($user)->create(['importance' => 3, 'content' => 'trivial']);

    $result = json_decode(
        resolve(AiGetImportantMemories::class)->handle(new Request([
            'threshold' => 8,
            'limit' => 10,
        ])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and(collect($result['memories'])->pluck('content')->all())->toBe(['critical']);
});

it('AiLinkMemories creates links between two memories', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $a = MemoryModel::factory()->for($user)->create();
    $b = MemoryModel::factory()->for($user)->create();

    $result = json_decode(
        resolve(AiLinkMemories::class)->handle(new Request([
            'memory_ids' => [$a->id, $b->id],
            'relationship' => 'related',
            'bidirectional' => true,
        ])),
        true,
    );

    expect($result['success'])->toBeTrue()
        ->and($a->outgoingLinks()->count() + $a->incomingLinks()->count())->toBeGreaterThan(0);
});
