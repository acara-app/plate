<?php

declare(strict_types=1);

use App\Models\User;

it('requires authentication', function (): void {
    $response = $this->get(route('meal-plans.create'));

    $response->assertRedirectToRoute('login');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.create'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders create meal plan page for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('meal-plans.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('meal-plans/create'));
});