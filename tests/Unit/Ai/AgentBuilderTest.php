<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Ai\Agents\FitnessSpecialist;
use App\Ai\Agents\HealthSpecialist;
use App\Ai\Agents\MealPlanSpecialist;
use App\Ai\Agents\NutritionSpecialist;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\GetCalorieLevelGuideline;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\LogHealthEntry;
use App\Ai\Tools\StartMealPlanGeneration;
use App\Ai\Tools\SuggestMeal;
use App\Enums\ModelName;
use App\Models\User;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Providers\Tools\WebSearch;

covers(AgentBuilder::class);

beforeEach(function (): void {
    $this->builder = resolve(AgentBuilder::class);
});

describe('build', function (): void {
    it('returns instructions and tools array', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result)
            ->toHaveKey('instructions')
            ->toHaveKey('tools')
            ->and($result['tools'])->toBeArray();
    });

    it('does not include raw profile context in instructions', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->toContain('You are Altani')
            ->not->toContain('USER PROFILE DATA');
    });

    it('instructs the agent to fetch profile context with the profile tool before personalizing advice', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'How much protein should I eat?',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->toContain('call `get_user_profile`')
            ->toContain('smallest relevant section')
            ->toContain('Use `all` only when a request spans multiple profile areas');
    });

    it('instructs the orchestrator to delegate to each specialist', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->toContain('meal_plan_specialist')
            ->toContain('nutrition_specialist')
            ->toContain('health_specialist')
            ->toContain('fitness_specialist');
    });

    it('states the specialist failure-handling rule exactly once', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect(mb_substr_count((string) $result['instructions'], 'treat it as a failed tool'))->toBe(1);
    });

    it('keeps durable writes with the orchestrator and delegates meal plans', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->toContain('log_health_entry')
            ->toContain('update_user_biometrics')
            ->toContain('update_user_profile_attributes')
            ->toContain('update_household_context')
            ->toContain('meal_plan_specialist');
    });

    it('instructs the orchestrator to inline profile context into the delegated task', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->toContain('Specialists have no access to `get_user_profile`')
            ->toContain('inline those facts verbatim into the `task`');
    });

    it('does not claim a delegated domain in its own expertise banner', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->not->toContain('glucose impact prediction')
            ->toContain('health_specialist');
    });

    it('does not include chat mode instructions', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])
            ->not->toContain('CHAT MODE:')
            ->not->toContain('Create Meal Plan mode');
    });
});

describe('tools', function (): void {
    it('returns top-level tools plus specialist sub-agents', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetUserProfile::class)
            ->toContain(LogHealthEntry::class)
            ->toContain(MealPlanSpecialist::class)
            ->toContain(NutritionSpecialist::class)
            ->toContain(HealthSpecialist::class)
            ->toContain(FitnessSpecialist::class)
            ->not->toContain(StartMealPlanGeneration::class)
            ->not->toContain(SuggestMeal::class);
    });

    it('includes image tools when attachments present', function (): void {
        $user = User::factory()->create();
        $image = new Base64Image(base64_encode('fake-image'), 'image/jpeg');
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Analyze this',
            images: [$image],
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(AnalyzePhoto::class);
    });

    it('excludes WebSearch when the toolset contains a Sensitive tool', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Search for something',
            modelName: ModelName::GPT_5_MINI,
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetUserProfile::class)
            ->not->toContain(WebSearch::class);
    });

    it('excludes WebSearch when a registered sub-agent can reach a Sensitive tool', function (): void {
        config()->set('plate.tools', [GetCalorieLevelGuideline::class]);

        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'What are calorie recommendations?',
            modelName: ModelName::GPT_5_MINI,
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetCalorieLevelGuideline::class)
            ->toContain(NutritionSpecialist::class)
            ->not->toContain(WebSearch::class);
    });

    it('includes WebSearch when no Sensitive tool is reachable, even via sub-agents', function (): void {
        config()->set('plate.tools', [GetCalorieLevelGuideline::class]);
        config()->set('plate.sub_agents', []);

        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'What are calorie recommendations?',
            modelName: ModelName::GPT_5_MINI,
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)
            ->toContain(GetCalorieLevelGuideline::class)
            ->toContain(WebSearch::class);
    });
});

it('handles null user gracefully', function (): void {
    $payload = new AgentPayload(
        userId: 1,
        message: 'Hello',
    );

    $result = $this->builder->build($payload, null);

    expect($result['instructions'])
        ->toContain('You are Altani')
        ->not->toContain('No user context available')
        ->not->toContain('USER PROFILE DATA');
});
