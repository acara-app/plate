<?php

declare(strict_types=1);

use App\Ai\Tools\GetUserProfile;
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

    app()->instance(GetsUserProfileContext::class, $this->action);
    $this->tool = new GetUserProfile();
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_user_profile')
        ->and($this->tool->description())->toContain("Retrieve the current user's profile");
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;

    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKey('section');
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['section' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns full profile when section is all', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->action->contextData = [
        'onboarding_completed' => true,
        'missing_data' => [],
        'context' => 'Formatted context',
        'raw_data' => ['biometrics' => ['age' => 30]],
    ];

    $request = new Request(['section' => 'all']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['profile'])->toHaveKey('biometrics');
});

it('returns specific section data', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->action->contextData = [
        'onboarding_completed' => true,
        'missing_data' => [],
        'context' => 'Formatted context',
        'raw_data' => [
            'biometrics' => ['age' => 30],
            'goals' => ['primary_goal' => 'weight_loss'],
        ],
    ];

    $request = new Request(['section' => 'biometrics']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['section'])->toBe('biometrics')
        ->and($json['data'])->toHaveKey('age', 30);
});

it('handles missing section error', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->action->contextData = [
        'onboarding_completed' => true,
        'missing_data' => [],
        'context' => 'Formatted context',
        'raw_data' => [
            'biometrics' => ['age' => 30],
        ],
    ];

    $request = new Request(['section' => 'invalid_section']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error')
        ->and($json['error'])->toContain("Section 'invalid_section' not found");
});

it('handles error when raw data is missing', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->action->contextData = [
        'onboarding_completed' => false,
        'missing_data' => ['profile'],
        'context' => 'No profile',
        'raw_data' => null,
    ];

    $request = new Request(['section' => 'biometrics']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'Profile data not available');
});
