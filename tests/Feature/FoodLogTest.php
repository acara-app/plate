<?php

declare(strict_types=1);

use App\Models\User;

it('requires authentication', function (): void {
    $response = $this->get(route('food-log.create'));

    $response->assertRedirectToRoute('login');
});

it('requires email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('food-log.create'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders food log page for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('food-log.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ongoing-tracking/create-food-log'));
});
