<?php

declare(strict_types=1);

use App\Ai\Agents\FitnessAgent;
use App\Ai\Agents\HealthAgent;
use App\Ai\Agents\NutritionAgent;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetDailyServingsByCalorie;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthData;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetHealthSummary;
use App\Ai\Tools\GetHealthSyncSupport;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Ai\Tools\SuggestSingleMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;

covers(NutritionAgent::class, HealthAgent::class, FitnessAgent::class);

dataset('specialists', [
    'nutrition' => [
        NutritionAgent::class,
        'nutrition_specialist',
        [
            SuggestSingleMeal::class,
            GetDietReference::class,
            GetCalorieLevelGuideline::class,
            GetDailyServingsByCalorie::class,
        ],
    ],
    'health' => [
        HealthAgent::class,
        'health_specialist',
        [
            GetHealthData::class,
            GetHealthSummary::class,
            GetHealthGoals::class,
            GetHealthSyncSupport::class,
            PredictGlucoseSpike::class,
        ],
    ],
    'fitness' => [
        FitnessAgent::class,
        'fitness_specialist',
        [
            SuggestWorkoutRoutine::class,
            SuggestWellnessRoutine::class,
            GetFitnessGoals::class,
        ],
    ],
]);

it('exposes itself to the orchestrator as a named delegatable tool', function (string $class, string $name): void {
    $agent = resolve($class);

    expect($agent)
        ->toBeInstanceOf(Agent::class)
        ->toBeInstanceOf(CanActAsTool::class)
        ->toBeInstanceOf(HasTools::class)
        ->and($agent->name())->toBe($name)
        ->and($agent->description())->toBeString()->not->toBe('');
})->with('specialists');

it('owns exactly its configured domain tools', function (string $class, string $name, array $expectedTools): void {
    $toolClasses = collect(resolve($class)->tools())
        ->map(fn (mixed $tool): string => $tool::class)
        ->all();

    expect($toolClasses)->toEqualCanonicalizing($expectedTools);
})->with('specialists');

it('configures a request timeout', function (string $class): void {
    $timeout = new ReflectionClass($class)->getAttributes(Timeout::class);

    expect($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(120);
})->with('specialists');

it('renders language-aware instructions for the authenticated user', function (string $class): void {
    $user = User::factory()->create(['locale' => 'mn']);
    $this->actingAs($user);

    ['label' => $label, 'code' => $code] = LanguageUtil::resolve('mn');

    expect(resolve($class)->instructions())
        ->toContain($label)
        ->toContain(sprintf('(%s)', $code));
})->with('specialists');

it('can be faked and prompted directly as an isolated sub-agent', function (string $class): void {
    $class::fake(['Specialist response.']);

    $response = resolve($class)->prompt('Handle this delegated task.');

    expect($response->text)->toBe('Specialist response.');

    $class::assertPrompted('Handle this delegated task.');
})->with('specialists');
