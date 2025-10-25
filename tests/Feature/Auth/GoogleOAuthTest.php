<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

it('redirects to Google OAuth page', function (): void {
    $response = get(route('auth.google.redirect'));

    $response->assertRedirect();
    expect($response->getTargetUrl())->toContain('accounts.google.com');
})->group('oauth');

it('creates new user from Google OAuth callback with mocked provider', function (): void {
    $googleUser = new SocialiteUser();
    $googleUser->id = 'google123';
    $googleUser->email = 'newuser@example.com';
    $googleUser->name = 'New User';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $response = get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));

    assertDatabaseHas('users', [
        'google_id' => 'google123',
        'email' => 'newuser@example.com',
        'name' => 'New User',
    ]);

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->email)->toBe('newuser@example.com');
})->group('oauth');

it('marks email as verified when creating user from Google OAuth', function (): void {
    $googleUser = new SocialiteUser();
    $googleUser->id = 'google123';
    $googleUser->email = 'verified@example.com';
    $googleUser->name = 'Verified User';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    get(route('auth.google.callback'));

    $user = User::query()->where('email', 'verified@example.com')->first();
    expect($user->email_verified_at)->not->toBeNull();
})->group('oauth');

it('links Google account to existing user by email', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google456';
    $googleUser->email = 'existing@example.com';
    $googleUser->name = 'Updated Name';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $response = get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));

    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('google456');
    expect($existingUser->name)->toBe('Updated Name');
    expect(Auth::id())->toBe($existingUser->id);
})->group('oauth');

it('updates existing Google user information on login', function (): void {
    $existingUser = User::factory()->create([
        'google_id' => 'google789',
        'email' => 'oldmail@example.com',
        'name' => 'Old Name',
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google789';
    $googleUser->email = 'newmail@example.com';
    $googleUser->name = 'New Name';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $response = get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));

    $existingUser->refresh();
    expect($existingUser->email)->toBe('newmail@example.com');
    expect($existingUser->name)->toBe('New Name');
    expect(Auth::id())->toBe($existingUser->id);
})->group('oauth');

it('handles missing name from Google gracefully for new users', function (): void {
    $googleUser = new SocialiteUser();
    $googleUser->id = 'google999';
    $googleUser->email = 'noname@example.com';
    $googleUser->name = null;

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $response = get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));

    assertDatabaseHas('users', [
        'google_id' => 'google999',
        'email' => 'noname@example.com',
        'name' => 'No Name',
    ]);
})->group('oauth');

it('handles missing name from Google gracefully for existing users', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'name' => 'Original Name',
        'google_id' => null,
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google111';
    $googleUser->email = 'existing@example.com';
    $googleUser->name = null;

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    get(route('auth.google.callback'));

    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name');
})->group('oauth');

it('redirects to login with error on OAuth exception', function (): void {
    Socialite::shouldReceive('driver')
        ->with('google')
        ->andThrow(new Exception('OAuth Error'));

    $response = get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Something went wrong!');
    expect(Auth::check())->toBeFalse();
})->group('oauth');

it('handles duplicate Google ID gracefully', function (): void {
    User::factory()->create([
        'google_id' => 'google_duplicate',
        'email' => 'first@example.com',
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google_duplicate';
    $googleUser->email = 'second@example.com';
    $googleUser->name = 'Second User';

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);

    $response = get(route('auth.google.callback'));

    $response->assertRedirect(route('dashboard'));

    // Should update the existing user with the Google ID
    $user = User::query()->where('google_id', 'google_duplicate')->first();
    expect($user->email)->toBe('second@example.com');
})->group('oauth');
