<?php

declare(strict_types=1);

use App\Actions\GetUserProfileContextAction;
use App\Ai\Agents\AssistantAgent;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\EnrichAttributeMetadata;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetDailyServingsByCalorie;
use App\Ai\Tools\GetDietReference;
use App\Ai\Tools\GetFitnessGoals;
use App\Ai\Tools\GetHealthEntries;
use App\Ai\Tools\GetHealthGoals;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\PredictGlucoseSpike;
use App\Ai\Tools\SuggestSingleMeal;
use App\Ai\Tools\SuggestWellnessRoutine;
use App\Ai\Tools\SuggestWorkoutRoutine;
use App\Ai\Tools\UpdateUserProfileAttributes;
use App\Enums\AgentMode;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\User;
use App\Services\ToolRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Files\Base64Image;

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
    $this->toolRegistry = resolve(ToolRegistry::class);

    $this->agent = new AssistantAgent(
        $this->user,
        $this->profileContext,
        $this->toolRegistry,
    );
});

it('returns instructions with default mode', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('You are Altani, a comprehensive AI wellness assistant')
        ->toContain('CHAT MODE: ask')
        ->toContain('USER PROFILE CONTEXT:')
        ->toContain('BIOMETRICS:');
});

it('returns instructions with CreateMealPlan mode', function (): void {
    $this->agent->withMode(AgentMode::CreateMealPlan);
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('You are Altani, a comprehensive AI wellness assistant')
        ->toContain('CHAT MODE: create-meal-plan')
        ->toContain('The user has explicitly selected "Create Meal Plan" mode')
        ->toContain('Use the create_meal_plan tool');
});

it('returns correct tools', function (): void {
    $tools = $this->agent->tools();

    $toolClasses = collect($tools)
        ->map(fn (mixed $t): string => $t::class)
        ->all();

    expect($toolClasses)->toContain(SuggestSingleMeal::class)
        ->toContain(GetUserProfile::class)
        ->toContain(CreateMealPlan::class)
        ->toContain(GetCalorieLevelGuideline::class)
        ->toContain(GetDailyServingsByCalorie::class)
        ->toContain(PredictGlucoseSpike::class)
        ->toContain(SuggestWellnessRoutine::class)
        ->toContain(GetHealthGoals::class)
        ->toContain(GetHealthEntries::class)
        ->toContain(LogHealthEntry::class)
        ->toContain(SuggestWorkoutRoutine::class)
        ->toContain(GetFitnessGoals::class)
        ->toContain(GetDietReference::class)
        ->toContain(EnrichAttributeMetadata::class)
        ->toContain(UpdateUserProfileAttributes::class);
});

it('returns empty messages when no conversation', function (): void {
    $messages = $this->agent->messages();

    expect($messages)->toBeArray()
        ->toHaveCount(0);
});

it('adds a custom tool via addTool()', function (): void {
    $customTool = new AnalyzePhoto([]);

    $result = $this->agent->addTool($customTool);

    // addTool() is fluent — it returns $this
    expect($result)->toBe($this->agent);

    // The custom tool should now appear in the tools list
    $toolClasses = collect($this->agent->tools())
        ->map(fn (mixed $t): string => $t::class)
        ->all();

    expect($toolClasses)->toContain(AnalyzePhoto::class);
});

it('includes AnalyzePhoto tool when attachments are set', function (): void {
    $image = new Base64Image(base64_encode('fake-image'), 'image/jpeg');

    $this->agent->withAttachments([$image]);

    $tools = $this->agent->tools();

    $toolClasses = collect($tools)
        ->map(fn (mixed $t): string => $t::class)
        ->all();

    expect($toolClasses)->toContain(AnalyzePhoto::class);
});
