<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Contracts\Ai\Memory\GeneratesMemoryQueries;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Models\User;

covers(AgentRunner::class);
covers(AgentBuilder::class);

beforeEach(function (): void {
    config()->set('memory.embeddings.dimensions', 8);
});

it('recalls memory only once when both instructions() and tools() fire in the same turn', function (): void {
    $user = User::factory()->create();

    $queryGenerations = 0;
    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldReceive('generateQueries')
        ->andReturnUsing(function () use (&$queryGenerations): array {
            $queryGenerations++;

            return [];
        });
    app()->instance(GeneratesMemoryQueries::class, $mock);

    $agent = new AgentRunner(resolve(AgentBuilder::class));
    $payload = new AgentPayload(
        userId: $user->id,
        message: 'What should I have for dinner?',
        mode: AgentMode::Ask,
        modelName: ModelName::GPT_5_4_MINI,
    );

    $agent->run($payload, $user);

    $agent->instructions();
    $agent->tools();

    expect($queryGenerations)->toBe(1);
});

it('does not trigger memory recall from the tools() path', function (): void {
    $user = User::factory()->create();

    $mock = Mockery::mock(GeneratesMemoryQueries::class);
    $mock->shouldNotReceive('generateQueries');

    app()->instance(GeneratesMemoryQueries::class, $mock);

    $agent = new AgentRunner(resolve(AgentBuilder::class));
    $payload = new AgentPayload(
        userId: $user->id,
        message: 'anything',
        mode: AgentMode::Ask,
        modelName: ModelName::GPT_5_4_MINI,
    );

    $agent->run($payload, $user);

    $agent->tools();

    expect(true)->toBeTrue();
});
