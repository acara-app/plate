<?php

declare(strict_types=1);

use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function intendedDraftToken(): array
{
    $token = Str::random(64);

    $draft = AnalysisDraft::factory()->create([
        'token_hash' => AnalysisDraft::hashToken($token),
    ]);

    return [$token, $draft, route('snap-to-track.review', ['draft' => $token], absolute: false)];
}

function fakeGoogleUserForIntended(string $googleId, string $email): void
{
    $googleUser = new SocialiteUser();
    $googleUser->id = $googleId;
    $googleUser->email = $email;
    $googleUser->name = 'Snap Tester';

    $provider = new readonly class($googleUser) implements Provider
    {
        public function __construct(private SocialiteUser $googleUser) {}

        public function redirect(): RedirectResponse
        {
            return new RedirectResponse('https://accounts.google.com');
        }

        public function user(): SocialiteUser
        {
            return $this->googleUser;
        }
    };

    Socialite::swap(new readonly class($provider)
    {
        public function __construct(private Provider $provider) {}

        public function driver(?string $driver = null): Provider
        {
            return $this->provider;
        }
    });
}

beforeEach(function (): void {
    $this->withoutVite();

    config()->set('plate.snap_to_track.activation_funnel', true);
});

it('carries a guest draft through email registration and verification to the review page', function (): void {
    [, $draft, $reviewUrl] = intendedDraftToken();

    $this->withSession(['url.intended' => $reviewUrl, 'snap_to_track.auth_path' => 'register'])
        ->post(route('register.store'), [
            'name' => 'Snap Tester',
            'email' => 'snap@example.com',
            'password' => 'SnapPassword123!',
            'password_confirmation' => 'SnapPassword123!',
            'accepted_disclaimer' => '1',
        ])
        ->assertRedirect($reviewUrl);

    $this->get($reviewUrl)->assertRedirect(route('verification.notice'));

    expect(session('url.intended'))->toBe(url($reviewUrl));

    $user = User::query()->where('email', 'snap@example.com')->sole();

    $verificationUrl = URL::signedRoute(
        'verification.verify',
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
        absolute: false,
    );

    $this->get($verificationUrl)->assertRedirect($reviewUrl);

    $this->get($reviewUrl)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('snap-to-track/review')
            ->where('state', 'restored'));

    expect($draft->refresh()->user_id)->toBe($user->id);
});

it('carries a guest draft through a plain login to the review page', function (): void {
    [, $draft, $reviewUrl] = intendedDraftToken();

    $user = User::factory()->withoutTwoFactor()->create();

    $this->withSession(['url.intended' => $reviewUrl, 'snap_to_track.auth_path' => 'login'])
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect($reviewUrl);

    $this->get($reviewUrl)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('state', 'restored'));

    expect($draft->refresh()->user_id)->toBe($user->id);
});

it('carries a guest draft through Google signup and the disclaimer gate to the review page', function (): void {
    [, $draft, $reviewUrl] = intendedDraftToken();

    fakeGoogleUserForIntended('google-snap-1', 'snap-google@example.com');

    $this->withSession(['url.intended' => $reviewUrl, 'snap_to_track.auth_path' => 'register'])
        ->get(route('auth.google.callback'))
        ->assertRedirect($reviewUrl);

    $this->get($reviewUrl)->assertRedirect(route('disclaimer.show'));

    expect(session('url.intended'))->toBe(url($reviewUrl));

    $this->post(route('disclaimer.accept'), ['accepted' => '1'])
        ->assertRedirect($reviewUrl);

    $this->get($reviewUrl)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('state', 'restored'));

    $user = User::query()->where('email', 'snap-google@example.com')->sole();

    expect($draft->refresh()->user_id)->toBe($user->id);
});

it('carries a guest draft through a Google login for an existing user to the review page', function (): void {
    [, $draft, $reviewUrl] = intendedDraftToken();

    $user = User::factory()->create(['google_id' => 'google-snap-2']);

    fakeGoogleUserForIntended('google-snap-2', $user->email);

    $this->withSession(['url.intended' => $reviewUrl])
        ->get(route('auth.google.callback'))
        ->assertRedirect($reviewUrl);

    $this->get($reviewUrl)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('state', 'restored'));

    expect($draft->refresh()->user_id)->toBe($user->id);
});
