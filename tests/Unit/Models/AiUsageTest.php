<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Models\AiUsage;

covers(AiUsage::class);

it('calculates total tokens correctly', function (): void {
    $aiUsage = new AiUsage([
        'user_id' => 1,
        'agent' => 'TestAgent',
        'model' => 'gemini-3-flash-preview',
        'provider' => 'gemini',
        'prompt_tokens' => 1000,
        'completion_tokens' => 500,
        'cache_read_input_tokens' => 200,
        'reasoning_tokens' => 100,
        'cost' => 0.01,
    ]);

    expect($aiUsage->totalTokens())->toBe(1800);
});

it('belongs to user', function (): void {
    $aiUsage = new AiUsage;
    $relation = $aiUsage->user();

    expect($relation->getForeignKeyName())->toBe('user_id');
});

it('has correct table name', function (): void {
    $aiUsage = new AiUsage;
    expect($aiUsage->getTable())->toBe('ai_usages');
});

it('casts prompt_tokens to integer', function (): void {
    $aiUsage = new AiUsage;
    $aiUsage->prompt_tokens = '1000';

    expect($aiUsage->prompt_tokens)->toBeInt();
});

it('filters usage rows to a single agent FQCN', function (): void {
    AiUsage::factory()->create(['agent' => NutritionSpecialist::class]);
    AiUsage::factory()->create(['agent' => HealthSpecialist::class]);
    AiUsage::factory()->create(['agent' => AgentRunner::class]);

    expect(AiUsage::query()->byAgent(NutritionSpecialist::class)->count())->toBe(1)
        ->and(AiUsage::query()->byAgent(AgentRunner::class)->count())->toBe(1);
});

it('filters usage rows to delegated specialists only', function (): void {
    AiUsage::factory()->create(['agent' => NutritionSpecialist::class]);
    AiUsage::factory()->create(['agent' => HealthSpecialist::class]);
    AiUsage::factory()->create(['agent' => AgentRunner::class]);

    expect(AiUsage::query()->specialist()->count())->toBe(2);
});
