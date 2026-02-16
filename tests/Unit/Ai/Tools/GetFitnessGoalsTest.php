<?php

declare(strict_types=1);

use App\Ai\Tools\GetFitnessGoals;
use App\Contracts\Actions\GetsUserProfileContext;
use App\Models\User;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

beforeEach(function (): void {
    // Create a simple test implementation of the interface
    $this->action = new class implements GetsUserProfileContext
    {
        public array $contextData = [];

        public function handle(User $user): array
        {
            return $this->contextData;
        }
    };

    $this->tool = new GetFitnessGoals($this->action);
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_fitness_goals')
        ->and($this->tool->description())->toContain('Retrieve the current user\'s fitness');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()->toBeEmpty();
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns success with goals', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->action->contextData = [
        'onboarding_completed' => true,
        'missing_data' => [],
        'context' => 'Formatted context',
        'raw_data' => [
            'goals' => ['primary_goal' => 'muscle_gain', 'intensity' => 'high'],
            'biometrics' => ['weight_kg' => 80],
        ],
    ];

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['goals']['primary_goal'])->toBe('muscle_gain')
        ->and($json['biometrics']['weight_kg'])->toBe(80);
});

it('returns error if raw data is missing', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->action->contextData = [
        'onboarding_completed' => false,
        'missing_data' => ['profile'],
        'context' => 'No profile',
        'raw_data' => null,
    ];

    $request = new Request;
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', false)
        ->and($json['message'])->toContain('User has not completed their profile');
});
