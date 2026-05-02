<?php

declare(strict_types=1);

use App\Enums\Telemetry\PaywallEvent;
use App\Models\User;
use App\Services\Telemetry\LogPaywallTelemetry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

covers(LogPaywallTelemetry::class);

beforeEach(function (): void {
    Config::set('plate.telemetry.channel', 'paywall');
});

it('writes the event name plus payload to the configured log channel', function (): void {
    $user = User::factory()->create();
    $channel = Mockery::mock();
    $channel->shouldReceive('info')
        ->once()
        ->withArgs(fn (string $event, array $context): bool => $event === 'paywall_shown'
            && $context['feature'] === 'meal_planner'
            && $context['tier_target'] === 'basic');
    Log::shouldReceive('channel')->with('paywall')->andReturn($channel);

    resolve(LogPaywallTelemetry::class)->emit(
        event: PaywallEvent::PaywallShown,
        user: $user,
        payload: [
            'tier_current' => 'free',
            'tier_target' => 'basic',
            'feature' => 'meal_planner',
        ],
    );
});

it('decorates the payload with tier_current when not already present', function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    $user = User::factory()->create();
    $logger = Log::spy();
    $channel = Mockery::mock();
    $channel->shouldReceive('info')->once()->andReturnUsing(function (string $event, array $context): void {
        expect($event)->toBe('paywall_shown')
            ->and($context['tier_current'])->toBe('free');
    });
    $logger->shouldReceive('channel')->andReturn($channel);

    resolve(LogPaywallTelemetry::class)->emit(
        event: PaywallEvent::PaywallShown,
        user: $user,
        payload: [],
    );
});

it('keeps caller-supplied tier_current and skips re-resolution', function (): void {
    $user = User::factory()->create();
    $logger = Log::spy();
    $channel = Mockery::mock();
    $channel->shouldReceive('info')->once()->andReturnUsing(function (string $event, array $context): void {
        expect($context['tier_current'])->toBe('basic');
    });
    $logger->shouldReceive('channel')->andReturn($channel);

    resolve(LogPaywallTelemetry::class)->emit(
        event: PaywallEvent::UpgradeClicked,
        user: $user,
        payload: ['tier_current' => 'basic'],
    );
});

it('omits tier_current when no user is supplied', function (): void {
    $logger = Log::spy();
    $channel = Mockery::mock();
    $channel->shouldReceive('info')->once()->andReturnUsing(function (string $event, array $context): void {
        expect($context)->not->toHaveKey('tier_current')
            ->and($context['user_id'])->toBeNull();
    });
    $logger->shouldReceive('channel')->andReturn($channel);

    resolve(LogPaywallTelemetry::class)->emit(
        event: PaywallEvent::CheckoutCompleted,
        user: null,
        payload: [],
    );
});
