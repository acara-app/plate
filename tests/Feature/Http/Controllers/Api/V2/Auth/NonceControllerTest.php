<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Auth\NonceController;
use App\Models\MobileAuthNonce;

covers(NonceController::class);

it('issues a single-use device-bound nonce', function (): void {
    $response = $this->postJson(route('api.v2.auth.nonce'), [
        'device_identifier' => 'device-uuid-1',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['nonce_id', 'nonce', 'expires_at']);

    $this->assertDatabaseHas('mobile_auth_nonces', [
        'nonce_id' => $response->json('nonce_id'),
        'device_identifier' => 'device-uuid-1',
    ]);
});

it('requires a device identifier', function (): void {
    $this->postJson(route('api.v2.auth.nonce'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['device_identifier']);
});

it('replaces a previous nonce for the same device', function (): void {
    MobileAuthNonce::factory()->create(['device_identifier' => 'device-uuid-1']);

    $this->postJson(route('api.v2.auth.nonce'), [
        'device_identifier' => 'device-uuid-1',
    ])->assertOk();

    expect(MobileAuthNonce::query()->where('device_identifier', 'device-uuid-1')->count())->toBe(1);
});
