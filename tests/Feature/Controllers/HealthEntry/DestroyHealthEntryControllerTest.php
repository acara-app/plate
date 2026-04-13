<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\DestroyHealthEntryController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

covers(DestroyHealthEntryController::class);

it('dispatches daily aggregate refresh after deleting a health entry', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id]);
    $utcDate = $sample->measured_at->copy()->utc()->toDateString();

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $sample));

    $response->assertRedirect();

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => str_contains($job->uniqueId(), $user->id.':'.$utcDate));
});

it('can delete own diabetes log', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $sample));

    $response->assertRedirect();

    $this->assertDatabaseMissing('health_sync_samples', [
        'id' => $sample->id,
    ]);
});

it('cannot delete another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $sample));

    $response->assertForbidden();

    $this->assertDatabaseHas('health_sync_samples', [
        'id' => $sample->id,
    ]);
});
