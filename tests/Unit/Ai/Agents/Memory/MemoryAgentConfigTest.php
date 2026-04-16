<?php

declare(strict_types=1);

use App\Ai\Agents\Memory\Concerns\UsesMemoryAgentConfig;
use App\Ai\Agents\Memory\MemoryCategorizerAgent;
use App\Ai\Agents\Memory\MemoryExtractorAgent;
use App\Ai\Agents\Memory\MemoryMergeDeciderAgent;
use App\Ai\Agents\Memory\MemoryQueryGeneratorAgent;
use App\Ai\Agents\Memory\MemoryReflectorAgent;
use App\Ai\Agents\Memory\MemoryValidatorAgent;

dataset('memory_agents', [
    MemoryCategorizerAgent::class,
    MemoryExtractorAgent::class,
    MemoryMergeDeciderAgent::class,
    MemoryQueryGeneratorAgent::class,
    MemoryReflectorAgent::class,
    MemoryValidatorAgent::class,
]);

it('falls back to gemini provider and no explicit model when config is unset', function (string $agentClass): void {
    config()->set('memory.ai_agent.provider');
    config()->set('memory.ai_agent.model');

    /** @var object&UsesMemoryAgentConfig $agent */
    $agent = resolve($agentClass);

    expect($agent->provider())->toBe('gemini')
        ->and($agent->model())->toBeNull();
})->with('memory_agents');

it('uses the configured provider and model when memory.ai_agent config is set', function (string $agentClass): void {
    config()->set('memory.ai_agent.provider', 'openai');
    config()->set('memory.ai_agent.model', 'gpt-5-mini');

    /** @var object&UsesMemoryAgentConfig $agent */
    $agent = resolve($agentClass);

    expect($agent->provider())->toBe('openai')
        ->and($agent->model())->toBe('gpt-5-mini');
})->with('memory_agents');

it('treats empty-string config values as unset', function (string $agentClass): void {
    config()->set('memory.ai_agent.provider', '');
    config()->set('memory.ai_agent.model', '');

    /** @var object&UsesMemoryAgentConfig $agent */
    $agent = resolve($agentClass);

    expect($agent->provider())->toBe('gemini')
        ->and($agent->model())->toBeNull();
})->with('memory_agents');
