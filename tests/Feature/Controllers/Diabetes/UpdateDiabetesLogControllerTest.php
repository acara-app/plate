<?php

declare(strict_types=1);

use App\Models\DiabetesLog;
use App\Models\User;

it('can update own diabetes log', function (): void {
    $user = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $user->id]);

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Updated notes',
    ];

    $response = $this->actingAs($user)
        ->put(route('diabetes-log.update', $log), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('diabetes_logs', [
        'id' => $log->id,
        'glucose_value' => 7.2,
        'notes' => 'Updated notes',
    ]);
});

it('cannot update another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $otherUser->id]);

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->put(route('diabetes-log.update', $log), $data);

    $response->assertForbidden();
});

it('prevents empty vitals log submission when updating', function (): void {
    $user = User::factory()->create();
    $log = DiabetesLog::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->put(route('diabetes-log.update', $log), [
            'log_type' => 'vitals',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['vitals']);
});
