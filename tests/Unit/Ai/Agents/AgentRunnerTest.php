<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\FitnessAgent;
use App\Ai\Agents\HealthAgent;
use App\Ai\Agents\NutritionAgent;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\SuggestSingleMeal;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Models\User;
use Laravel\Ai\Files\Base64Image;

covers(AgentRunner::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->agentBuilder = resolve(AgentBuilder::class);
    $this->agent = resolve(AgentRunner::class);
});

describe('instructions', function (): void {
    it('returns instructions with default mode', function (): void {
        $payload = new AgentPayload(
            userId: $this->user->id,
            message: 'Hello',
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_4_MINI,
        );

        $this->agent->run($payload, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('CHAT MODE: ask')
            ->toContain('call `get_user_profile`');
    });

    it('returns instructions with CreateMealPlan mode', function (): void {
        $payload = new AgentPayload(
            userId: $this->user->id,
            message: 'Create a meal plan',
            mode: AgentMode::CreateMealPlan,
            modelName: ModelName::GPT_5_4_MINI,
        );

        $this->agent->run($payload, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('CHAT MODE: create-meal-plan')
            ->toContain('Create Meal Plan mode')
            ->toContain('create_meal_plan');
    });
});

describe('tools', function (): void {
    it('returns top-level tools plus specialist sub-agents', function (): void {
        $payload = new AgentPayload(
            userId: $this->user->id,
            message: 'Hello',
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_4_MINI,
        );

        $this->agent->run($payload, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetUserProfile::class)
            ->toContain(CreateMealPlan::class)
            ->toContain(LogHealthEntry::class)
            ->toContain(NutritionAgent::class)
            ->toContain(HealthAgent::class)
            ->toContain(FitnessAgent::class)
            ->not->toContain(SuggestSingleMeal::class);
    });

    it('includes AnalyzePhoto tool when attachments are set', function (): void {
        $image = new Base64Image(base64_encode('fake-image'), 'image/jpeg');
        $payload = new AgentPayload(
            userId: $this->user->id,
            message: 'Analyze this',
            images: [$image],
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_4_MINI,
        );

        $this->agent->run($payload, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(AnalyzePhoto::class);
    });

    it('includes provider tools when web search enabled', function (): void {
        $payload = new AgentPayload(
            userId: $this->user->id,
            message: 'Search for something',
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_MINI,
        );

        $this->agent->run($payload, $this->user);
        $tools = $this->agent->tools();

        expect($tools)->not->toBeEmpty();
    });
});

describe('messages', function (): void {
    it('returns empty messages when no conversation', function (): void {
        $payload = new AgentPayload(
            userId: $this->user->id,
            message: 'Hello',
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_4_MINI,
        );

        $this->agent->run($payload, $this->user);
        $messages = $this->agent->messages();

        expect($messages)->toBeArray()
            ->toHaveCount(0);
    });
});
