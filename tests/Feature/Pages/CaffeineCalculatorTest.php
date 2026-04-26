<?php

declare(strict_types=1);

it('returns 200 for the caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful();
});

it('registers the caffeine calculator route at /tools/caffeine-calculator without auth middleware', function (): void {
    $route = collect(app('router')->getRoutes())
        ->first(fn ($route) => $route->getName() === 'caffeine-calculator');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('tools/caffeine-calculator')
        ->and($route->gatherMiddleware())->not->toContain('auth')
        ->and($route->gatherMiddleware())->not->toContain('auth:web');
});
