<?php

declare(strict_types=1);

use App\Models\DiabetesLog;
use App\Models\User;

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
