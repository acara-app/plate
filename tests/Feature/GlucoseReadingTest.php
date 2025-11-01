<?php

declare(strict_types=1);

use App\Models\GlucoseReading;
use App\Models\User;

it('requires authentication to view glucose index', function (): void {
    $response = $this->get(route('glucose.index'));

    $response->assertRedirectToRoute('login');
});

it('requires email verification to view glucose index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('glucose.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders glucose index page for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('glucose.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glucose/index')
            ->has('readings')
            ->has('readingTypes'));
});

it('displays user glucose readings', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create readings for both users
    GlucoseReading::factory()->count(3)->create(['user_id' => $user->id]);
    GlucoseReading::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('glucose.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glucose/index')
            ->has('readings.data', 3)); // Should only see their own readings
});

it('can store a new glucose reading', function (): void {
    $user = User::factory()->create();

    $data = [
        'reading_value' => 120.5,
        'reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Morning reading after breakfast',
    ];

    $response = $this->actingAs($user)
        ->post(route('glucose.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('glucose_readings', [
        'user_id' => $user->id,
        'reading_value' => 120.5,
        'reading_type' => 'fasting',
        'notes' => 'Morning reading after breakfast',
    ]);
});

it('validates required fields when storing glucose reading', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('glucose.store'), []);

    $response->assertSessionHasErrors(['reading_value', 'reading_type', 'measured_at']);
});

it('validates reading value range', function (): void {
    $user = User::factory()->create();

    // Test minimum value
    $response = $this->actingAs($user)
        ->post(route('glucose.store'), [
            'reading_value' => 10, // Below minimum of 20
            'reading_type' => 'fasting',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['reading_value']);

    // Test maximum value
    $response = $this->actingAs($user)
        ->post(route('glucose.store'), [
            'reading_value' => 700, // Above maximum of 600
            'reading_type' => 'fasting',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['reading_value']);
});

it('validates reading type enum', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('glucose.store'), [
            'reading_value' => 120,
            'reading_type' => 'InvalidType',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['reading_type']);
});

it('can delete own glucose reading', function (): void {
    $user = User::factory()->create();
    $reading = GlucoseReading::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete(route('glucose.destroy', $reading));

    $response->assertRedirect();

    $this->assertDatabaseMissing('glucose_readings', [
        'id' => $reading->id,
    ]);
});

it('cannot delete another user glucose reading', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $reading = GlucoseReading::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->delete(route('glucose.destroy', $reading));

    $response->assertForbidden();

    $this->assertDatabaseHas('glucose_readings', [
        'id' => $reading->id,
    ]);
});

it('stores glucose reading without notes', function (): void {
    $user = User::factory()->create();

    $data = [
        'reading_value' => 95.0,
        'reading_type' => 'post-meal',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('glucose.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('glucose_readings', [
        'user_id' => $user->id,
        'reading_value' => 95.0,
        'reading_type' => 'post-meal',
        'notes' => null,
    ]);
});

it('can update own glucose reading', function (): void {
    $user = User::factory()->create();
    $reading = GlucoseReading::factory()->create(['user_id' => $user->id]);

    $data = [
        'reading_value' => 130.0,
        'reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Updated notes',
    ];

    $response = $this->actingAs($user)
        ->put(route('glucose.update', $reading), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('glucose_readings', [
        'id' => $reading->id,
        'reading_value' => 130.0,
        'notes' => 'Updated notes',
    ]);
});

it('cannot update another user glucose reading', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $reading = GlucoseReading::factory()->create(['user_id' => $otherUser->id]);

    $data = [
        'reading_value' => 130.0,
        'reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->put(route('glucose.update', $reading), $data);

    $response->assertForbidden();
});

it('validates required fields when updating glucose reading', function (): void {
    $user = User::factory()->create();
    $reading = GlucoseReading::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->put(route('glucose.update', $reading), []);

    $response->assertSessionHasErrors(['reading_value', 'reading_type', 'measured_at']);
});

it('renders glucose dashboard for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('glucose.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glucose/tracking')
            ->has('readings')
            ->has('readingTypes'));
});

it('displays all user glucose readings on dashboard', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    GlucoseReading::factory()->count(5)->create(['user_id' => $user->id]);
    GlucoseReading::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('glucose.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glucose/tracking')
            ->has('readings', 5));
});
