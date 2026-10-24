<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\Fixtures\PasskeyAuthenticator;

it('logs in directly with a verified passkey and respects remember me', function (bool $remember): void {
    $user = User::factory()->create();
    $authenticator = new PasskeyAuthenticator;
    $passkey = $authenticator->store($user);
    $challenge = $this->withSession(['url.intended' => route('user-profile.edit')])
        ->getJson(route('passkey.login-options'))->assertOk()->json('options.challenge');
    $oldSessionId = session()->getId();

    $response = $this->postJson(route('passkey.login'), [
        'credential' => $authenticator->assertion($challenge, $user),
        'remember' => $remember,
    ])->assertOk()->assertJsonPath('redirect', route('user-profile.edit'));

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($oldSessionId);
    expect($passkey->fresh()->last_used_at)->not->toBeNull();
    if ($remember) {
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    } else {
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }
})->with([true, false]);

it('rejects login without an active challenge', function (): void {
    $user = User::factory()->create();
    $authenticator = new PasskeyAuthenticator;
    $authenticator->store($user);

    $this->postJson(route('passkey.login'), ['credential' => $authenticator->assertion('expired', $user)])
        ->assertUnprocessable()->assertJsonValidationErrors('credential');

    $this->assertGuest();
});

it('rejects invalid passkey assertions', function (string $failure): void {
    $user = User::factory()->create();
    $authenticator = new PasskeyAuthenticator;
    $passkey = $authenticator->store($user);
    $challenge = $this->getJson(route('passkey.login-options'))->json('options.challenge');
    $credential = $authenticator->assertion($failure === 'challenge' ? 'wrong-challenge' : $challenge, $user, $failure === 'origin' ? 'https://untrusted.example' : null);
    if ($failure === 'signature') {
        $credential['response']['signature'] = ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded(random_bytes(64));
    }
    if ($failure === 'removed') {
        $passkey->delete();
    }

    $this->postJson(route('passkey.login'), ['credential' => $credential])
        ->assertUnprocessable()->assertJsonValidationErrors('credential');

    $this->assertGuest();
    if ($failure !== 'removed') {
        expect($passkey->fresh()->last_used_at)->toBeNull();
    }
})->with(['challenge', 'origin', 'signature', 'removed']);

it('confirms identity with an owned passkey and rejects replay', function (): void {
    $user = User::factory()->create();
    $authenticator = new PasskeyAuthenticator;
    $authenticator->store($user);
    $challenge = $this->actingAs($user)->withSession(['url.intended' => route('passkeys.edit')])
        ->getJson(route('passkey.confirm-options'))->assertOk()->json('options.challenge');
    $credential = $authenticator->assertion($challenge, $user);

    $this->postJson(route('passkey.confirm'), ['credential' => $credential])
        ->assertOk()->assertJsonPath('redirect', route('passkeys.edit'));
    $this->getJson(route('password.confirmation'))->assertJsonPath('confirmed', true);
    session()->forget('auth.password_confirmed_at');
    $this->postJson(route('passkey.confirm'), ['credential' => $credential])
        ->assertUnprocessable()->assertJsonValidationErrors('credential');
    $this->getJson(route('password.confirmation'))->assertJsonPath('confirmed', false);
});

it('rejects confirming identity with another accounts passkey', function (): void {
    $owner = User::factory()->create();
    $authenticator = new PasskeyAuthenticator;
    $authenticator->store($owner);
    $challenge = $this->actingAs(User::factory()->create())
        ->getJson(route('passkey.confirm-options'))->assertOk()->json('options.challenge');

    $this->postJson(route('passkey.confirm'), ['credential' => $authenticator->assertion($challenge, $owner)])
        ->assertUnprocessable()->assertJsonValidationErrors('credential');
    $this->getJson(route('password.confirmation'))->assertJsonPath('confirmed', false);
});

it('throttles repeated passkey requests', function (bool $options): void {
    $this->withSession([])->withCookie(config('session.cookie'), session()->getId())->withCredentials();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        if ($options) {
            $this->getJson(route('passkey.login-options'))->assertOk();
        } else {
            $this->postJson(route('passkey.login'), ['credential' => ['id' => 'same-credential']])->assertUnprocessable();
        }
    }

    if ($options) {
        $this->getJson(route('passkey.login-options'))->assertTooManyRequests();
    } else {
        $this->postJson(route('passkey.login'), ['credential' => ['id' => 'same-credential']])->assertTooManyRequests();
    }
    $this->assertGuest();
})->with(['session and IP' => true, 'credential and IP' => false]);

it('validates malformed credential identifiers without a server error', function (): void {
    $this->postJson(route('passkey.login'), ['credential' => ['id' => ['invalid']]])
        ->assertUnprocessable()->assertJsonValidationErrors('credential.id');
    $this->assertGuest();
});
