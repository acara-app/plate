<?php

declare(strict_types=1);

use App\Models\User;

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
