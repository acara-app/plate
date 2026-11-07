<?php

declare(strict_types=1);

use App\Listeners\SyncUserTimezoneOnSignIn;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

covers(SyncUserTimezoneOnSignIn::class);

it('saves the browser timezone of a visitor who signs up', function (): void {
    $this->withSession(['timezone' => 'Asia/Ulaanbaatar'])
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password1234',
            'accepted_disclaimer' => '1',
        ]);

    expect(User::query()->where('email', 'test@example.com')->sole()->timezone)->toBe('Asia/Ulaanbaatar');
});

it('keeps the saved timezone when signing in before the browser reports one', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'timezone' => 'Asia/Ulaanbaatar',
    ]);

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->timezone)->toBe('Asia/Ulaanbaatar');
});
