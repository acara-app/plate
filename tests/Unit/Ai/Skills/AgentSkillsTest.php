<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use App\Services\ToolRegistry;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Tools\AgentTool;
use Laravel\Ai\Tools\LoadSkill;
use Laravel\Ai\Tools\ToolNameResolver;

covers(AgentRunner::class);

it('offers every skill under resources/skills through LoadSkill', function (): void {
    $folders = array_map(basename(...), glob(resource_path('skills/*'), GLOB_ONLYDIR));
    $catalog = (string) collect(LoadSkill::mergeInto([], resolve(AgentRunner::class)))->sole()->description();

    expect($folders)->toBe(['nutrition-analyzer', 'weightloss-analyzer'])
        ->and($catalog)->toContain('- nutrition-analyzer: ', '- weightloss-analyzer: ');
});

it('names only tools that exist in Plate', function (): void {
    $registry = resolve(ToolRegistry::class);
    $specialists = $registry->getSubAgents();
    $tools = [
        ...$registry->getTools(),
        ...$registry->getImageTools([]),
        ...array_map(fn (Agent $specialist): AgentTool => new AgentTool($specialist), $specialists),
        ...collect($specialists)->flatMap(fn (Agent $specialist): array => $specialist instanceof HasTools ? [...$specialist->tools()] : []),
    ];
    $names = array_map(ToolNameResolver::resolve(...), $tools);

    foreach (glob(resource_path('skills/*/SKILL.md')) as $skill) {
        preg_match_all('/`([a-z]+(?:_[a-z]+)+)`/', (string) file_get_contents($skill), $named);

        expect(array_diff($named[1], $names, ['dietary_preferences']))->toBe([]);
    }
});
