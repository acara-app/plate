<?php

declare(strict_types=1);

use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Enums\DietType;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ToolRegistry;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Tools\AgentTool;
use Laravel\Ai\Tools\LoadSkill;
use Laravel\Ai\Tools\ToolNameResolver;
use Tests\Helpers\TestJsonSchema;

covers(AgentRunner::class, MealPlanSpecialist::class, NutritionSpecialist::class);

it('offers only the skills assigned to each agent', function (string $agent, array $names): void {
    $loader = collect(LoadSkill::mergeInto([], resolve($agent)))->sole();

    expect($loader->schema(new TestJsonSchema)['name']->toArray()['enum'])->toEqualCanonicalizing($names);
})->with(function (): array {
    $diets = array_map(fn (DietType $diet): string => $diet->skillName(), DietType::cases());

    return [
        'main chat' => [AgentRunner::class, ['nutrition-analyzer', 'weightloss-analyzer']],
        'meal planning' => [MealPlanSpecialist::class, $diets],
        'nutrition' => [NutritionSpecialist::class, [...$diets, 'usda-nutrition']],
    ];
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

it('reads the requested USDA reference through the nutrition specialist', function (string $file, string $heading): void {
    NutritionSpecialist::fake([
        new ToolCall('guide', 'LoadSkill', ['name' => 'usda-nutrition']),
        new ToolCall('reference', 'LoadSkill', ['name' => 'usda-nutrition', 'path' => $file]),
        'Reference retrieved.',
    ])->preventStrayPrompts();

    $response = resolve(NutritionSpecialist::class)->prompt('Look up the requested USDA reference.');
    $results = $response->toolResults->pluck('result', 'id');

    expect($results->get('guide'))->toContain($file);
    expect($results->get('reference'))->toContain($heading);
})->with([
    ['references/dietary-guidelines-usda.md', '# Dietary Guidelines for Americans'],
    ['references/daily-servings-by-calorie-level-usda.md', '# Daily Servings by Calorie Level'],
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
