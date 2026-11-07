<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\OnceProp;

covers(HandleInertiaRequests::class);

it('shares app name from config', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('name')
        ->and($shared['name'])->toBe(config('app.name'));
});

it('shares null user when guest', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('auth')
        ->and($shared['auth'])->toHaveKey('user')
        ->and($shared['auth']['user'])->toBeNull();
});

it('shares authenticated user data', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['auth']['user'])->not->toBeNull()
        ->and($shared['auth']['user']->id)->toBe($user->id)
        ->and($shared['auth']['user']->name)->toBe('Test User')
        ->and($shared['auth']['user']->email)->toBe('test@example.com');
});

it('defaults sidebarOpen to true when no cookie', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('sidebarOpen')
        ->and($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to true when cookie is true', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'true');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeTrue();
});

it('sets sidebarOpen to false when cookie is false', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->cookies->set('sidebar_state', 'false');

    $shared = $middleware->share($request);

    expect($shared['sidebarOpen'])->toBeFalse();
});

it('includes parent shared data', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('errors');
});

it('defaults locale to en for guests', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared['locale'])->toBe('en');
});

it('uses the user language for both server and portal copy', function (string $locale, string $planName): void {
    $user = User::factory()->create([
        'locale' => $locale,
    ]);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['locale'])->toBe($locale)
        ->and(__('common.photo_plans.free.name'))->toBe($planName);
})->with([
    'English' => ['en', 'Free scans'],
    'French' => ['fr', 'Scans gratuits'],
    'Mongolian' => ['mn', 'Үнэгүй зураг шинжилгээ'],
]);

it('defaults to en when user has no locale set', function (): void {
    $user = User::factory()->create([
        'locale' => null,
    ]);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['locale'])->toBe('en');
});

it('shares translations for current locale', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('translations')
        ->and($shared['translations'])->toBeInstanceOf(OnceProp::class);

    $translations = $shared['translations']();
    expect($translations)->toBeArray();
});

it('loads auth translations', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    $translations = $shared['translations']();

    expect($translations)->toHaveKey('auth')
        ->and($translations['auth'])->toHaveKey('login');
});

it('loads common translations', function (): void {
    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');

    $shared = $middleware->share($request);

    $translations = $shared['translations']();

    expect($translations)->toHaveKey('common')
        ->and($translations['common'])->toBeArray();
});

it('shares null entitlement when no user is authenticated', function (): void {
    $middleware = new HandleInertiaRequests();

    $shared = $middleware->share(Request::create('/', 'GET'));

    expect($shared)->toHaveKey('entitlement')
        ->and($shared['entitlement'])->toBeNull();
});

it('shares the resolved entitlement for authenticated users', function (): void {
    enableBilling();

    $user = User::factory()->create();

    $middleware = new HandleInertiaRequests();
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('entitlement')
        ->and($shared['entitlement'])->toMatchArray([
            'tier' => 'free',
            'tier_label' => 'Free',
            'payment_pending' => false,
            'payment_recovery_url' => null,
            'premium_enforcement_active' => true,
            'on_grace_period' => false,
            'grace_period_ends_at' => null,
        ]);
});

it('marks the entitlement as unrestricted when billing is off', function (): void {
    disableBilling();

    $user = User::factory()->create();

    $middleware = new HandleInertiaRequests();
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['entitlement'])->toMatchArray([
        'tier' => 'free',
        'premium_enforcement_active' => false,
        'payment_pending' => false,
    ]);
});

it('shares whether the user needs to review their biometrics', function (bool $needsReview): void {
    $user = User::factory()->create();
    $user->profile()->create(['needs_re_onboarding' => $needsReview]);

    $middleware = new HandleInertiaRequests();

    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);

    $shared = $middleware->share($request);

    expect($shared['needsBiometricsReview']())->toBe($needsReview);
})->with([
    'flagged profile' => [true],
    'confirmed profile' => [false],
]);

it('never asks guests to review biometrics', function (): void {
    $middleware = new HandleInertiaRequests();

    $shared = $middleware->share(Request::create('/', 'GET'));

    expect($shared['needsBiometricsReview']())->toBeFalse();
});
