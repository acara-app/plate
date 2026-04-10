<?php

declare(strict_types=1);

use App\Http\Controllers\HealthSyncPageController;

covers(HealthSyncPageController::class);

beforeEach(function (): void {
    $this->appStoreUrl = config('plate.health_sync.app_store_url');
});

it('exposes the App Store URL through config', function (): void {
    expect(config('plate.health_sync.app_store_url'))
        ->toBe('https://apps.apple.com/us/app/acara-health-sync/id6761504525');
});

it('removes the coming soon placeholder from the health sync landing page', function (): void {
    $this->get(route('health-sync'))
        ->assertOk()
        ->assertDontSee('Coming soon to the App Store')
        ->assertSee($this->appStoreUrl);
});

it('shows the App Store badge on the health sync setup page', function (): void {
    $this->get(route('health-sync.setup'))
        ->assertOk()
        ->assertDontSee('Acara Health Sync is currently in development')
        ->assertSee($this->appStoreUrl);
});

it('links to the App Store from the footer on the home page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Health Sync (iOS)')
        ->assertSee(route('health-sync'));
});

it('announces the iOS app launch on the home page', function (): void {
    $this->get(route('home'))
        ->assertOk()
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

it('promotes the iOS app on the meet-altani page', function (): void {
    $this->get(route('meet-altani'))
        ->assertOk()
        ->assertSee($this->appStoreUrl);
});

it('promotes the iOS app on the ai-health-coach page', function (): void {
    $this->get(route('ai-health-coach'))
        ->assertOk()
        ->assertSee($this->appStoreUrl);
});

it('promotes the iOS app on the ai-nutritionist page', function (): void {
    $this->get(route('ai-nutritionist'))
        ->assertOk()
        ->assertSee($this->appStoreUrl);
});

it('promotes the iOS app on the ai-personal-trainer page', function (): void {
    $this->get(route('ai-personal-trainer'))
        ->assertOk()
        ->assertSee($this->appStoreUrl);
});

it('promotes the iOS app on the meal-planner page', function (): void {
    $this->get(route('meal-planner'))
        ->assertOk()
        ->assertSee($this->appStoreUrl);
});
