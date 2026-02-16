<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function (): void {
    // Create a mock provider that can be configured per test
    $this->provider = new class implements Provider
    {
        public ?SocialiteUser $user = null;

        public ?Exception $exception = null;

        public function redirect(): RedirectResponse
        {
            return new RedirectResponse('https://accounts.google.com');
        }

        public function user(): SocialiteUser
        {
            if ($this->exception instanceof Exception) {
                throw $this->exception;
            }

            return $this->user ?? new SocialiteUser();
        }
    };

    // Bind the mock provider to the Socialite facade
    Socialite::swap(new readonly class($this->provider)
    {
        public function __construct(private Provider $provider) {}

        public function driver(?string $driver = null): Provider
        {
            if ($driver === 'google') {
                return $this->provider;
            }

            throw new InvalidArgumentException("Driver [{$driver}] not supported.");
        }
    });
});

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

    $this->provider->user = $googleUser;

    $response = get(route('auth.google.callback'));

    $response->assertRedirectToRoute('dashboard');

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

    $this->provider->user = $googleUser;

    get(route('auth.google.callback'));

    $user = User::query()->where('email', 'verified@example.com')->first();
    expect($user->email_verified_at)->not->toBeNull();
})->group('oauth');

it('links Google account to existing user by email and redirects to chat', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);
    $existingUser->profile()->create([
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google456';
    $googleUser->email = 'existing@example.com';
    $googleUser->name = 'Updated Name';

    $this->provider->user = $googleUser;

    $response = get(route('auth.google.callback'));

    $response->assertRedirectToRoute('dashboard');

    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('google456');
    expect($existingUser->name)->toBe('Updated Name');
    expect(Auth::id())->toBe($existingUser->id);
})->group('oauth');

it('updates existing Google user information on login and redirects to chat', function (): void {
    $existingUser = User::factory()->create([
        'google_id' => 'google789',
        'email' => 'oldmail@example.com',
        'name' => 'Old Name',
    ]);
    $existingUser->profile()->create([
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);

    $googleUser = new SocialiteUser();
    $googleUser->id = 'google789';
    $googleUser->email = 'newmail@example.com';
    $googleUser->name = 'New Name';

    $this->provider->user = $googleUser;

    $response = get(route('auth.google.callback'));

    $response->assertRedirectToRoute('dashboard');

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

    $this->provider->user = $googleUser;

    $response = get(route('auth.google.callback'));

    $response->assertRedirectToRoute('dashboard');

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

    $this->provider->user = $googleUser;

    get(route('auth.google.callback'));

    $existingUser->refresh();
    expect($existingUser->name)->toBe('Original Name');
})->group('oauth');

it('redirects to login with error on OAuth exception', function (): void {
    $this->provider->exception = new Exception('OAuth Error');

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

    $this->provider->user = $googleUser;

    $response = get(route('auth.google.callback'));

    $response->assertRedirectToRoute('dashboard');

    // Should update the existing user with the Google ID
    $user = User::query()->where('google_id', 'google_duplicate')->first();
    expect($user->email)->toBe('second@example.com');
})->group('oauth');
