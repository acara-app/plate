<?php

declare(strict_types=1);

use App\Http\Controllers\UserEmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\URL;

covers(UserEmailVerification::class);

it('may verify email', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::signedRoute(
        'verification.verify',
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
        absolute: false
    );

    $response = $this->actingAs($user)
        ->fromRoute('verification.notice')
        ->get($verificationUrl);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

it('returns to checkout after verifying when a plan was chosen', function (): void {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::signedRoute(
        'verification.verify',
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
        absolute: false
    );

    $this->actingAs($user)
        ->withSession(['checkout.selected_product' => 7])
        ->get($verificationUrl)
        ->assertRedirect(route('checkout.subscription', absolute: false));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('redirects to dashboard if already verified', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $verificationUrl = URL::signedRoute(
        'verification.verify',
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
        absolute: false
    );

    $response = $this->actingAs($user)
        ->fromRoute('verification.notice')
        ->get($verificationUrl);

    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

it('requires valid signature', function (): void {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $invalidUrl = route('verification.verify', [
        'id' => $user->getKey(),
        'hash' => sha1($user->email),
    ]);

    $response = $this->actingAs($user)
        ->fromRoute('verification.notice')
        ->get($invalidUrl);

    $response->assertForbidden();
});
