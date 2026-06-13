<?php

declare(strict_types=1);

use App\Models\User;

it('does not expose the removed mobile sync settings page', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/mobile-sync')
        ->assertNotFound();
});

it('does not expose the removed pairing token endpoint', function (): void {
    $this->postJson('/api/v2/sync/pair', [
        'token' => 'ABCDEFGH',
        'device_name' => 'iPhone',
    ])->assertNotFound();
});
