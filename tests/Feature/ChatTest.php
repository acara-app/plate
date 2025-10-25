<?php

declare(strict_types=1);

use App\Models\User;

it('requires authentication', function (): void {
    $response = $this->get(route('chat.create'));

    $response->assertRedirectToRoute('login');
});

it('requires email verification', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('chat.create'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders chat page for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('chat.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/create-chat'));
});
