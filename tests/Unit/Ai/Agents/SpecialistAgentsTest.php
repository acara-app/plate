<?php

declare(strict_types=1);

use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetDailyServingsByCalorie;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthData;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetHealthSummary;
use App\Ai\Tools\GetHealthSyncSupport;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\StartMealPlanGeneration;
use App\Ai\Tools\SuggestMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Models\User;
use App\Utilities\LanguageUtil;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;

covers(NutritionSpecialist::class, GlucoseSpikeSpecialist::class, HealthSpecialist::class, FitnessSpecialist::class, MealPlanSpecialist::class);

dataset('specialists', [
    'meal plan' => [
        MealPlanSpecialist::class,
        'meal_plan_specialist',
        [
            StartMealPlanGeneration::class,
            GetDietReference::class,
        ],
    ],
    'nutrition' => [
        NutritionSpecialist::class,
        'nutrition_specialist',
        [
            SuggestMeal::class,
            GetDietReference::class,
            GetCalorieLevelGuideline::class,
            GetDailyServingsByCalorie::class,
        ],
    ],
    'health' => [
        HealthSpecialist::class,
        'health_specialist',
        [
            GetHealthData::class,
            GetHealthSummary::class,
            GetHealthGoals::class,
            GetHealthSyncSupport::class,
        ],
    ],
    'fitness' => [
        FitnessSpecialist::class,
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
        ->and($agent->name())->toBe($name)
        ->and($agent->description())->toBeString()->not->toBe('');
})->with('specialists');

it('owns exactly its configured domain tools', function (string $class, string $name, array $expectedTools): void {
    $toolClasses = collect(resolve($class)->tools())
        ->map(fn (mixed $tool): string => $tool::class)
        ->all();

    expect($toolClasses)->toEqualCanonicalizing($expectedTools);
})->with('specialists');

it('declares the cross-agent diet reference tool once in the shared group', function (): void {
    expect(config()->array('plate.shared_tools', []))->toContain(GetDietReference::class);
});

it('pulls the shared tool group only for specialists that opt in', function (): void {
    config()->set('plate.shared_tools', [GetUserProfile::class]);

    $tools = fn (string $class): array => collect(resolve($class)->tools())
        ->map(fn (mixed $tool): string => $tool::class)
        ->all();

    expect($tools(NutritionSpecialist::class))->toContain(GetUserProfile::class)
        ->and($tools(MealPlanSpecialist::class))->toContain(GetUserProfile::class);
});

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

dataset('specialist scope keywords', [
    'glucose spike' => [GlucoseSpikeSpecialist::class, ['blood sugar spike questions and worries', 'structured glucose spike risk result']],
    'meal plan' => [MealPlanSpecialist::class, ['meal plan', 'day count']],
    'nutrition' => [NutritionSpecialist::class, ['single meals', 'meal_plan_specialist']],
    'health' => [HealthSpecialist::class, ['health data', 'Health Sync']],
    'fitness' => [FitnessSpecialist::class, ['workout', 'wellness']],
]);

it('keeps its routing scope and isolation marker in the description as the single source of truth', function (string $class, array $keywords): void {
    $description = resolve($class)->description();

    expect($description)->toContain('cannot see the chat history');

    foreach ($keywords as $keyword) {
        expect($description)->toContain($keyword);
    }
})->with('specialist scope keywords');
