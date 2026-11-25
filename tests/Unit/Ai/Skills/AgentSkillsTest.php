<?php

declare(strict_types=1);

use App\Ai\Agents\AgentRunner;
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Tools\LoadSkill;
use Laravel\Ai\Tools\Request;

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
