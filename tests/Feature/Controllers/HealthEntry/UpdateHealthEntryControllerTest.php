<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\UpdateHealthEntryController;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(UpdateHealthEntryController::class);

it('can update own diabetes log', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id]);

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Updated notes',
    ];

    $response = $this->actingAs($user)
        ->put(route('health-entries.update', $sample), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('health_sync_samples', [
        'id' => $sample->id,
        'type_identifier' => 'bloodGlucose',
        'value' => 130,
        'notes' => 'Updated notes',
    ]);
});

it('cannot update another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $otherUser->id]);

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->put(route('health-entries.update', $sample), $data);

    $response->assertForbidden();
});

it('prevents empty vitals log submission when updating', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->put(route('health-entries.update', $sample), [
            'log_type' => 'vitals',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['vitals']);
});
