<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Auth\GoogleAuthController;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

covers(GoogleAuthController::class);

beforeEach(function (): void {
    config(['services.google.allowed_audiences' => ['ios-client.apps.googleusercontent.com']]);
});

/**
 * @param  array<string, mixed>  $claims
 */
function googleIdToken(array $claims = []): string
{
    $keyPair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keyPair, $privateKey);
    $details = openssl_pkey_get_details($keyPair);

    $b64 = fn (string $data): string => mb_rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

    Http::fake([
        'https://www.googleapis.com/oauth2/v3/certs' => Http::response([
            'keys' => [[
                'kty' => 'RSA',
                'kid' => 'test-key',
                'alg' => 'RS256',
                'use' => 'sig',
                'n' => $b64($details['rsa']['n']),
                'e' => $b64($details['rsa']['e']),
            ]],
        ]),
    ]);

    return JWT::encode(array_merge([
        'iss' => 'https://accounts.google.com',
        'aud' => 'ios-client.apps.googleusercontent.com',
        'sub' => 'google-sub-123',
        'email' => 'user@example.com',
        'email_verified' => true,
        'name' => 'Google User',
        'iat' => now()->timestamp,
        'exp' => now()->addHour()->timestamp,
        'jti' => Str::uuid()->toString(),
    ], $claims), $privateKey, 'RS256', 'test-key');
}

it('creates a user from a verified Google token and mints a chat-only token', function (): void {
    $token = googleIdToken(['sub' => 'new-google-sub', 'email' => 'new@example.com']);

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk()->assertJson(['abilities' => ['chat:converse']]);

    $user = User::query()->where('google_id', 'new-google-sub')->first();
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('new@example.com')
        ->and($user->tokens()->first()->name)->toBe('mobile:device-uuid-1')
        ->and($user->tokens()->first()->abilities)->toBe(['chat:converse']);
});

it('logs in an existing user matched by google_id', function (): void {
    User::factory()->create(['google_id' => 'existing-sub']);
    $token = googleIdToken(['sub' => 'existing-sub']);

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    expect(User::query()->where('google_id', 'existing-sub')->count())->toBe(1);
});

it('links google_id to a verified email account', function (): void {
    $user = User::factory()->create(['email' => 'link@example.com', 'google_id' => null]);
    $token = googleIdToken(['sub' => 'link-sub', 'email' => 'link@example.com', 'email_verified' => true]);

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    expect($user->fresh()->google_id)->toBe('link-sub');
});

it('refuses to link into an unverified email account', function (): void {
    User::factory()->unverified()->create(['email' => 'unverified@example.com', 'google_id' => null]);
    $token = googleIdToken(['sub' => 'attacker-sub', 'email' => 'unverified@example.com', 'email_verified' => true]);

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertStatus(409)->assertJson(['code' => 'email_exists']);

    expect(User::query()->where('email', 'unverified@example.com')->first()->google_id)->toBeNull();
});

it('rejects a token with an audience that is not allowed', function (): void {
    $token = googleIdToken(['aud' => 'evil-client.apps.googleusercontent.com']);

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertStatus(401);
});

it('rejects replaying the same token twice', function (): void {
    $token = googleIdToken(['jti' => 'fixed-jti']);

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    $this->postJson(route('api.v2.auth.google'), [
        'id_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertStatus(401);
});
