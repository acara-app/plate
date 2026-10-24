<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Pest\Browser\Playwright\Playwright;
use Pest\Browser\Support\ComputeUrl;
use Tests\Fixtures\PasskeyBrowserAuthenticator;

afterEach(function (): void {
    Playwright::setHost(null);
});

it('enrolls signs in confirms and removes a passkey in the browser', function (bool $mobile): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    $origin = mb_rtrim(str_replace('127.0.0.1', 'localhost', ComputeUrl::from('/')), '/');
    $url = $origin.'/settings/passkeys';
    Playwright::setHost('localhost');
    URL::useOrigin($origin);
    URL::useAssetOrigin($origin);
    config()->set('passkeys.relying_party_id', 'localhost');
    config()->set('passkeys.allowed_origins', [$origin]);
    $pendingPage = visit($url)->withLocale('en-US');
    if ($mobile) {
        $pendingPage = $pendingPage->on()->mobile();
    }
    $page = $pendingPage->assertSee('Confirm your identity');
    PasskeyBrowserAuthenticator::enable($page);

    $page->fill('password', 'password')
        ->click('[data-test="confirm-password-button"]')
        ->assertSee('No passkeys yet');
    $page->click('Add passkey')
        ->fill('#passkey-name', 'Browser key')
        ->click('Register passkey')
        ->assertSee('Browser key');

    $this->assertDatabaseHas('passkeys', ['user_id' => $user->id, 'name' => 'Browser key']);

    if ($mobile) {
        $page->click('[data-sidebar="trigger"]');
    }
    $page->click('[data-test="sidebar-menu-button"]')
        ->click('[data-test="logout-button"]')
        ->navigate($url)
        ->assertPathIs('/login')
        ->click('[data-test="login-passkey"]')
        ->assertSee('Confirm your identity')
        ->click('[data-test="confirm-passkey"]')
        ->assertPathIs('/settings/passkeys')
        ->assertSee('Browser key')
        ->click('[aria-label="Remove Browser key"]')
        ->click('button:has-text("Remove passkey")')
        ->assertSee('No passkeys yet')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth')
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseCount('passkeys', 0);
})->with(['desktop' => false, 'mobile' => true]);

it('recovers from a cancelled passkey prompt', function (): void {
    $page = visit('/login')->assertSee('Sign in with a passkey');
    $page->script('() => { navigator.credentials.get = async () => { throw new DOMException("Cancelled", "NotAllowedError"); }; }');

    $page->click('[data-test="login-passkey"]')
        ->assertSee('Passkey verification was cancelled or timed out. You can try again.')
        ->assertEnabled('[data-test="login-passkey"]')
        ->assertEnabled('[data-test="login-button"]')
        ->assertSee('Forgot password?')
        ->assertNoJavaScriptErrors();
});

it('keeps password and Google login available in unsupported browsers', function (): void {
    $page = visit('/login')->assertSee('Sign in with a passkey');
    $page->page()->context()->addInitScript('Object.defineProperty(window, "PublicKeyCredential", { value: undefined });');

    $page->refresh()
        ->assertMissing('[data-test="login-passkey"]')
        ->assertVisible('input[name="password"]')
        ->assertEnabled('[data-test="login-button"]')
        ->assertSee('Continue with Google')
        ->assertNoJavaScriptErrors();
});

it('guides passwordless accounts through signing out to set a password', function (): void {
    $this->actingAs(User::factory()->withoutTwoFactor()->create(['password' => null, 'google_id' => 'google-account']));

    visit('/settings/passkeys')
        ->assertSee('Before adding your first passkey, set a password for this account.')
        ->assertMissing('input[name="password"]')
        ->assertMissing('[data-test="confirm-passkey"]')
        ->click('Sign out to set a password')
        ->navigate('/login')
        ->click('Forgot password?')
        ->assertPathIs('/forgot-password')
        ->assertVisible('input[name="email"]')
        ->assertNoJavaScriptErrors();
});

it('requests fresh identity confirmation when enrollment confirmation expires', function (): void {
    $this->actingAs(User::factory()->withoutTwoFactor()->create());
    $page = visit('/settings/passkeys')
        ->fill('password', 'password')
        ->click('[data-test="confirm-password-button"]')
        ->assertSee('Add passkey');
    $this->travel(4)->hours();

    $page->click('Add passkey')->fill('#passkey-name', 'My laptop')->click('Register passkey')
        ->assertSee('Confirm your identity')
        ->fill('password', 'password')
        ->click('[data-test="confirm-password-button"]')
        ->assertPathIs('/settings/passkeys')
        ->assertSee('No passkeys yet')
        ->assertNoJavaScriptErrors();
    $this->assertDatabaseCount('passkeys', 0);
});

it('allows retrying when the passkey request fails', function (): void {
    $page = visit('/login')->assertSee('Sign in with a passkey');
    $page->script('() => { const fetch = window.fetch; window.fetch = (input, init) => String(input).includes("/passkeys/login/options") ? Promise.resolve(new Response("{}", { status: 503 })) : fetch(input, init); }');

    $page->click('[data-test="login-passkey"]')
        ->assertSee('We could not complete the passkey request. Please try again.')
        ->assertEnabled('[data-test="login-passkey"]')
        ->assertEnabled('[data-test="login-button"]')
        ->assertNoJavaScriptErrors();
});
