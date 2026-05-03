<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Billing\PremiumRolloutGate;
use Illuminate\Support\Facades\Config;

covers(PremiumRolloutGate::class);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', false);
    Config::set('plate.premium_rollout.allowlist', []);
    Config::set('plate.premium_rollout.percentage', 0);
});

function gate(): PremiumRolloutGate
{
    return resolve(PremiumRolloutGate::class);
}

it('returns true for everyone when the global flag is on', function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create();

    expect(gate()->isActiveFor($user))->toBeTrue()
        ->and(gate()->isActiveFor())->toBeTrue()
        ->and(gate()->reasonFor($user))->toBe('global_flag');
});

it('returns false for guests when the flag is off', function (): void {
    expect(gate()->isActiveFor())->toBeFalse()
        ->and(gate()->reasonFor())->toBe('disabled');
});

it('returns false for users not in any rollout cohort', function (): void {
    $user = User::factory()->create();

    expect(gate()->isActiveFor($user))->toBeFalse()
        ->and(gate()->reasonFor($user))->toBe('disabled');
});

it('admits users whose email is on the allowlist', function (): void {
    $user = User::factory()->create(['email' => 'staff@example.com']);

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com', 'someone@else.test']);

    expect(gate()->isActiveFor($user))->toBeTrue()
        ->and(gate()->reasonFor($user))->toBe('allowlist');
});

it('admits users whose id is on the allowlist', function (): void {
    $user = User::factory()->create();

    Config::set('plate.premium_rollout.allowlist', [(string) $user->id]);

    expect(gate()->isActiveFor($user))->toBeTrue()
        ->and(gate()->reasonFor($user))->toBe('allowlist');
});

it('matches allowlist emails case-insensitively', function (): void {
    $user = User::factory()->create(['email' => 'Staff@Example.Com']);

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);

    expect(gate()->isActiveFor($user))->toBeTrue();
});

it('admits everyone when percentage is 100', function (): void {
    Config::set('plate.premium_rollout.percentage', 100);

    $user = User::factory()->create();

    expect(gate()->isActiveFor($user))->toBeTrue()
        ->and(gate()->reasonFor($user))->toBe('percentile');
});

it('admits no one when percentage is 0', function (): void {
    Config::set('plate.premium_rollout.percentage', 0);

    $user = User::factory()->create();

    expect(gate()->isActiveFor($user))->toBeFalse();
});

it('is deterministic per user across calls', function (): void {
    Config::set('plate.premium_rollout.percentage', 50);

    $user = User::factory()->create();

    $first = gate()->isActiveFor($user);
    $second = gate()->isActiveFor($user);
    $third = gate()->isActiveFor($user);

    expect($first)->toBe($second)
        ->and($second)->toBe($third);
});

it('keeps both rollout signals independent so allowlist works without percentage', function (): void {
    $allowlisted = User::factory()->create(['email' => 'staff@example.com']);
    $notAllowlisted = User::factory()->create(['email' => 'random@example.com']);

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);
    Config::set('plate.premium_rollout.percentage', 0);

    expect(gate()->isActiveFor($allowlisted))->toBeTrue()
        ->and(gate()->isActiveFor($notAllowlisted))->toBeFalse();
});

it('roughly approximates the configured percentage across many users', function (): void {
    Config::set('plate.premium_rollout.percentage', 30);

    $users = User::factory()->count(100)->create();

    $hits = $users->filter(fn (User $u): bool => gate()->isActiveFor($u))->count();

    expect($hits)->toBeGreaterThan(15)
        ->and($hits)->toBeLessThan(45);
});
