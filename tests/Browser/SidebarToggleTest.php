<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('collapses the sidebar from its own button and reopens it from the page header', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('chat.index', absolute: false))
        ->assertDataAttribute('[data-slot="sidebar"]', 'state', 'expanded')
        ->assertAriaAttribute('#app-sidebar [data-sidebar="trigger"]', 'label', 'Close sidebar')
        ->click('#app-sidebar [data-sidebar="trigger"]')
        ->assertDataAttribute('[data-slot="sidebar"]', 'state', 'collapsed')
        ->assertNotPresent('#app-sidebar [data-sidebar="trigger"]')
        ->assertAriaAttribute('header [data-sidebar="trigger"]', 'label', 'Open sidebar')
        ->assertAriaAttribute('header [data-sidebar="trigger"]', 'expanded', 'false')
        ->click('header [data-sidebar="trigger"]')
        ->assertDataAttribute('[data-slot="sidebar"]', 'state', 'expanded')
        ->assertAriaAttribute('header [data-sidebar="trigger"]', 'expanded', 'true')
        ->assertPresent('#app-sidebar [data-sidebar="trigger"]')
        ->assertNoJavaScriptErrors();
});
