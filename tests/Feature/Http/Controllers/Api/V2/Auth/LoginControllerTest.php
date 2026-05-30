<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Auth\LoginController;
use App\Http\Controllers\Api\V2\Auth\TwoFactorChallengeController;
use App\Models\MobileTwoFactorChallenge;
use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

covers(LoginController::class, TwoFactorChallengeController::class);

it('logs in with email and password and mints a chat-only token', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->postJson(route('api.v2.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone 17 Pro',
        'device_identifier' => 'device-uuid-1',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['api_token', 'abilities', 'user' => ['name'], 'consent_required'])
        ->assertJson(['abilities' => ['chat:converse']]);

    $token = $user->tokens()->first();
    expect($token->name)->toBe('mobile:device-uuid-1')
        ->and($token->abilities)->toBe(['chat:converse']);
});

it('rejects invalid credentials', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->postJson(route('api.v2.auth.login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('returns a 2FA challenge when two-factor is enabled', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson(route('api.v2.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ]);

    $response->assertConflict()
        ->assertJson(['two_factor_required' => true])
        ->assertJsonStructure(['challenge_token']);

    expect($user->tokens()->count())->toBe(0);
    $this->assertDatabaseHas('mobile_two_factor_challenges', [
        'user_id' => $user->id,
        'device_identifier' => 'device-uuid-1',
    ]);
});

it('completes the 2FA challenge with a valid TOTP code', function (): void {
    $secret = resolve(TwoFactorAuthenticationProvider::class)->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $challengeToken = $this->postJson(route('api.v2.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->json('challenge_token');

    $code = resolve(Google2FA::class)->getCurrentOtp($secret);

    $this->postJson(route('api.v2.auth.two-factor'), [
        'challenge_token' => $challengeToken,
        'code' => $code,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk()->assertJson(['abilities' => ['chat:converse']]);

    expect($user->tokens()->count())->toBe(1);
    $this->assertDatabaseEmpty('mobile_two_factor_challenges');
});

it('completes the 2FA challenge with a recovery code', function (): void {
    $user = User::factory()->create([
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(
            (string) json_encode(['recovery-code-1', 'recovery-code-2'])
        ),
        'two_factor_confirmed_at' => now(),
    ]);

    $challengeToken = $this->postJson(route('api.v2.auth.login'), [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->json('challenge_token');

    $this->postJson(route('api.v2.auth.two-factor'), [
        'challenge_token' => $challengeToken,
        'recovery_code' => 'recovery-code-1',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(1)
        ->and($user->fresh()->recoveryCodes())->not->toContain('recovery-code-1');
});

it('rejects an invalid two-factor code and counts the attempt', function (): void {
    $secret = resolve(TwoFactorAuthenticationProvider::class)->generateSecretKey();
    $user = User::factory()->create([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $challenge = MobileTwoFactorChallenge::factory()->for($user)->create([
        'device_identifier' => 'device-uuid-1',
        'token_hash' => hash('sha256', 'known-token'),
    ]);

    $this->postJson(route('api.v2.auth.two-factor'), [
        'challenge_token' => 'known-token',
        'code' => '000000',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnprocessable();

    expect($challenge->fresh()->attempts)->toBe(1);
});

it('rejects an expired challenge', function (): void {
    $user = User::factory()->create();
    MobileTwoFactorChallenge::factory()->for($user)->expired()->create([
        'device_identifier' => 'device-uuid-1',
        'token_hash' => hash('sha256', 'known-token'),
    ]);

    $this->postJson(route('api.v2.auth.two-factor'), [
        'challenge_token' => 'known-token',
        'code' => '000000',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnprocessable()->assertJsonValidationErrors(['challenge_token']);
});
