<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\ListLoggedDatesController;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(ListLoggedDatesController::class);

it('keeps another week’s logged days private to signed-in users', function (): void {
    $this->getJson(route('health-entries.logged-dates', ['date' => '2026-10-15']))
        ->assertUnauthorized();
});

it('lists the signed-in user’s logged days in the Monday-to-Sunday week of the requested date', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $otherUser = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    foreach (['2026-10-11 15:00:00', '2026-10-12 23:00:00', '2026-10-13 02:00:00', '2026-10-18 15:00:00', '2026-10-18 16:30:00'] as $measuredAt) {
        HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id, 'measured_at' => $measuredAt]);
    }

    HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $otherUser->id, 'measured_at' => '2026-10-14 02:00:00']);

    $this->actingAs($user)
        ->getJson(route('health-entries.logged-dates', ['date' => '2026-10-15']))
        ->assertOk()
        ->assertExactJson(['2026-10-13', '2026-10-18']);
});
