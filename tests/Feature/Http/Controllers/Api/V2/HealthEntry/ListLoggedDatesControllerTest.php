<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\HealthEntry\ListLoggedDatesController;
use App\Models\HealthSyncSample;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

covers(ListLoggedDatesController::class);

it('requires a bearer token', function (): void {
    $this->getJson(route('api.v2.health-entries.logged-dates', ['date' => '2026-10-15']))->assertUnauthorized();
});

it('lists the local days with entries in the Monday-to-Sunday week of the given day', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    foreach (['2026-10-04 15:00:00', '2026-10-05 01:00:00', '2026-10-11 15:30:00', '2026-10-11 16:30:00'] as $measuredAt) {
        HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id, 'measured_at' => $measuredAt]);
    }

    HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'stepCount', 'measured_at' => '2026-10-07 01:00:00']);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.logged-dates', ['date' => '2026-10-08']))
        ->assertOk()
        ->assertExactJson(['week_start' => '2026-10-05', 'data' => ['2026-10-05', '2026-10-11']]);
});

it('rejects malformed dates', function (): void {
    Sanctum::actingAs(User::factory()->create(), ['chat:converse']);

    $this->getJson(route('api.v2.health-entries.logged-dates', ['date' => '2026/10/08']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
});
