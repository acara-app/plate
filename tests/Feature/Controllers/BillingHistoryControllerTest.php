<?php

declare(strict_types=1);

use App\Models\User;

it('renders billing history page', function (): void {
    $user = User::factory()->create();

    $this->withoutVite();

    $response = $this->actingAs($user)
        ->get(route('billing.index'));

    $response->assertOk();
});

it('handles exception when fetching invoices', function (): void {
    $user = User::factory()->create(['stripe_id' => null]);

    $this->withoutVite();

    $response = $this->actingAs($user)
        ->get(route('billing.index'));

    $response->assertOk();
});
