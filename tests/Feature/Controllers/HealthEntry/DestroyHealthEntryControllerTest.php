<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\DestroyHealthEntryController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

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

it('deletes an own entry and returns to its day in the logbook', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create([
        'user_id' => $user->id,
        'measured_at' => '2026-10-17 23:30:00',
    ]);

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $sample));

    $response->assertRedirectToRoute('health-entries.index', ['date' => '2026-10-18']);

    $this->assertDatabaseMissing('health_sync_samples', [
        'id' => $sample->id,
    ]);
});

it('collects UTC dates from grouped samples when deleting', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();

    $sample1 = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create([
        'user_id' => $user->id,
        'group_id' => $groupId,
    ]);

    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create([
        'user_id' => $user->id,
        'group_id' => $groupId,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $sample1));

    $response->assertRedirect();
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

it('does not delete samples that are not logbook entries', function (string $type): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => $type]);

    $this->actingAs($user)
        ->delete(route('health-entries.destroy', $sample))
        ->assertNotFound();

    $this->assertDatabaseHas('health_sync_samples', ['id' => $sample->id]);
})->with(['synced steps' => ['stepCount'], 'logged water' => ['water']]);
