<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\User;

final class FakePaywallTelemetry implements EmitsPaywallEvents
{
    /**
     * @var array<int, array{event: PaywallEvent, user_id: ?int, payload: array<string, mixed>}>
     */
    public array $emitted = [];

    /**
     * @param  array<string, mixed>  $payload
     */
    public function emit(PaywallEvent $event, ?User $user = null, array $payload = []): void
    {
        $this->emitted[] = [
            'event' => $event,
            'user_id' => $user?->id,
            'payload' => $payload,
        ];
    }

    /**
     * @return array<int, array{event: PaywallEvent, user_id: ?int, payload: array<string, mixed>}>
     */
    public function eventsOfType(PaywallEvent $event): array
    {
        return array_values(array_filter(
            $this->emitted,
            static fn (array $entry): bool => $entry['event'] === $event,
        ));
    }

    public function reset(): void
    {
        $this->emitted = [];
    }
}
