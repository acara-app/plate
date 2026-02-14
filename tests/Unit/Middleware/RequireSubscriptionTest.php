<?php

declare(strict_types=1);

use App\Http\Middleware\RequireSubscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

it('does not set requiresSubscription attribute when user is not authenticated', function (): void {
    Auth::shouldReceive('user')->andReturn(null)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn ($req): Response => response('OK'));

    expect($request->attributes->has('requiresSubscription'))->toBe(false)
        ->and($response->getContent())->toBe('OK');
});

it('does not require subscription when premium upgrades are disabled', function (): void {
    config()->set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create(['is_verified' => false]);
    Auth::shouldReceive('user')->andReturn($user)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn ($req): Response => response('OK'));

    expect($request->attributes->get('requiresSubscription'))->toBe(false)
        ->and($response->getContent())->toBe('OK');
});

it('does not require subscription when premium upgrades are enabled but user is verified', function (): void {
    config()->set('plate.enable_premium_upgrades', true);

    $user = User::factory()->verified()->create();
    Auth::shouldReceive('user')->andReturn($user)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn ($req): Response => response('OK'));

    expect($request->attributes->get('requiresSubscription'))->toBe(false)
        ->and($response->getContent())->toBe('OK');
});

it('requires subscription when premium upgrades are enabled and user is not verified', function (): void {
    config()->set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create(['is_verified' => false]);
    Auth::shouldReceive('user')->andReturn($user)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn ($req): Response => response('OK'));

    expect($request->attributes->get('requiresSubscription'))->toBe(true)
        ->and($response->getContent())->toBe('OK');
});

it('passes the request to the next middleware', function (): void {
    config()->set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create(['is_verified' => false]);
    Auth::shouldReceive('user')->andReturn($user)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');
    $nextCalled = false;

    $response = $middleware->handle($request, function ($req) use (&$nextCalled): Response {
        $nextCalled = true;

        return response('Next Called');
    });

    expect($nextCalled)->toBe(true)
        ->and($response->getContent())->toBe('Next Called');
});

it('handles user with null is_verified attribute', function (): void {
    config()->set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create(['is_verified' => null]);
    Auth::shouldReceive('user')->andReturn($user)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');

    $middleware->handle($request, fn ($req): Response => response('OK'));

    expect($request->attributes->get('requiresSubscription'))->toBe(true);
});

it('correctly evaluates all conditions in combination', function (bool $isPremiumEnabled, bool $isVerified, bool $expected): void {
    config()->set('plate.enable_premium_upgrades', $isPremiumEnabled);

    $user = User::factory()->create(['is_verified' => $isVerified]);
    Auth::shouldReceive('user')->andReturn($user)->once();

    $middleware = new RequireSubscription();

    $request = Request::create('/', 'GET');

    $middleware->handle($request, fn ($req): Response => response('OK'));

    expect($request->attributes->get('requiresSubscription'))->toBe($expected);
})->with([
    'premium disabled, user not verified' => [false, false, false],
    'premium disabled, user verified' => [false, true, false],
    'premium enabled, user not verified' => [true, false, true],
    'premium enabled, user verified' => [true, true, false],
]);
