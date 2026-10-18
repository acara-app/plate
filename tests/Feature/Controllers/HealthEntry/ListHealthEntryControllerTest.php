<?php

declare(strict_types=1);

use App\Enums\HealthSyncType;
use App\Http\Controllers\HealthEntry\ListHealthEntryController;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(ListHealthEntryController::class);

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

it('renders diabetes log index page for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('health-entries.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('displays user diabetes logs', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->count(3)->create(['user_id' => $user->id]);
    HealthSyncSample::factory()->bloodGlucose()->fromWeb()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('health-entries.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('health-entries/index')
            ->has('logs.data', 3));
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

it('filters health entries by type without exposing another user’s readings', function (string $type, array $identifiers): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    foreach (HealthSyncType::entryTypeValues() as $identifier) {
        HealthSyncSample::factory()->create([
            'user_id' => $user->id,
            'type_identifier' => $identifier,
            'group_id' => null,
        ]);
        HealthSyncSample::factory()->create([
            'user_id' => $otherUser->id,
            'type_identifier' => $identifier,
            'group_id' => null,
        ]);
    }

    $this->actingAs($user)
        ->get(route('health-entries.index', ['type' => $type]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('filters.type', $type)
            ->where('logs.total', count($identifiers))
            ->has('logs.data', count($identifiers)));
})->with([
    'food' => ['food', ['carbohydrates', 'protein', 'totalFat', 'dietaryEnergy']],
    'glucose' => ['glucose', ['bloodGlucose']],
    'insulin' => ['insulin', ['insulin']],
    'medication' => ['meds', ['medication', 'medicationDoseEvent']],
    'vitals' => ['vitals', ['weight', 'bloodPressureSystolic', 'bloodPressureDiastolic', 'a1c']],
    'exercise' => ['exercise', ['exerciseMinutes', 'workouts']],
]);

it('rejects invalid entry filters', function (mixed $type): void {
    $this->actingAs(User::factory()->create())
        ->getJson(route('health-entries.index', ['type' => $type]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');
})->with(['unknown' => ['unknown'], 'array' => [['food']]]);
