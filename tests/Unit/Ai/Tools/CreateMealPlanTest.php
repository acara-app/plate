<?php

declare(strict_types=1);

use App\Ai\Tools\CreateMealPlan;
use App\Contracts\Ai\GeneratesMealPlans;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    // Create a simple test implementation
    $this->agent = new class implements GeneratesMealPlans
    {
        public ?Exception $exception = null;

        public array $calls = [];

        public function handle(User $user, int $totalDays = 7): void
        {
            $this->calls[] = ['user' => $user, 'totalDays' => $totalDays];

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
        ->and($json['total_days'])->toBe(3);

    expect($this->agent->calls)->toHaveCount(1);
    expect($this->agent->calls[0]['user']->id)->toBe($user->id);
    expect($this->agent->calls[0]['totalDays'])->toBe(3);
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
