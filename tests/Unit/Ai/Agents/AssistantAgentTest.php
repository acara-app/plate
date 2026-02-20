<?php

declare(strict_types=1);

use App\Actions\GetUserProfileContextAction;
use App\Ai\Agents\AssistantAgent;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Ai\Tools\SuggestSingleMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Contracts\Ai\GeneratesSingleMeals;
use App\Contracts\Ai\PredictsGlucoseSpikes;
use App\Enums\AgentMode;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\History;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $this->profileContext = new GetUserProfileContextAction;
    $this->suggestSingleMealTool = new SuggestSingleMeal(resolve(GeneratesSingleMeals::class));
    $this->getUserProfileTool = new GetUserProfile($this->profileContext);
    $this->createMealPlanTool = new CreateMealPlan(resolve(GeneratesMealPlans::class));
    $this->predictGlucoseSpikeTool = new PredictGlucoseSpike(resolve(PredictsGlucoseSpikes::class));
    $this->suggestWellnessRoutineTool = new SuggestWellnessRoutine;
    $this->getHealthGoalsTool = new GetHealthGoals($this->profileContext);
    $this->suggestWorkoutRoutineTool = new SuggestWorkoutRoutine;
    $this->getFitnessGoalsTool = new GetFitnessGoals($this->profileContext);

    $this->agent = new AssistantAgent(
        $this->user,
        $this->profileContext,
        $this->suggestSingleMealTool,
        $this->getUserProfileTool,
        $this->createMealPlanTool,
        $this->predictGlucoseSpikeTool,
        $this->suggestWellnessRoutineTool,
        $this->getHealthGoalsTool,
        $this->suggestWorkoutRoutineTool,
        $this->getFitnessGoalsTool,
    );
});

it('returns instructions with default mode', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('You are a comprehensive AI wellness assistant')
        ->toContain('CHAT MODE: ask')
        ->toContain('USER PROFILE CONTEXT:')
        ->toContain('BIOMETRICS:');
});

it('returns instructions with CreateMealPlan mode', function (): void {
    $this->agent->withMode(AgentMode::CreateMealPlan);
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('You are a comprehensive AI wellness assistant')
        ->toContain('CHAT MODE: create-meal-plan')
        ->toContain('The user has explicitly selected "Create Meal Plan" mode')
        ->toContain('Use the create_meal_plan tool');
});

it('returns correct tools', function (): void {
    $tools = $this->agent->tools();

    expect($tools)->toHaveCount(9)
        ->and($tools[0])->toBeInstanceOf(SuggestSingleMeal::class)
        ->and($tools[1])->toBeInstanceOf(GetUserProfile::class)
        ->and($tools[2])->toBeInstanceOf(CreateMealPlan::class)
        ->and($tools[3])->toBeInstanceOf(PredictGlucoseSpike::class)
        ->and($tools[4])->toBeInstanceOf(SuggestWellnessRoutine::class)
        ->and($tools[5])->toBeInstanceOf(GetHealthGoals::class)
        ->and($tools[6])->toBeInstanceOf(SuggestWorkoutRoutine::class)
        ->and($tools[7])->toBeInstanceOf(GetFitnessGoals::class)
        ->and($tools[8])->toBeInstanceOf(GetDietReference::class);
});

it('returns empty messages when no history', function (): void {
    $messages = $this->agent->messages();

    expect($messages)->toBeArray()
        ->toHaveCount(0);
});

it('returns messages from history', function (): void {
    History::factory()->create([
        'user_id' => $this->user->id,
        'role' => 'user',
        'content' => 'Hello, I need help with my diet',
    ]);

    History::factory()->create([
        'user_id' => $this->user->id,
        'role' => 'assistant',
        'content' => 'I can help you with that!',
    ]);

    $messages = $this->agent->messages();

    expect($messages)->toHaveCount(2);
    // Messages are returned in chronological order (oldest first)
    $userMessage = collect($messages)->first(fn ($m): bool => $m->content === 'Hello, I need help with my diet');
    $assistantMessage = collect($messages)->first(fn ($m): bool => $m->content === 'I can help you with that!');
    expect($userMessage)->not->toBeNull();
    expect($assistantMessage)->not->toBeNull();
});

it('limits messages to 50', function (): void {
    // Create 60 messages (only 50 should be returned)
    for ($i = 0; $i < 60; $i++) {
        History::factory()->create([
            'user_id' => $this->user->id,
            'role' => 'user',
            'content' => 'Message '.$i,
        ]);
    }

    $messages = $this->agent->messages();

    expect($messages)->toHaveCount(50);
});
