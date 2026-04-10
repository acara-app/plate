<?php

declare(strict_types=1);

use App\Actions\FindOrCreateUserFromGoogleOAuth;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

covers(FindOrCreateUserFromGoogleOAuth::class);

beforeEach(function (): void {
    $this->action = new FindOrCreateUserFromGoogleOAuth();
});

it('creates a new user when Google ID does not exist', function (): void {
    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_new_123';
    $googleUser->email = 'newuser@test.com';
    $googleUser->name = 'New Test User';

    $user = $this->action->handle($googleUser);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->google_id)->toBe('google_new_123')
        ->and($user->email)->toBe('newuser@test.com')
        ->and($user->name)->toBe('New Test User')
        ->and($user->email_verified_at)->not->toBeNull();
})->group('oauth', 'actions');

it('updates existing user when Google ID matches', function (): void {
    $existingUser = User::factory()->create([
        'google_id' => 'google_existing_456',
        'email' => 'old@test.com',
        'name' => 'Old Name',
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_existing_456';
    $googleUser->email = 'new@test.com';
    $googleUser->name = 'New Name';

    $user = $this->action->handle($googleUser);

    expect($user->id)->toBe($existingUser->id)
        ->and($user->email)->toBe('new@test.com')
        ->and($user->name)->toBe('New Name');
})->group('oauth', 'actions');

it('links Google account to existing user by email', function (): void {
    $existingUser = User::factory()->create([
        'google_id' => null,
        'email' => 'existing@test.com',
        'name' => 'Existing User',
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_link_789';
    $googleUser->email = 'existing@test.com';
    $googleUser->name = 'Updated User';

    $user = $this->action->handle($googleUser);

    expect($user->id)->toBe($existingUser->id)
        ->and($user->google_id)->toBe('google_link_789')
        ->and($user->name)->toBe('Updated User');
})->group('oauth', 'actions');

it('uses default name when Google provides null name for new user', function (): void {
    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_noname_999';
    $googleUser->email = 'noname@test.com';
    $googleUser->name = null;

    expect($this->action->handle($googleUser)->name)->toBe('No Name');
})->group('oauth', 'actions');

it('preserves existing name when Google provides null name for existing user', function (): void {
    User::factory()->create([
        'google_id' => null,
        'email' => 'preserve@test.com',
        'name' => 'Original Name',
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_preserve_111';
    $googleUser->email = 'preserve@test.com';
    $googleUser->name = null;

    expect($this->action->handle($googleUser)->name)->toBe('Original Name');
})->group('oauth', 'actions');

it('preserves existing name when Google ID matches and provides null name', function (): void {
    User::factory()->create([
        'google_id' => 'google_keep_222',
        'email' => 'keep@test.com',
        'name' => 'Keep This Name',
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_keep_222';
    $googleUser->email = 'keep@test.com';
    $googleUser->name = null;

    expect($this->action->handle($googleUser)->name)->toBe('Keep This Name');
})->group('oauth', 'actions');

it('sets email_verified_at for new users created via OAuth', function (): void {
    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_verified_333';
    $googleUser->email = 'verified@test.com';
    $googleUser->name = 'Verified User';

    $user = $this->action->handle($googleUser);

    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->email_verified_at)->toBeInstanceOf(DateTimeImmutable::class);
})->group('oauth', 'actions');
