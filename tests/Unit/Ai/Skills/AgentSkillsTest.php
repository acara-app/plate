<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use App\Services\ToolRegistry;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Tools\AgentTool;
use Laravel\Ai\Tools\LoadSkill;
use Laravel\Ai\Tools\Request;
use Laravel\Ai\Tools\ToolNameResolver;
use Symfony\Component\Yaml\Yaml;

covers(AgentRunner::class);

it('keeps every skill under resources/skills valid as an Agent Skill and lists it for the agent', function (): void {
    $folders = array_map(basename(...), glob(resource_path('skills/*'), GLOB_ONLYDIR) ?: []);
    $listed = (string) collect(LoadSkill::mergeInto([], resolve(AgentRunner::class)))->sole()->description();

    expect($folders)->toBe(['nutrition-analyzer', 'weightloss-analyzer'])
        ->each->toMatch('/^[a-z0-9]+(-[a-z0-9]+)*$/');

    foreach ($folders as $folder) {
        $skill = Skill::fromDirectory(resource_path("skills/{$folder}"));

        expect($skill)->toBeInstanceOf(Skill::class)
            ->and($skill->name)->toBe($folder)
            ->and(mb_strlen($skill->name))->toBeLessThanOrEqual(64)
            ->and(mb_strlen((string) $skill->description))->toBeLessThanOrEqual(1024)
            ->and(mb_trim((string) $skill->instructions))->not->toBe('')
            ->and($listed)->toContain("- {$skill->name}: ");
    }
});

it("loads a skill's instructions with its licence as the only bundled file", function (string $name): void {
    $loader = collect(LoadSkill::mergeInto([], resolve(AgentRunner::class)))->sole();

    expect((string) $loader->handle(new Request(['name' => $name])))
        ->toStartWith("<skill_content name=\"{$name}\">")
        ->toContain("<skill_resources>\nLICENSE.md\n</skill_resources>");
})->with(['nutrition-analyzer', 'weightloss-analyzer']);

it('names only tools the agent can call itself or reach through a specialist', function (): void {
    $registry = resolve(ToolRegistry::class);
    $specialists = $registry->getSubAgents();
    $names = static fn (array $tools): array => array_map(ToolNameResolver::resolve(...), $tools);

    $direct = [
        ...$names($registry->getTools()),
        ...$names($registry->getImageTools([])),
        ...$names(array_map(static fn (Agent $specialist): AgentTool => new AgentTool($specialist), $specialists)),
    ];
    $reachable = [
        ...$direct,
        ...collect($specialists)->flatMap(static fn (Agent $specialist): array => $specialist instanceof HasTools ? $names([...$specialist->tools()]) : [])->all(),
    ];

    foreach (glob(resource_path('skills/*/SKILL.md')) ?: [] as $file) {
        preg_match('/\A---\R(.*?)\R---\R?(.*)\z/s', (string) file_get_contents($file), $parts);
        preg_match_all('/`([a-z]+(?:_[a-z]+)+)`/', $parts[2], $named);

        expect(explode(' ', (string) Yaml::parse($parts[1])['allowed-tools']))->each->toBeIn($direct)
            ->and(array_values(array_diff(array_unique($named[1]), ['dietary_preferences'])))->each->toBeIn($reachable);
    }
});
