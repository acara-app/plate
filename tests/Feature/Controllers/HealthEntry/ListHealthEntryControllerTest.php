<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\ListHealthEntryController;
use App\Http\Requests\ListHealthEntriesRequest;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(ListHealthEntryController::class, ListHealthEntriesRequest::class);

it('requires authentication to view diabetes log index', function (): void {
    $response = $this->get(route('health-entries.index'));

    $response->assertRedirectToRoute('login');
});

it('requires email verification to view diabetes log index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('health-entries.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders the logbook for today in the user’s timezone', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    $this->actingAs($user)
        ->get(route('health-entries.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('health-entries/index')
            ->where('date', '2026-10-19')
            ->where('today', '2026-10-19')
            ->where('newEntryAt', null)
            ->has('entries')
            ->has('loggedDates')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('lists only the chosen local day’s entries for the signed-in user', function (string $date, array $values): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $otherUser = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    foreach (['2026-10-17 15:30:00' => 101, '2026-10-17 23:30:00' => 102, '2026-10-18 15:59:00' => 103, '2026-10-18 16:00:00' => 104] as $measuredAt => $value) {
        HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id, 'value' => $value, 'measured_at' => $measuredAt]);
    }

    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $otherUser->id, 'measured_at' => '2026-10-18 01:00:00']);

    $this->actingAs($user)
        ->get(route('health-entries.index', ['date' => $date]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('date', $date)
            ->where('entries', fn ($entries): bool => collect($entries)->pluck('glucose_value')->map(fn ($value): int => (int) $value)->all() === $values));
})->with([
    'late local evening' => ['2026-10-17', [101]],
    'local morning after a UTC late evening' => ['2026-10-18', [103, 102]],
    'next local day starts at 16:00 UTC' => ['2026-10-19', [104]],
]);

it('marks the days of the selected Monday-to-Sunday week that have entries', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    foreach (['2026-10-11 15:00:00', '2026-10-12 23:00:00', '2026-10-13 02:00:00', '2026-10-18 15:00:00', '2026-10-18 16:30:00'] as $measuredAt) {
        HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id, 'measured_at' => $measuredAt]);
    }

    $this->actingAs($user)
        ->get(route('health-entries.index', ['date' => '2026-10-15']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('loggedDates', ['2026-10-13', '2026-10-18']));
});

it('defers dialog helper props so they are skipped on partial reloads', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('health-entries.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('recentMedications')
            ->missing('recentInsulins')
            ->missing('todaysMeals'));
});

it('rejects malformed logbook dates', function (mixed $date): void {
    $this->actingAs(User::factory()->create())
        ->getJson(route('health-entries.index', ['date' => $date]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('date');
})->with(['impossible month' => ['2026-13-01'], 'day first' => ['18-10-2026'], 'array' => [['2026-10-18']]]);

it('suggests the current account-local time on a past day for new entries', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);

    $this->actingAs($user)
        ->get(route('health-entries.index', ['date' => '2026-10-17']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('newEntryAt', '2026-10-17T01:00:00+08:00'));
});
