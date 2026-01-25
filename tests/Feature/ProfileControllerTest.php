<?php

declare(strict_types=1);

use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;

it('renders dietary preferences page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.dietary-preferences.show'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('profile/dietary-preferences'));
});

it('renders dietary preferences page with existing profile', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    $preference = DietaryPreference::factory()->create(['name' => 'Test Preference 1', 'type' => 'allergy']);

    $profile->dietaryPreferences()->attach($preference->id, [
        'severity' => 'moderate',
        'notes' => 'Test note',
    ]);

    $this->actingAs($user)
        ->get(route('profile.dietary-preferences.show'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('profile/dietary-preferences'));
});

it('stores dietary preferences', function (): void {
    $user = User::factory()->create();
    $preference1 = DietaryPreference::factory()->create(['name' => 'Test Preference 2', 'type' => 'intolerance']);
    $preference2 = DietaryPreference::factory()->create(['name' => 'Test Preference 3', 'type' => 'pattern']);

    $this->actingAs($user)
        ->post(route('profile.dietary-preferences.store'), [
            'dietary_preference_ids' => [$preference1->id, $preference2->id],
            'severities' => ['mild', 'moderate'],
            'notes' => ['Note 1', 'Note 2'],
        ])->assertRedirect();

    $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

    expect($profile->dietaryPreferences->pluck('id')->toArray())
        ->toBe([$preference1->id, $preference2->id]);
});

it('renders health conditions page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.health-conditions.show'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('profile/health-conditions'));
});

it('stores health conditions', function (): void {
    $user = User::factory()->create();
    $condition1 = HealthCondition::factory()->create(['name' => 'Test Condition 1']);
    $condition2 = HealthCondition::factory()->create(['name' => 'Test Condition 2']);

    $this->actingAs($user)
        ->post(route('profile.health-conditions.store'), [
            'health_condition_ids' => [$condition1->id, $condition2->id],
            'notes' => ['Note 1', 'Note 2'],
            'units_preference' => 'mg/dL',
        ])->assertRedirect();

    $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

    expect($profile->healthConditions->pluck('id')->toArray())
        ->toBe([$condition1->id, $condition2->id]);
    expect($profile->units_preference->value)->toBe('mg/dL');
});

it('stores health conditions without units preference', function (): void {
    $user = User::factory()->create();
    $condition = HealthCondition::factory()->create(['name' => 'Test Condition 3']);

    $this->actingAs($user)
        ->post(route('profile.health-conditions.store'), [
            'health_condition_ids' => [$condition->id],
            'notes' => ['Note 1'],
        ])->assertRedirect();

    $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

    expect($profile->healthConditions->pluck('id')->toArray())
        ->toBe([$condition->id]);
});

it('renders medications page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.medications.show'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('profile/medications'));
});

it('renders medications page with existing profile', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    $profile->medications()->create(['name' => 'Test Medication']);

    $this->actingAs($user)
        ->get(route('profile.medications.show'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('profile/medications'));
});

it('stores medications', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.medications.store'), [
            'medications' => [
                [
                    'name' => 'Aspirin',
                    'dosage' => '100mg',
                    'frequency' => 'daily',
                    'purpose' => 'Pain relief',
                    'started_at' => '2024-01-01',
                ],
                ['name' => 'Ibuprofen'],
            ],
        ])->assertRedirect();

    $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

    expect($profile->medications->count())->toBe(2);
    expect($profile->medications->pluck('name')->toArray())
        ->toBe(['Aspirin', 'Ibuprofen']);
});

it('validates that medication name is required', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('profile.medications.store'), [
            'medications' => [
                ['dosage' => '100mg'],
            ],
        ])->assertSessionHasErrors(['medications.0.name']);
});

it('replaces existing medications when storing new ones', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();
    $profile->medications()->create(['name' => 'Old Medication']);

    expect($profile->medications->count())->toBe(1);

    $this->actingAs($user)
        ->post(route('profile.medications.store'), [
            'medications' => [
                ['name' => 'New Medication'],
            ],
        ])->assertRedirect();

    $profile->refresh();

    expect($profile->medications->count())->toBe(1);
    expect($profile->medications->first()->name)->toBe('New Medication');
});
