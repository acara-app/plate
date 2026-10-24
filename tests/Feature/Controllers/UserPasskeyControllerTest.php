<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Tests\Fixtures\PasskeyAuthenticator;

beforeEach(function (): void {
    $this->withoutVite();
});

it('registers a named passkey after confirmation', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $authenticator = new PasskeyAuthenticator;
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $challenge = $this->getJson(route('passkey.registration-options'))->assertOk()->json('options.challenge');

    $this->postJson(route('passkey.store'), [
        'name' => 'My phone',
        'credential' => $authenticator->registration($challenge),
    ])->assertOk()->assertJsonPath('status', 'passkey-registered');

    $this->assertDatabaseHas('passkeys', ['user_id' => $user->id, 'name' => 'My phone']);
});

it('shows an empty passkey list after identity confirmation', function (): void {
    $this->actingAs(User::factory()->withoutTwoFactor()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/settings/passkeys')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('user-passkeys/edit', false)
            ->where('canManagePasskeys', true)
            ->where('passkeys', []));
});

it('lists only the current users passkeys without exposing credentials', function (): void {
    $user = User::factory()->create();
    $passkey = (new PasskeyAuthenticator)->store($user);
    (new PasskeyAuthenticator)->store(User::factory()->create());

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('passkeys.edit'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('passkeys', 1)
            ->where('passkeys.0', [
                'id' => $passkey->id,
                'name' => 'My phone',
                'authenticator' => null,
                'created_at' => $passkey->created_at->toIso8601String(),
                'last_used_at' => null,
            ])
            ->missing('auth.user.passkeys'));
});

it('requires identity confirmation before managing passkeys', function (): void {
    $user = User::factory()->create();
    $passkey = (new PasskeyAuthenticator)->store($user);
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - 20000]);

    $this->get(route('passkeys.edit'))->assertRedirectToRoute('password.confirm');
    $this->getJson(route('passkey.registration-options'))->assertStatus(423);
    $this->postJson(route('passkey.store'), [])->assertStatus(423);
    $this->deleteJson(route('passkey.destroy', $passkey))->assertStatus(423);

    $this->assertModelExists($passkey);
});

it('keeps account security settings available before onboarding', function (string $state): void {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('passkeys.edit'))->assertOk();
})->with([
    'unverified email' => 'unverified',
    'missing disclaimer' => 'withoutDisclaimer',
]);

it('keeps passkey management inaccessible to guests', function (): void {
    $this->get(route('passkeys.edit'))->assertRedirectToRoute('login');
    $this->getJson(route('passkey.registration-options'))->assertUnauthorized();
    $this->postJson(route('passkey.store'))->assertUnauthorized();
});

it('removes an owned passkey after confirmation', function (): void {
    $user = User::factory()->create();
    $passkey = (new PasskeyAuthenticator)->store($user);

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson(route('passkey.destroy', $passkey))
        ->assertOk()->assertJsonPath('status', 'passkey-deleted');

    $this->assertModelMissing($passkey);
});

it('forbids removing another accounts passkey', function (): void {
    $passkey = (new PasskeyAuthenticator)->store(User::factory()->create());

    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson(route('passkey.destroy', $passkey))->assertForbidden();

    $this->assertModelExists($passkey);
});

it('advertises the passkey management page to password managers', function (): void {
    $this->getJson(route('well-known.passkeys'))->assertExactJson([
        'enroll' => route('passkeys.edit'),
        'manage' => route('passkeys.edit'),
    ]);
});

it('hides passkey entry points when the feature is disabled', function (): void {
    config()->set('fortify.features', []);
    $this->get(route('login'))->assertInertia(fn (AssertableInertia $page) => $page->where('canManagePasskeys', false));
    $this->getJson(route('well-known.passkeys'))->assertNotFound();
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('passkeys.edit'))->assertNotFound();
});

it('offers password setup to accounts without a password or passkey', function (): void {
    $this->actingAs(User::factory()->withoutTwoFactor()->create(['password' => null, 'google_id' => 'google-account']))
        ->get(route('password.confirm'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasPassword', false)
            ->where('canConfirmWithPasskey', false));
});

it('offers passkey confirmation only to accounts with an enrolled passkey', function (bool $enrolled): void {
    $user = User::factory()->create();
    if ($enrolled) {
        (new PasskeyAuthenticator)->store($user);
    }

    $this->actingAs($user)->get(route('password.confirm'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('hasPassword', true)
            ->where('canConfirmWithPasskey', $enrolled));
})->with([true, false]);

it('deletes passkeys when the account is deleted', function (): void {
    $user = User::factory()->create();
    $passkey = (new PasskeyAuthenticator)->store($user);
    Illuminate\Support\Facades\Storage::fake(App\Models\MealPhoto::DISK);

    app(App\Actions\DeleteUser::class)->handle($user);

    $this->assertModelMissing($user);
    $this->assertModelMissing($passkey);
});

it('validates the passkey name before enrollment', function (mixed $name): void {
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()])
        ->postJson(route('passkey.store'), ['name' => $name])
        ->assertUnprocessable()->assertJsonValidationErrors('name');

    $this->assertDatabaseCount('passkeys', 0);
})->with(['missing' => null, 'too long' => str_repeat('a', 256)]);

it('rejects enrollment without a fresh registration challenge', function (bool $replayed): void {
    $user = User::factory()->create();
    $authenticator = new PasskeyAuthenticator;
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
    $challenge = $this->getJson(route('passkey.registration-options'))->json('options.challenge');
    $payload = ['name' => 'My phone', 'credential' => $authenticator->registration($challenge)];
    if ($replayed) {
        $this->postJson(route('passkey.store'), $payload)->assertOk();
    } else {
        session()->forget('passkey.registration_options');
    }

    $this->postJson(route('passkey.store'), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('credential');
    $this->assertDatabaseCount('passkeys', $replayed ? 1 : 0);
})->with(['missing' => false, 'replayed' => true]);
