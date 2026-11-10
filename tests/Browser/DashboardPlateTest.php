<?php

declare(strict_types=1);

use App\Enums\ConsentPurpose;
use App\Models\Consent;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $user = User::factory()->create([
        'name' => 'Bartholomew Example',
        'privacy_version' => config()->string('plate.privacy_policy_version'),
    ]);
    Consent::factory()->for($user)->create(['purpose' => ConsentPurpose::ProcessHealthData]);
    UserProfile::factory()->for($user)->create(['onboarding_completed' => true]);

    $this->actingAs($user);
});

it('keeps Snap to Track on the first screen of a phone, above the composer', function (int $width, int $height): void {
    visit(route('dashboard', absolute: false))
        ->resize($width, $height)
        ->assertSee('Snap to Track')
        ->assertScript(<<<'JS'
            (() => {
                const snap = [...document.querySelectorAll('[data-test="dashboard-snap-to-track"]')]
                    .find((link) => link.offsetParent !== null)
                    .getBoundingClientRect();
                const composer = document.querySelector('[data-test="dashboard-composer"]').getBoundingClientRect();

                return snap.top >= 0 && snap.bottom <= composer.top && composer.bottom <= window.innerHeight;
            })()
            JS)
        ->assertNoJavaScriptErrors();
})->with([
    'a 6.1 inch iPhone in Chrome' => [393, 662],
    'a narrow iPhone in Safari' => [375, 635],
    'a compact Android phone' => [360, 672],
]);

it('drafts a hydration question from the water moment on the plate', function (): void {
    visit(route('dashboard', absolute: false))
        ->resize(393, 662)
        ->click('[data-test="day-plate-moment"][aria-label="Stay hydrated"]')
        ->assertValue('[data-test="dashboard-composer"] textarea', 'How much water should I drink today?')
        ->assertNoJavaScriptErrors();
});

it('offers a scan shortcut in the top right corner of a phone', function (): void {
    visit(route('dashboard', absolute: false))
        ->resize(393, 662)
        ->assertAriaAttribute('[data-test="dashboard-scan-shortcut"]', 'label', 'Snap to Track')
        ->assertScript(<<<'JS'
            (() => {
                const shortcut = document.querySelector('[data-test="dashboard-scan-shortcut"]').getBoundingClientRect();
                const menu = document.querySelector('section [data-sidebar="trigger"]').getBoundingClientRect();

                return shortcut.top === menu.top && shortcut.height === menu.height && Math.round(window.innerWidth - shortcut.right) === Math.round(menu.left);
            })()
            JS)
        ->click('[data-test="dashboard-scan-shortcut"]')
        ->assertPathIs(route('snap-to-track.index', absolute: false));
});
