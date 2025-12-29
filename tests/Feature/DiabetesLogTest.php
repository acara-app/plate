<?php

declare(strict_types=1);

use App\Models\DiabetesLog;
use App\Models\User;

it('requires authentication to view diabetes log index', function (): void {
    $response = $this->get(route('diabetes-log.index'));

    $response->assertRedirectToRoute('login');
});

it('requires email verification to view diabetes log index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders diabetes log index page for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('diabetes-log/index')
            ->has('logs')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('displays user diabetes logs', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create logs for both users
    DiabetesLog::factory()->count(3)->create(['user_id' => $user->id]);
    DiabetesLog::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('diabetes-log/index')
            ->has('logs.data', 3)); // Should only see their own logs
});

it('can store a new diabetes log with glucose reading', function (): void {
    $user = User::factory()->create();

    $data = [
        'glucose_value' => 120.5,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Morning reading after breakfast',
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'glucose_value' => 120.5,
        'glucose_reading_type' => 'fasting',
        'notes' => 'Morning reading after breakfast',
    ]);
});

it('can store a diabetes log with insulin only', function (): void {
    $user = User::factory()->create();

    $data = [
        'measured_at' => now()->toDateTimeString(),
        'insulin_units' => 10,
        'insulin_type' => 'bolus',
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'insulin_units' => 10,
        'insulin_type' => 'bolus',
    ]);
});

it('validates reading value range', function (): void {
    $user = User::factory()->create();

    // Test minimum value
    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'glucose_value' => 10, // Below minimum of 20
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_value']);

    // Test maximum value
    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'glucose_value' => 700, // Above maximum of 600
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_value']);
});

it('validates reading type enum', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'glucose_value' => 120,
            'glucose_reading_type' => 'InvalidType',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_reading_type']);
});

it('can delete own diabetes log', function (): void {
    $user = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete(route('diabetes-log.destroy', $log));

    $response->assertRedirect();

    $this->assertDatabaseMissing('diabetes_logs', [
        'id' => $log->id,
    ]);
});

it('cannot delete another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->delete(route('diabetes-log.destroy', $log));

    $response->assertForbidden();

    $this->assertDatabaseHas('diabetes_logs', [
        'id' => $log->id,
    ]);
});

it('stores diabetes log without notes', function (): void {
    $user = User::factory()->create();

    $data = [
        'glucose_value' => 95.0,
        'glucose_reading_type' => 'post-meal',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'glucose_value' => 95.0,
        'glucose_reading_type' => 'post-meal',
        'notes' => null,
    ]);
});

it('can update own diabetes log', function (): void {
    $user = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $user->id]);

    $data = [
        'glucose_value' => 130.0,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Updated notes',
    ];

    $response = $this->actingAs($user)
        ->put(route('diabetes-log.update', $log), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'id' => $log->id,
        'glucose_value' => 130.0,
        'notes' => 'Updated notes',
    ]);
});

it('cannot update another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $otherUser->id]);

    $data = [
        'glucose_value' => 130.0,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->put(route('diabetes-log.update', $log), $data);

    $response->assertForbidden();
});

it('renders diabetes log tracking dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('diabetes-log/tracking')
            ->has('logs')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('displays all user diabetes logs on tracking dashboard', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    DiabetesLog::factory()->count(5)->create(['user_id' => $user->id]);
    DiabetesLog::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('diabetes-log/tracking')
            ->has('logs', 5));
});

it('renders diabetes insights page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.insights'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('diabetes-log/insights')
            ->has('glucoseAnalysis')
            ->has('concerns')
            ->has('hasMealPlan')
            ->has('mealPlan'));
});
