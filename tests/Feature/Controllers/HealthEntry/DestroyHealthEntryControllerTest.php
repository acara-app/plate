<?php

declare(strict_types=1);

use App\Models\HealthEntry;
use App\Models\User;

it('can delete own diabetes log', function (): void {
    $user = User::factory()->create();
    $log = HealthEntry::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $log));

    $response->assertRedirect();

    $this->assertDatabaseMissing('health_entries', [
        'id' => $log->id,
    ]);
});

it('cannot delete another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $log = HealthEntry::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->delete(route('health-entries.destroy', $log));

    $response->assertForbidden();

    $this->assertDatabaseHas('health_entries', [
        'id' => $log->id,
    ]);
});
