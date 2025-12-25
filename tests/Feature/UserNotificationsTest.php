<?php

declare(strict_types=1);

use App\Models\User;

uses()->group('notification-settings');

it('requires authentication to view notification settings', function (): void {
    $response = $this->get(route('user-notifications.edit'));

    $response->assertRedirectToRoute('login');
});

it('renders notification settings page for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('user-notifications.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('notificationSettings')
            ->has('defaultThresholds')
            ->where('defaultThresholds.low', config('glucose.hypoglycemia_threshold'))
            ->where('defaultThresholds.high', config('glucose.hyperglycemia_threshold')));
});

it('displays default notification settings for new user', function (): void {
    $user = User::factory()->create(['settings' => null]);

    $response = $this->actingAs($user)
        ->get(route('user-notifications.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notificationSettings.glucose_notifications_enabled', true)
            ->where('notificationSettings.glucose_notification_low_threshold', null)
            ->where('notificationSettings.glucose_notification_high_threshold', null));
});

it('displays saved notification settings', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucoseNotificationsEnabled' => false,
            'glucoseNotificationLowThreshold' => 70,
            'glucoseNotificationHighThreshold' => 180,
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('user-notifications.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notificationSettings.glucose_notifications_enabled', false)
            ->where('notificationSettings.glucose_notification_low_threshold', 70)
            ->where('notificationSettings.glucose_notification_high_threshold', 180));
});

it('can update notification settings', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationLowThreshold' => 80,
            'glucoseNotificationHighThreshold' => 200,
        ]);

    $response->assertRedirectToRoute('user-notifications.edit')
        ->assertSessionHas('status', 'notification-settings-updated');

    expect($user->fresh()->settings)->toEqual([
        'glucoseNotificationsEnabled' => true,
        'glucoseNotificationLowThreshold' => 80,
        'glucoseNotificationHighThreshold' => 200,
    ]);
});

it('can disable glucose notifications', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationLowThreshold' => 70,
            'glucoseNotificationHighThreshold' => 180,
        ],
    ]);

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => false,
            'glucoseNotificationLowThreshold' => null,
            'glucoseNotificationHighThreshold' => null,
        ]);

    $response->assertRedirectToRoute('user-notifications.edit');

    expect($user->fresh()->settings['glucoseNotificationsEnabled'])->toBeFalse();
});

it('validates glucose notification enabled is boolean', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => 'invalid',
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationsEnabled']);
});

it('validates low threshold is integer', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationLowThreshold' => 'not-a-number',
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationLowThreshold']);
});

it('validates low threshold minimum value', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationLowThreshold' => 30,
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationLowThreshold']);
});

it('validates low threshold maximum value', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationLowThreshold' => 200,
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationLowThreshold']);
});

it('validates high threshold is integer', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationHighThreshold' => 'not-a-number',
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationHighThreshold']);
});

it('validates high threshold minimum value', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationHighThreshold' => 50,
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationHighThreshold']);
});

it('validates high threshold maximum value', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationHighThreshold' => 500,
        ]);

    $response->assertSessionHasErrors(['glucoseNotificationHighThreshold']);
});

it('accepts null values for thresholds', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->patch(route('user-notifications.update'), [
            'glucoseNotificationsEnabled' => true,
            'glucoseNotificationLowThreshold' => null,
            'glucoseNotificationHighThreshold' => null,
        ]);

    $response->assertRedirectToRoute('user-notifications.edit');

    expect($user->fresh()->settings)->toEqual([
        'glucoseNotificationsEnabled' => true,
        'glucoseNotificationLowThreshold' => null,
        'glucoseNotificationHighThreshold' => null,
    ]);
});
