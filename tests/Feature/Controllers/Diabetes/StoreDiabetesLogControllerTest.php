<?php

declare(strict_types=1);

use App\Models\User;

it('can store a new diabetes log with glucose reading', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 6.7,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Morning reading after breakfast',
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'glucose_value' => 121, // 6.7 mmol/L converted to mg/dL
        'glucose_reading_type' => 'fasting',
        'notes' => 'Morning reading after breakfast',
    ]);
});

it('can store a diabetes log with insulin only', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'insulin',
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

    // Test minimum value (default unit is mmol/L, min is 1.1)
    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'glucose',
            'glucose_value' => 0.5, // Below minimum of 1.1 mmol/L
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_value']);

    // Test maximum value (default unit is mmol/L, max is 33.3)
    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'glucose',
            'glucose_value' => 40.0, // Above maximum of 33.3 mmol/L
            'glucose_reading_type' => 'fasting',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_value']);
});

it('validates reading type enum', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'glucose',
            'glucose_value' => 6.7,
            'glucose_reading_type' => 'InvalidType',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_reading_type']);
});

it('stores diabetes log without notes', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 5.3,
        'glucose_reading_type' => 'post-meal',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'glucose_value' => 95, // 5.3 mmol/L converted to mg/dL
        'glucose_reading_type' => 'post-meal',
        'notes' => null,
    ]);
});

it('requires log_type field', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['log_type']);
});

it('prevents empty glucose log submission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'glucose',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['glucose_value', 'glucose_reading_type']);
});

it('prevents empty food log submission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'food',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['carbs_grams']);
});

it('prevents empty insulin log submission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'insulin',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['insulin_units', 'insulin_type']);
});

it('prevents empty medication log submission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'meds',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['medication_name']);
});

it('prevents empty vitals log submission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'vitals',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['vitals']);
});

it('prevents empty exercise log submission', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), [
            'log_type' => 'exercise',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['exercise_type']);
});

it('can store food log with carbs', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'food',
        'carbs_grams' => 45,
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Lunch',
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'carbs_grams' => 45,
        'notes' => 'Lunch',
    ]);
});

it('can store medication log', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'meds',
        'medication_name' => 'Metformin',
        'medication_dosage' => '500mg',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'medication_name' => 'Metformin',
        'medication_dosage' => '500mg',
    ]);
});

it('can store vitals log with at least one vital sign', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'vitals',
        'weight' => 75.5,
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'weight' => 75.5,
    ]);
});

it('can store exercise log', function (): void {
    $user = User::factory()->create();

    $data = [
        'log_type' => 'exercise',
        'exercise_type' => 'Running',
        'exercise_duration_minutes' => 30,
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->post(route('diabetes-log.store'), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'user_id' => $user->id,
        'exercise_type' => 'Running',
        'exercise_duration_minutes' => 30,
    ]);
});
