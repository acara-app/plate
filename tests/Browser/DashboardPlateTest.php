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

it('logs water from a shortcut without sending a chat message', function (string $routeName, array $parameters, string $shortcut, int $width, int $height): void {
    visit(route($routeName, $parameters, absolute: false))
        ->resize($width, $height)
        ->click($shortcut)
        ->assertPathIs(route('health-entries.index', absolute: false))
        ->assertVisible('[role="dialog"]')
        ->click('[role="dialog"] [aria-label="250 ml"]')
        ->assertAriaAttribute('[role="dialog"] [aria-label="250 ml"]', 'pressed', 'true')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => auth()->id(),
        'type_identifier' => 'water',
        'value' => 250,
        'unit' => 'mL',
    ]);
    $this->assertDatabaseEmpty('agent_conversation_messages');
})->with([
    'the dashboard water moment' => ['dashboard', [], '[data-test="day-plate-moment"][aria-label="Log water"]', 393, 662],
    'the mobile chat starter' => ['chat.create', ['conversationId' => '00000000-0000-4000-8000-000000000001'], '[data-test="chat-starter"]:has-text("Log water"):visible', 393, 662],
    'the desktop chat starter' => ['chat.create', ['conversationId' => '00000000-0000-4000-8000-000000000001'], '[data-test="chat-starter"]:has-text("Log water"):visible', 1440, 900],
]);

it('still drafts a meal question from the breakfast moment on the plate', function (): void {
    visit(route('dashboard', absolute: false))
        ->resize(393, 662)
        ->click('[data-test="day-plate-moment"][aria-label="Plan breakfast"]')
        ->assertValue('[data-test="dashboard-composer"] textarea', 'What should I have for breakfast today?')
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
