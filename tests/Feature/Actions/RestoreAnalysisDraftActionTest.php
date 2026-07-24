<?php

declare(strict_types=1);

use App\Actions\RestoreAnalysisDraftAction;
use App\Enums\AnalysisDraftStatus;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Support\Str;

function draftWithToken(array $attributes = []): array
{
    $token = Str::random(64);

    $draft = AnalysisDraft::factory()->create([
        'token_hash' => AnalysisDraft::hashToken($token),
        ...$attributes,
    ]);

    return [$token, $draft];
}

beforeEach(function (): void {
    $this->action = resolve(RestoreAnalysisDraftAction::class);
    $this->user = User::factory()->create();
});

it('claims an unclaimed draft on first restore', function (): void {
    [$token, $draft] = draftWithToken();

    $resolution = $this->action->handle($token, $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::Restored)
        ->and($resolution->draft->user_id)->toBe($this->user->id)
        ->and($resolution->draft->claimed_at)->not->toBeNull()
        ->and($resolution->analysis()->totalCalories)->toBe(490.0);
});

it('restores a draft already claimed by the same user', function (): void {
    [$token] = draftWithToken(['user_id' => $this->user->id, 'claimed_at' => now()->subMinutes(2)]);

    $resolution = $this->action->handle($token, $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::Restored)
        ->and($resolution->draft->claimed_at->getTimestamp())->toBe(now()->subMinutes(2)->getTimestamp());
});

it('rejects a draft claimed by another user without leaking data', function (): void {
    $other = User::factory()->create();
    [$token] = draftWithToken(['user_id' => $other->id, 'claimed_at' => now()]);

    $resolution = $this->action->handle($token, $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::ClaimedByOther)
        ->and($resolution->draft)->toBeNull()
        ->and($resolution->analysis())->toBeNull();
});

it('reports an unknown token as invalid', function (): void {
    $resolution = $this->action->handle(Str::random(64), $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::Invalid)
        ->and($resolution->draft)->toBeNull();
});

it('reports a schema version mismatch as invalid', function (): void {
    [$token] = draftWithToken(['schema_version' => AnalysisDraft::SCHEMA_VERSION + 1]);

    $resolution = $this->action->handle($token, $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::Invalid);
});

it('reports an expired draft with its record still available for analytics', function (): void {
    [$token] = draftWithToken([
        'created_at' => now()->subMinutes(90),
        'expires_at' => now()->subMinutes(30),
    ]);

    $resolution = $this->action->handle($token, $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::Expired)
        ->and($resolution->draft)->not->toBeNull()
        ->and($resolution->draft->ageBand())->toBe('gte_60m');
});

it('reports a consumed draft', function (): void {
    [$token] = draftWithToken([
        'user_id' => $this->user->id,
        'claimed_at' => now(),
        'consumed_at' => now(),
        'health_group_id' => Str::uuid()->toString(),
    ]);

    $resolution = $this->action->handle($token, $this->user);

    expect($resolution->status)->toBe(AnalysisDraftStatus::Consumed)
        ->and($resolution->draft->health_group_id)->not->toBeNull();
});

it('does not claim expired or consumed drafts', function (): void {
    [$expiredToken, $expired] = draftWithToken(['expires_at' => now()->subMinute()]);

    $this->action->handle($expiredToken, $this->user);

    expect($expired->refresh()->user_id)->toBeNull();
});
