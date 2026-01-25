<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('requires authentication', function (): void {
    $response = $this->post(route('meal-plans.store'));

    $response->assertRedirectToRoute('login');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'));

    $response->assertRedirectToRoute('verification.notice');
});

it('stores meal plan for authenticated user', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'prompt' => 'Test custom prompt',
        ]);

    $response->assertRedirect();
});