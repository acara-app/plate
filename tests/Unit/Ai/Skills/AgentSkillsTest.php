<?php

declare(strict_types=1);

use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ToolRegistry;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Responses\Data\ToolCall;
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

it('loads report examples separately from the skill instructions', function (string $skill, string $reportHeading): void {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->forUser($user)->create();
    AgentRunner::fake([
        new ToolCall('read-skill', 'LoadSkill', ['name' => $skill]),
        new ToolCall('read-reports', 'LoadSkill', ['name' => $skill, 'path' => 'report-examples.md']),
        'Report ready.',
    ])->preventStrayPrompts();

    $response = resolve(AgentRunner::class)->runSync(new AgentRequest(
        message: 'Prepare my report.',
        conversationId: $conversation->id,
    ), $user);
    $results = $response->toolResults->pluck('result', 'id');

    expect($results->get('read-skill'))->toContain('## Safety Guardrails', 'report-examples.md')->not->toContain($reportHeading);
    expect($results->get('read-reports'))->toContain($reportHeading);
})->with([
    ['nutrition-analyzer', '# Nutrition Intake Trend Review'],
    ['weightloss-analyzer', '# Weight Management Phase Review'],
]);

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
