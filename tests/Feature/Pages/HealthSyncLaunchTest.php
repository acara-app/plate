<?php

declare(strict_types=1);

beforeEach(function (): void {
    $this->appStoreUrl = config()->string('acara.health_sync.app_store_url');
});

it('announces the iOS app and links to Health Sync from the home page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Health Sync (iOS)')
        ->assertSee(route('health-sync'))
        ->assertSee('Your Apple Health data, where you actually use it.')
        ->assertSee($this->appStoreUrl);
});

it('offers both iOS app and PWA on the install-app page', function (): void {
    $this->get(route('install-app'))
        ->assertOk()
        ->assertSee('Acara Health Sync')
        ->assertSee('Progressive Web App')
        ->assertSee($this->appStoreUrl);
});

it('links to the App Store from the support page iOS section', function (): void {
    $this->get(route('support'))
        ->assertOk()
        ->assertSee('Acara Health Sync (iOS)')
        ->assertSee($this->appStoreUrl);
});

it('promotes the iOS app on the diabetes log book info page', function (): void {
    $this->get(route('diabetes-log-book-info'))
        ->assertOk()
        ->assertSee('Option 2: Fully Automatic via Apple Health')
        ->assertSee($this->appStoreUrl);
});

it('promotes the iOS app on each coaching page', function (string $route): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee($this->appStoreUrl);
})->with(['meet-altani', 'ai-health-coach', 'ai-nutritionist', 'ai-personal-trainer', 'meal-planner']);
