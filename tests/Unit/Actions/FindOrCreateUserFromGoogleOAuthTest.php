<?php

declare(strict_types=1);

use App\Actions\FindOrCreateUserFromGoogleOAuth;
use App\Exceptions\AccountLinkException;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

covers(FindOrCreateUserFromGoogleOAuth::class);

function googleAccount(string $id, string $email, ?string $name, ?bool $emailVerified = true): SocialiteUser
{
    $googleUser = new SocialiteUser();
    $googleUser->setRaw($emailVerified === null ? [] : ['email_verified' => $emailVerified]);
    $googleUser->id = $id;
    $googleUser->email = $email;
    $googleUser->name = $name;

    return $googleUser;
}

beforeEach(function (): void {
    $this->action = resolve(FindOrCreateUserFromGoogleOAuth::class);
});

it('creates a verified user when Google confirms the email', function (): void {
    $user = $this->action->handle(googleAccount('google_new_123', 'newuser@test.com', 'New Test User'));

    expect($user->google_id)->toBe('google_new_123')
        ->and($user->email)->toBe('newuser@test.com')
        ->and($user->name)->toBe('New Test User')
        ->and($user->email_verified_at)->not->toBeNull();
})->group('oauth', 'actions');

it('creates an unverified user when Google does not confirm the email', function (?bool $emailVerified): void {
    $user = $this->action->handle(googleAccount('google_unconfirmed', 'unconfirmed@test.com', 'Someone', $emailVerified));

    expect($user->email_verified_at)->toBeNull();
})->with(['not verified' => [false], 'claim missing' => [null]])->group('oauth', 'actions');

it('returns a returning Google user without changing their email or name', function (): void {
    $existingUser = User::factory()->create(['google_id' => 'google_existing_456', 'email' => 'old@test.com', 'name' => 'Old Name']);

    $user = $this->action->handle(googleAccount('google_existing_456', 'new@test.com', 'New Name'));

    expect($user->id)->toBe($existingUser->id)
        ->and($user->email)->toBe('old@test.com')
        ->and($user->name)->toBe('Old Name');
})->group('oauth', 'actions');

it('links Google to an existing verified account with the same email', function (): void {
    $existingUser = User::factory()->create(['google_id' => null, 'email' => 'existing@test.com', 'name' => 'Existing User']);

    $user = $this->action->handle(googleAccount('google_link_789', 'existing@test.com', 'Updated User'));

    expect($user->id)->toBe($existingUser->id)
        ->and($user->google_id)->toBe('google_link_789')
        ->and($user->name)->toBe('Existing User');
})->group('oauth', 'actions');

it('refuses to link Google to an existing account whose email was never verified', function (): void {
    User::factory()->unverified()->create(['google_id' => null, 'email' => 'claimed@test.com']);

    $this->action->handle(googleAccount('google_owner', 'claimed@test.com', 'Real Owner'));
})->throws(AccountLinkException::class)->group('oauth', 'actions');

it('uses a default name when Google provides none for a new user', function (): void {
    expect($this->action->handle(googleAccount('google_noname_999', 'noname@test.com', null))->name)->toBe('No Name');
})->group('oauth', 'actions');
