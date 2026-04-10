<?php

declare(strict_types=1);

use App\Ai\AgentBuilder;
use App\Ai\AgentPayload;
use App\Ai\Tools\AnalyzePhoto;
use App\Ai\Tools\CreateMealPlan;
use App\Ai\Tools\GetUserProfile;
use App\Ai\Tools\SuggestSingleMeal;
use App\Enums\AgentMode;
use App\Enums\ModelName;
use App\Models\User;
use App\Services\ToolRegistry;
use Laravel\Ai\Files\Base64Image;

covers(AgentBuilder::class);

beforeEach(function (): void {
    $this->toolRegistry = resolve(ToolRegistry::class);
    $this->builder = new AgentBuilder($this->toolRegistry);
});

describe('build', function (): void {
    it('returns instructions and tools array', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
            mode: AgentMode::Ask,
        );

        $result = $this->builder->build($payload, $user);

        expect($result)
            ->toHaveKey('instructions')
            ->toHaveKey('tools')
            ->and($result['tools'])->toBeArray();
    });

    it('includes profile context in instructions', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
            mode: AgentMode::Ask,
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])->toContain('You are Altani');
    });

    it('includes chat mode in instructions', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
            mode: AgentMode::CreateMealPlan,
        );

        $result = $this->builder->build($payload, $user);

        expect($result['instructions'])->toContain('CHAT MODE: create-meal-plan');
    });
});

describe('tools', function (): void {
    it('returns base tools', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Hello',
            mode: AgentMode::Ask,
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(SuggestSingleMeal::class)
            ->toContain(GetUserProfile::class)
            ->toContain(CreateMealPlan::class);
    });

    it('includes image tools when attachments present', function (): void {
        $user = User::factory()->create();
        $image = new Base64Image(base64_encode('fake-image'), 'image/jpeg');
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Analyze this',
            images: [$image],
            mode: AgentMode::Ask,
        );

        $result = $this->builder->build($payload, $user);

        $toolClasses = collect($result['tools'])
            ->map(fn (mixed $t): string => $t::class)
            ->all();

        expect($toolClasses)->toContain(AnalyzePhoto::class);
    });

    it('includes provider tools when web search enabled', function (): void {
        $user = User::factory()->create();
        $payload = new AgentPayload(
            userId: $user->id,
            message: 'Search for something',
            mode: AgentMode::Ask,
            modelName: ModelName::GPT_5_MINI,
        );

        $result = $this->builder->build($payload, $user);

        expect($result['tools'])->not->toBeEmpty();
    });
});

it('handles null user gracefully', function (): void {
    $payload = new AgentPayload(
        userId: 1,
        message: 'Hello',
        mode: AgentMode::Ask,
    );

    $result = $this->builder->build($payload, null);

    expect($result['instructions'])->toContain('No user context available');
});
