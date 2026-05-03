<?php

declare(strict_types=1);

namespace App\Services\Telemetry;

use App\Contracts\Billing\ResolvesUserTier;
use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final readonly class LogPaywallTelemetry implements EmitsPaywallEvents
{
    public function __construct(private ResolvesUserTier $resolveUserTier) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function emit(PaywallEvent $event, ?User $user = null, array $payload = []): void
    {
        $context = [
            'event' => $event->value,
            'user_id' => $user?->id,
            'timestamp' => now()->toIso8601String(),
            ...$payload,
        ];

        if ($user instanceof User && ! array_key_exists('tier_current', $context)) {
            $context['tier_current'] = $this->resolveUserTier->resolve($user)->tier->value;
        }

        $channel = config()->string('plate.telemetry.channel', 'paywall');

        Log::channel($channel)->info($event->value, $context);
    }
}
