<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\UpdateHealthEntryController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

covers(UpdateHealthEntryController::class);

it('dispatches daily aggregate refresh for old and new dates after updating a health entry', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDay(),
    ]);
    $previousUtcDate = $sample->measured_at->copy()->utc()->toDateString();

    $newMeasuredAt = now();

    $response = $this->actingAs($user)->put(route('health-entries.update', $sample), [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => $newMeasuredAt->toDateTimeString(),
    ]);

    $response->assertRedirect();

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => str_contains($job->uniqueId(), $user->id.':'.$previousUtcDate));

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => str_contains($job->uniqueId(), $user->id.':'.$newMeasuredAt->copy()->utc()->toDateString()));
});

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
