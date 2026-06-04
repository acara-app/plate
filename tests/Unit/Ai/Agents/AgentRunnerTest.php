<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\GlucoseSpikeSpecialist;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
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
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('call `get_user_profile`');
    });

    it('returns instructions that delegate meal planning to a specialist', function (): void {
        $request = new AgentRequest(message: 'Create a meal plan', modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $instructions = $this->agent->instructions();

        expect($instructions)
            ->toContain('You are Altani, a comprehensive AI wellness assistant')
            ->toContain('meal_plan_specialist');
    });
});

describe('tools', function (): void {
    it('returns top-level tools plus specialist sub-agents', function (): void {
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetUserProfile::class)
            ->toContain(LogHealthEntry::class)
            ->toContain(MealPlanSpecialist::class)
            ->toContain(NutritionSpecialist::class)
            ->toContain(GlucoseSpikeSpecialist::class)
            ->toContain(HealthSpecialist::class)
            ->toContain(FitnessSpecialist::class);
    });

    it('includes AnalyzePhoto tool when attachments are set', function (): void {
        $image = new Base64Image(base64_encode('fake-image'), 'image/jpeg');
        $request = new AgentRequest(message: 'Analyze this', images: [$image], modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        $toolClasses = collect($tools)
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(AnalyzePhoto::class);
    });

    it('includes provider tools when web search enabled', function (): void {
        $request = new AgentRequest(message: 'Search for something', modelName: ModelName::GPT_5_MINI);

        $this->agent->run($request, $this->user);
        $tools = $this->agent->tools();

        expect($tools)->not->toBeEmpty();
    });
});

describe('messages', function (): void {
    it('returns empty messages when no conversation', function (): void {
        $request = new AgentRequest(message: 'Hello', modelName: ModelName::GPT_5_4_MINI);

        $this->agent->run($request, $this->user);
        $messages = $this->agent->messages();

        expect($messages)->toBeArray()
            ->toHaveCount(0);
    });
});
