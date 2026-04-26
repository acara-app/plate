<?php

declare(strict_types=1);

it('returns 200 for the caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful();
});

it('renders the caffeine calculator under the mini-app layout with the expected title', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSee('<title>Coffee Caffeine Calculator: How Much Is Too Much?</title>', false)
        ->assertSee('Caffeine Calculator');
});

it('renders the H1 and subheading copy', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            '<h1',
            'Coffee Caffeine Calculator: How Much Is Too Much?',
            '</h1>',
            'Choose your drink, tell us about you, and find your safe daily limit.',
        ], false);
});

it('registers the caffeine calculator route at /tools/caffeine-calculator without auth middleware', function (): void {
    $route = collect(app('router')->getRoutes())
        ->first(fn ($route) => $route->getName() === 'caffeine-calculator');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('tools/caffeine-calculator')
        ->and($route->gatherMiddleware())->not->toContain('auth')
        ->and($route->gatherMiddleware())->not->toContain('auth:web');
});
