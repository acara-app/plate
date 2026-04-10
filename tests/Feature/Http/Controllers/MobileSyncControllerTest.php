<?php

declare(strict_types=1);

use App\Http\Controllers\MobileSyncController;
use App\Models\MobileSyncDevice;
use App\Models\User;

covers(MobileSyncController::class);

it('renders mobile sync page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mobile-sync.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('mobile-sync/edit'));
});

it('shows empty state when no devices paired', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('mobile-sync.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('devices', [])
            ->where('pending_token', null)
            ->has('instance_url')
        );
});

it('shows paired devices', function (): void {
    $user = User::factory()->create();
    MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_name' => 'iPhone 15 Pro',
    ]);

    $this->actingAs($user)
        ->get(route('mobile-sync.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('devices', 1)
            ->where('devices.0.device_name', 'iPhone 15 Pro')
        );
});

it('shows pending token when generated', function (): void {
    $user = User::factory()->create();
    MobileSyncDevice::factory()->for($user)->withToken()->create();

    $this->actingAs($user)
        ->get(route('mobile-sync.edit'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('pending_token', fn ($token): bool => $token !== null && mb_strlen((string) $token) === 8)
            ->has('instance_url')
        );
});

it('generates a pairing token', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('mobile-sync.token'))
        ->assertRedirect(route('mobile-sync.edit'));

    expect($user->mobileSyncDevices()->count())->toBe(1)
        ->and($user->mobileSyncDevices()->first()->linking_token)->not->toBeNull();
});

it('deactivates pending tokens when generating new one', function (): void {
    $user = User::factory()->create();
    $oldDevice = MobileSyncDevice::factory()->for($user)->withToken()->create();

    $this->actingAs($user)
        ->post(route('mobile-sync.token'))
        ->assertRedirect();

    expect($oldDevice->fresh()->is_active)->toBeFalse()
        ->and($user->mobileSyncDevices()->where('is_active', true)->count())->toBe(1);
});

it('disconnects a paired device', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create();

    $this->actingAs($user)
        ->delete(route('mobile-sync.destroy', $device))
        ->assertRedirect(route('mobile-sync.edit'));

    expect($device->fresh()->is_active)->toBeFalse();
});

it('prevents disconnecting another users device', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($otherUser)->paired()->create();

    $this->actingAs($user)
        ->delete(route('mobile-sync.destroy', $device))
        ->assertForbidden();
});

it('requires authentication', function (): void {
    $this->get(route('mobile-sync.edit'))
        ->assertRedirect(route('login'));
});
