<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Auth\AppleAuthController;
use App\Models\MobileAuthNonce;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

covers(AppleAuthController::class);

beforeEach(function (): void {
    config(['services.apple.client_id' => 'com.acaraplate.apple-health-sync']);
});

/**
 * @param  array<string, mixed>  $claims
 */
function appleIdToken(string $rawNonce, array $claims = []): string
{
    $keyPair = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($keyPair, $privateKey);
    $details = openssl_pkey_get_details($keyPair);

    $b64 = fn (string $data): string => mb_rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

    Http::fake([
        'https://appleid.apple.com/auth/keys' => Http::response([
            'keys' => [[
                'kty' => 'RSA',
                'kid' => 'apple-key',
                'alg' => 'RS256',
                'use' => 'sig',
                'n' => $b64($details['rsa']['n']),
                'e' => $b64($details['rsa']['e']),
            ]],
        ]),
    ]);

    return JWT::encode(array_merge([
        'iss' => 'https://appleid.apple.com',
        'aud' => 'com.acaraplate.apple-health-sync',
        'sub' => 'apple-sub-123',
        'email' => 'user@example.com',
        'email_verified' => 'true',
        'is_private_email' => 'false',
        'nonce' => hash('sha256', $rawNonce),
        'iat' => now()->timestamp,
        'exp' => now()->addHour()->timestamp,
    ], $claims), $privateKey, 'RS256', 'apple-key');
}

it('creates a user from a private-relay Apple token without marking the email verified', function (): void {
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('raw-nonce', [
        'sub' => 'apple-relay-sub',
        'email' => 'abc123@privaterelay.appleid.com',
        'is_private_email' => 'true',
    ]);

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
        'full_name' => 'Relay User',
    ])->assertOk()->assertJson(['abilities' => ['chat:converse']]);

    $user = User::query()->where('apple_id', 'apple-relay-sub')->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Relay User')
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->tokens()->first()->abilities)->toBe(['chat:converse']);
});

it('logs in an existing user matched by apple_id', function (): void {
    User::factory()->create(['apple_id' => 'existing-apple-sub']);
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('raw-nonce', ['sub' => 'existing-apple-sub']);

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    expect(User::query()->where('apple_id', 'existing-apple-sub')->count())->toBe(1);
});

it('links apple_id to a verified non-relay email account', function (): void {
    $user = User::factory()->create(['email' => 'real@example.com', 'apple_id' => null]);
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('raw-nonce', [
        'sub' => 'link-apple-sub',
        'email' => 'real@example.com',
        'email_verified' => 'true',
        'is_private_email' => 'false',
    ]);

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    expect($user->fresh()->apple_id)->toBe('link-apple-sub');
});

it('rejects sign-in when Apple is not configured', function (): void {
    config(['services.apple.client_id' => '']);
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => 'unverifiable-token',
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnauthorized();
});

it('refuses to link into an unverified email account', function (): void {
    User::factory()->unverified()->create(['email' => 'unverified@example.com', 'apple_id' => null]);
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('raw-nonce', [
        'sub' => 'attacker-apple-sub',
        'email' => 'unverified@example.com',
        'email_verified' => 'true',
        'is_private_email' => 'false',
    ]);

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertConflict()->assertJson(['code' => 'email_exists']);
});

it('rejects a token whose nonce does not match the stored nonce', function (): void {
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('a-different-nonce');

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnauthorized();
});

it('consumes the nonce so it cannot be replayed', function (): void {
    $nonce = MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('raw-nonce', ['sub' => 'single-use-sub']);

    $payload = [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ];

    $this->postJson(route('api.v2.auth.apple'), $payload)->assertOk();
    $this->postJson(route('api.v2.auth.apple'), $payload)->assertUnauthorized();
});

it('rejects an expired nonce', function (): void {
    $nonce = MobileAuthNonce::factory()->expired()->create(['device_identifier' => 'device-uuid-1', 'nonce' => 'raw-nonce']);
    $token = appleIdToken('raw-nonce');

    $this->postJson(route('api.v2.auth.apple'), [
        'nonce_id' => $nonce->nonce_id,
        'identity_token' => $token,
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnauthorized();
});
