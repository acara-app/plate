<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Str;

it('lets mobile users navigate from row padding and reopen the sidebar', function (string $start, int $width, int $height, string $theme): void {
    $this->actingAs(User::factory()->create());
    $path = $start === 'chat'
        ? route('chat.create', ['conversationId' => (string) Str::uuid()], absolute: false)
        : route('health-entries.index', absolute: false);

    $page = visit($path)->on()->mobile()->withLocale('en-US')->resize($width, $height);
    $page->script("document.documentElement.classList.toggle('dark', '{$theme}' === 'dark')");
    $page->assertScript(<<<'JS'
        (() => { const rect = document.querySelector('[data-sidebar="trigger"]').getBoundingClientRect(); return rect.width >= 48 && rect.height >= 48; })()
        JS)
        ->click('[data-sidebar="trigger"]')
        ->assertVisible('#app-sidebar')
        ->screenshot(false, "mobile-sidebar-{$width}-{$theme}");

    $page->assertScript(<<<'JS'
        [...document.querySelectorAll('#app-sidebar [data-sidebar="menu-button"]')].every((item) => item.getBoundingClientRect().height >= 48)
        JS);

    $page->page()->locator('#app-sidebar a[href="/chat"]')->tap(['position' => ['x' => 4, 'y' => 4]]);

    $page->assertPathIs('/chat')
        ->assertMissing('#app-sidebar')
        ->click('[data-sidebar="trigger"]')
        ->assertVisible('#app-sidebar a[href="/chat"][aria-current="page"]')
        ->click('#app-sidebar a[href="/chat"]')
        ->assertMissing('#app-sidebar')
        ->click('[data-sidebar="trigger"]')
        ->assertVisible('#app-sidebar');

    $page->page()->locator('#app-sidebar a[href="/chat"]')->click(['modifiers' => ['ControlOrMeta']]);
    $page->assertVisible('#app-sidebar');
    $page->page()->locator('#app-sidebar a[target="_blank"]')->first()->click();
    $page->assertVisible('#app-sidebar')
        ->click('[data-test="sidebar-menu-button"]')
        ->click('[role="menuitem"]:has-text("Settings")')
        ->assertPathIs('/settings/profile')
        ->assertMissing('#app-sidebar')
        ->click('[data-sidebar="trigger"]')
        ->keys('#app-sidebar button[aria-label="Close sidebar"]', 'Escape')
        ->assertMissing('#app-sidebar')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->resize(1280, 900)
        ->assertVisible('#app-sidebar')
        ->assertScript('document.querySelector(\'#app-sidebar a[href="/chat"]\').getBoundingClientRect().height', 32)
        ->screenshot(false, 'sidebar-desktop-expanded')
        ->keys('#app-sidebar a[href="/chat"]', 'Tab')
        ->assertScript('document.activeElement.matches(":focus-visible")')
        ->click('#app-sidebar button[aria-label="Close sidebar"]')
        ->assertScript('document.querySelector(\'#app-sidebar a[href="/chat"]\').getBoundingClientRect().width', 32)
        ->screenshot(false, 'sidebar-desktop-collapsed')
        ->assertNoJavaScriptErrors();
})->with([
    'standard layout, narrow portrait' => ['standard', 320, 568, 'light'],
    'chat layout, portrait' => ['chat', 390, 844, 'dark'],
    'standard layout, landscape' => ['standard', 667, 375, 'dark'],
]);
