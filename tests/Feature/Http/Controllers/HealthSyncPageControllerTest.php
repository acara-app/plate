<?php

declare(strict_types=1);

use App\Http\Controllers\HealthSyncPageController;

covers(HealthSyncPageController::class);

it('shows the configured iOS requirements and download link on the landing page', function (): void {
    config([
        'acara.health_sync.app_store_url' => 'https://example.com/health-sync',
        'acara.health_sync.minimum_ios_version' => '19.2',
    ]);

    $this->get(route('health-sync'))
        ->assertOk()
        ->assertViewIs('health-sync.index')
        ->assertSee('Acara Health Sync')
        ->assertSee('End-to-End Encrypted')
        ->assertDontSee('Coming soon to the App Store')
        ->assertSee('iOS 19.2 or later')
        ->assertSee('href="https://example.com/health-sync"', false)
        ->assertSee(route('health-sync.setup'));
});

it('links to the iOS app and landing page from the setup guide', function (): void {
    $this->get(route('health-sync.setup'))
        ->assertOk()
        ->assertViewIs('health-sync.setup')
        ->assertSee('Set Up Health Sync')
        ->assertSee('Sign In')
        ->assertDontSee('Acara Health Sync is currently in development')
        ->assertSee(config()->string('acara.health_sync.app_store_url'))
        ->assertSee(route('health-sync'));
});
