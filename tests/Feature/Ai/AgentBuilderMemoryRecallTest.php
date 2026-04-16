<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Contracts\Ai\Memory\GeneratesMemoryQueries;
use App\Enums\AgentMode;
use App\Models\Conversation;
use App\Models\History;
use App\Models\Memory as MemoryModel;
use App\Models\User;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Messages\MessageRole;

covers(AgentBuilder::class);

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
    config()->set('memory.retrieval.similarity_threshold', 0.3);
    config()->set('memory.retrieval.max_results', 3);
    config()->set('memory.retrieval.context_turns', 5);
});

function stubQueryAgentForAgentBuilder(array $queries): void
{
    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldReceive('generateQueries')->andReturn($queries);
    app()->instance(GeneratesMemoryQueries::class, $mock);
}

it('injects recalled memories into the assistant instructions', function (): void {
    $user = User::factory()->create();
    $vector = [1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0];

    stubQueryAgentForAgentBuilder(['dairy preference']);
    Embeddings::fake(fn (): array => [$vector]);

    MemoryModel::factory()
        ->for($user)
        ->withVector($vector)
        ->withCategories(['health', 'preference'])
        ->create(['content' => 'User is lactose intolerant']);

    $payload = new AgentPayload(
        userId: $user->id,
        message: 'what should I drink with dinner?',
        mode: AgentMode::Ask,
    );

    $result = resolve(AgentBuilder::class)->build($payload, $user);

    expect($result['instructions'])
        ->toContain('# RECALLED MEMORIES')
        ->and($result['instructions'])->toContain('User is lactose intolerant')
        ->and($result['instructions'])->toContain('[health, preference]');
});

it('omits the recalled memories block when no memories match', function (): void {
    $user = User::factory()->create();

    stubQueryAgentForAgentBuilder(['anything']);
    Embeddings::fake(fn (): array => [[1.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0]]);

    $payload = new AgentPayload(
        userId: $user->id,
        message: 'hello',
        mode: AgentMode::Ask,
    );

    $result = resolve(AgentBuilder::class)->build($payload, $user);

    expect($result['instructions'])->not->toContain('# RECALLED MEMORIES');
});

it('passes the recent conversation tail to the memory retriever', function (): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();

    History::factory()->forConversation($conversation)->userMessage()->create([
        'content' => 'I am allergic to shellfish',
        'created_at' => now()->subMinutes(3),
    ]);
    History::factory()->forConversation($conversation)->assistantMessage()->create([
        'content' => 'Noted — I will avoid shellfish in suggestions',
        'created_at' => now()->subMinutes(2),
    ]);

    $captured = [];
    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldReceive('generateQueries')
        ->andReturnUsing(function (string $context) use (&$captured): array {
            $captured[] = $context;

            return [];
        });
    app()->instance(GeneratesMemoryQueries::class, $mock);

    $payload = new AgentPayload(
        userId: $user->id,
        message: 'what should I eat tonight?',
        mode: AgentMode::Ask,
        conversationId: $conversation->id,
    );

    resolve(AgentBuilder::class)->build($payload, $user);

    expect($captured)->toHaveCount(1)
        ->and($captured[0])->toContain('I am allergic to shellfish')
        ->and($captured[0])->toContain('Noted — I will avoid shellfish in suggestions')
        ->and($captured[0])->toContain(MessageRole::User->value.': what should I eat tonight?');
});
