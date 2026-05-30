<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Auth\CapabilitiesController;
use App\Http\Controllers\Api\V2\Auth\MeController;
use App\Models\User;

covers(MeController::class, CapabilitiesController::class);

/**
 * @return array<string, string>
 */
function bearer(User $user): array
{
    return ['Authorization' => 'Bearer '.$user->createToken('mobile:device-1', ['chat:converse'])->plainTextToken];
}

it('requires authentication for me', function (): void {
    $this->getJson(route('api.v2.auth.me'))->assertUnauthorized();
});

it('returns the user, consent state, and capabilities', function (): void {
    config([
        'mobile.chat_first_enabled' => true,
        'mobile.min_app_version' => '1.6.0',
    ]);
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $this->withHeaders(bearer($user))
        ->getJson(route('api.v2.auth.me'))
        ->assertOk()
        ->assertJson([
            'user' => ['name' => 'Jane Doe'],
            'consent' => ['consent_required' => false],
            'capabilities' => [
                'chat_first' => true,
                'min_app_version' => '1.6.0',
            ],
        ]);
});

it('flags consent_required when the medical disclaimer is unaccepted', function (): void {
    $user = User::factory()->withoutDisclaimer()->create();

    $this->withHeaders(bearer($user))
        ->getJson(route('api.v2.auth.me'))
        ->assertOk()
        ->assertJson(['consent' => ['consent_required' => true]]);
});

it('exposes capabilities without authentication', function (): void {
    config([
        'mobile.chat_first_enabled' => false,
        'mobile.auth_methods' => ['apple' => true, 'google' => true, 'email' => false],
        'mobile.min_app_version' => '1.5.0',
    ]);

    $this->getJson(route('api.v2.auth.capabilities'))
        ->assertOk()
        ->assertJsonPath('methods', ['apple', 'google'])
        ->assertJson([
            'chat_first' => false,
            'min_app_version' => '1.5.0',
        ]);
});
