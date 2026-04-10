<?php

declare(strict_types=1);

use App\Http\Controllers\HealthSyncPageController;

covers(HealthSyncPageController::class);

it('displays the health sync landing page', function (): void {
    $this->get(route('health-sync'))
        ->assertOk()
        ->assertViewIs('health-sync.index');
});

it('displays the health sync setup page', function (): void {
    $this->get(route('health-sync.setup'))
        ->assertOk()
        ->assertViewIs('health-sync.setup');
});

it('landing page contains expected SEO content', function (): void {
    $this->get(route('health-sync'))
        ->assertSee('Acara Health Sync')
        ->assertSee('End-to-End Encrypted');
});

it('setup page contains expected SEO content', function (): void {
    $this->get(route('health-sync.setup'))
        ->assertSee('Set Up Health Sync')
        ->assertSee('Generate a Pairing Token');
});

it('landing page links to setup guide', function (): void {
    $this->get(route('health-sync'))
        ->assertSee(route('health-sync.setup'));
});

it('setup page links back to landing page', function (): void {
    $this->get(route('health-sync.setup'))
        ->assertSee(route('health-sync'));
});
