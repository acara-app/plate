<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

it('requires accepted_disclaimer checkbox during registration', function (): void {
    post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('accepted_disclaimer');
});

it('creates user with accepted_disclaimer_at when checkbox is accepted', function (): void {
    post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'accepted_disclaimer' => '1',
    ])->assertRedirect();

    assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    $user = User::query()->where('email', 'test@example.com')->first();
    expect($user->accepted_disclaimer_at)->not->toBeNull();
});

it('redirects to disclaimer page when user has not accepted', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('disclaimer.show'));
});

it('allows access to dashboard when disclaimer is accepted', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => now(),
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('accepts disclaimer and redirects to dashboard', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->post(route('disclaimer.accept'), ['accepted' => '1'])
        ->assertRedirect();

    $user->refresh();
    expect($user->accepted_disclaimer_at)->not->toBeNull();
});
