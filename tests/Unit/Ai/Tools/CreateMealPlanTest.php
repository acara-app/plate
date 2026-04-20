<?php

declare(strict_types=1);

use App\Ai\Tools\CreateMealPlan;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(CreateMealPlan::class);

beforeEach(function (): void {
    $this->agent = new class implements GeneratesMealPlans
    {
        public ?Exception $exception = null;

        public array $calls = [];

        public function handle(User $user, int $totalDays = 7, ?string $customPrompt = null): void
        {
            $this->calls[] = ['user' => $user, 'totalDays' => $totalDays, 'customPrompt' => $customPrompt];

            if ($this->exception instanceof Exception) {
                throw $this->exception;
            }
        }
    };

    app()->instance(GeneratesMealPlans::class, $this->agent);
    $this->tool = new CreateMealPlan();
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('create_meal_plan')
        ->and($this->tool->description())->toContain('Generate a complete multi-day meal plan');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['total_days', 'custom_prompt']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['total_days' => 3]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('generates meal plan successfully', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['total_days' => 3]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['total_days'])->toBe(3)
        ->and($json['requested_days'])->toBe(3)
        ->and($json['was_capped'])->toBeFalse()
        ->and($json['max_allowed_days'])->toBe(7)
        ->and($json['redirect_url'])->toStartWith('http');

    expect($this->agent->calls)->toHaveCount(1)
        ->and($this->agent->calls[0]['user']->id)->toBe($user->id)
        ->and($this->agent->calls[0]['totalDays'])->toBe(3);
});

it('caps days to maximum of 7 and reports capping', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['total_days' => 30]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['total_days'])->toBe(7)
        ->and($json['requested_days'])->toBe(30)
        ->and($json['was_capped'])->toBeTrue()
        ->and($json['max_allowed_days'])->toBe(7)
        ->and($this->agent->calls[0]['totalDays'])->toBe(7);
});

it('enforces minimum of 1 day', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['total_days' => -5]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['total_days'])->toBe(1)
        ->and($json['requested_days'])->toBe(-5)
        ->and($json['was_capped'])->toBeTrue();
});

it('passes custom prompt to agent', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['total_days' => 3, 'custom_prompt' => 'Focus on Mediterranean diet']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['custom_prompt'])->toBe('Focus on Mediterranean diet')
        ->and($this->agent->calls[0]['customPrompt'])->toBe('Focus on Mediterranean diet');
});

it('defaults to 7 days when total_days is omitted', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request([]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['total_days'])->toBe(7)
        ->and($json['requested_days'])->toBe(7)
        ->and($json['was_capped'])->toBeFalse()
        ->and($this->agent->calls[0]['totalDays'])->toBe(7);
});

it('handles exceptions during generation', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->agent->exception = new Exception('Agent error');

    $request = new Request(['total_days' => 3]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain('Agent error');
});
