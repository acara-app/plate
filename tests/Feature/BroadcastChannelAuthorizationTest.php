<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('broadcasting.default', 'reverb');
    require base_path('routes/channels.php');
});

it('authorizes users for their own private chat channel', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.1234',
            'channel_name' => 'private-chat.'.$user->id,
        ])
        ->assertOk();
});

it('rejects users from another private chat channel', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.1234',
            'channel_name' => 'private-chat.'.$other->id,
        ])
        ->assertForbidden();
});
