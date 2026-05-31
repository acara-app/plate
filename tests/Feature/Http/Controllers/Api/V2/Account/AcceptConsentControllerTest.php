<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Account\AcceptConsentController;
use App\Http\Requests\Api\V2\ChatStreamRequest;
use App\Models\User;
use Illuminate\Support\Str;

covers(AcceptConsentController::class, ChatStreamRequest::class);

/**
 * @return array<string, string>
 */
function consentBearer(User $user): array
{
    return ['Authorization' => 'Bearer '.$user->createToken('mobile:device-1', ['chat:converse'])->plainTextToken];
}

it('requires authentication', function (): void {
    $this->postJson(route('api.v2.account.consent'), [])->assertUnauthorized();
});

it('records consent timestamps and version', function (): void {
    $user = User::factory()->withoutDisclaimer()->create();

    $this->withHeaders(consentBearer($user))
        ->postJson(route('api.v2.account.consent'), [
            'terms_version' => '2026-01',
            'privacy_version' => '2026-02',
            'medical_disclaimer' => true,
        ])->assertNoContent();

    $user->refresh();
    expect($user->terms_accepted_at)->not->toBeNull()
        ->and($user->privacy_accepted_at)->not->toBeNull()
        ->and($user->accepted_disclaimer_at)->not->toBeNull()
        ->and($user->consent_version)->toBe('2026-01')
        ->and($user->privacy_version)->toBe('2026-02');
});

it('requires the medical disclaimer to be accepted', function (): void {
    $user = User::factory()->create();

    $this->withHeaders(consentBearer($user))
        ->postJson(route('api.v2.account.consent'), [
            'terms_version' => '2026-01',
            'privacy_version' => '2026-01',
            'medical_disclaimer' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors(['medical_disclaimer']);
});

it('blocks chat streaming until the medical disclaimer is accepted', function (): void {
    $user = User::factory()->withoutDisclaimer()->create();

    $this->withHeaders(consentBearer($user))
        ->postJson(route('api.v2.chat.stream', ['conversation' => Str::uuid()->toString()]), [
            'messages' => [],
        ])
        ->assertForbidden()
        ->assertJson(['code' => 'consent_required']);
});
