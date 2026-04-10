<?php

declare(strict_types=1);

use App\Http\Controllers\HouseholdController;
use App\Models\User;
use App\Models\UserProfile;

covers(HouseholdController::class);

it('renders household edit page', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('household.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('household/edit')
            ->has('householdContext'));
});

it('renders household edit page with existing context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'household_context' => 'My husband and two kids',
    ]);

    $response = $this->actingAs($user)
        ->get(route('household.edit'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('household/edit')
            ->where('householdContext', 'My husband and two kids'));
});

it('updates household context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('household.edit')
        ->patch(route('household.update'), [
            'household_context' => 'My husband Bataa is 38, has type 2 diabetes.',
        ]);

    $response->assertRedirectToRoute('household.edit');

    expect($user->profile->refresh()->household_context)
        ->toBe('My husband Bataa is 38, has type 2 diabetes.');
});

it('allows clearing household context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create([
        'household_context' => 'Previous context',
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('household.edit')
        ->patch(route('household.update'), [
            'household_context' => null,
        ]);

    $response->assertRedirectToRoute('household.edit');

    expect($user->profile->refresh()->household_context)->toBeNull();
});

it('rejects household context exceeding 2000 characters', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create();

    $response = $this->actingAs($user)
        ->fromRoute('household.edit')
        ->patch(route('household.update'), [
            'household_context' => str_repeat('a', 2001),
        ]);

    $response->assertSessionHasErrors('household_context');
});

it('auto-creates profile when visiting household edit', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('household.edit'));

    $response->assertOk();

    expect($user->profile()->exists())->toBeTrue();
});

it('requires authentication', function (): void {
    $this->get(route('household.edit'))
        ->assertRedirect(route('login'));

    $this->patch(route('household.update'))
        ->assertRedirect(route('login'));
});
