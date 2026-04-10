<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\DestroyHealthEntryController;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(DestroyHealthEntryController::class);

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
